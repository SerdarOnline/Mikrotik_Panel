<?php
/**
 * RouterOS PHP API Client
 * Supports RouterOS 6.x and 7.x
 * Port: 8728 (plain) / 8729 (SSL)
 */
class RouterosAPI
{
    private $socket = null;
    private $debug = false;
    private $connected = false;
    public $error = '';

    public function connect(string $ip, string $user, string $pass, int $port = 8728, int $timeout = 10): bool
    {
        $this->socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        if (!$this->socket) {
            $this->error = "Connection failed: $errstr ($errno)";
            return false;
        }
        socket_set_timeout($this->socket, $timeout);

        // Login
        $this->write('/login', true);
        $response = $this->read();

        // RouterOS 6.x challenge-response login
        if (isset($response[0]) && strpos($response[0], '=ret=') !== false) {
            $challenge = pack('H*', substr($response[0], 5));
            $md5 = md5(chr(0) . $pass . $challenge);
            $this->write('/login');
            $this->write('=name=' . $user);
            $this->write('=response=00' . $md5, true);
        } else {
            // RouterOS 7.x plain login
            $this->write('/login');
            $this->write('=name=' . $user);
            $this->write('=password=' . $pass, true);
        }

        $response = $this->read();
        if (isset($response[0]) && $response[0] === '!done') {
            $this->connected = true;
            return true;
        }

        $this->error = 'Login failed: ' . implode(', ', $response);
        fclose($this->socket);
        $this->socket = null;
        return false;
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
        $this->connected = false;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function comm(string $command, array $params = []): array
    {
        $this->write($command);
        foreach ($params as $key => $value) {
            $this->write('=' . $key . '=' . $value);
        }
        $this->write('', true); // End of sentence
        return $this->parseResponse($this->readFull());
    }

    private function write(string $word, bool $last = false): void
    {
        if ($word !== '') {
            $len = strlen($word);
            $encoded = '';
            if ($len < 0x80) {
                $encoded = chr($len);
            } elseif ($len < 0x4000) {
                $len |= 0x8000;
                $encoded = chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
            } elseif ($len < 0x200000) {
                $len |= 0xC00000;
                $encoded = chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
            } elseif ($len < 0x10000000) {
                $len |= 0xE0000000;
                $encoded = chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
            } else {
                $encoded = chr(0xF0) . chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
            }
            fwrite($this->socket, $encoded . $word);
        }
        if ($last) {
            fwrite($this->socket, chr(0));
        }
    }

    private function read(): array
    {
        $response = [];
        while (true) {
            $word = $this->readWord();
            if ($word === '') {
                break;
            }
            $response[] = $word;
        }
        return $response;
    }

    // !done veya !fatal gelene kadar tüm sentence'ları oku
    private function readFull(): array
    {
        $response = [];
        while (true) {
            $word = $this->readWord();
            if ($word === '') {
                // sentence sonu — !done görüp görmediğimize bak
                if (in_array('!done', $response, true) || in_array('!fatal', $response, true)) {
                    break;
                }
                continue; // başka !re'ler gelebilir
            }
            $response[] = $word;
            if ($word === '!done' || $word === '!fatal') {
                $this->readWord(); // son boş kelimeyi yut
                break;
            }
        }
        return $response;
    }


    private function readWord(): string
    {
        $len = $this->readLength();
        if ($len === 0) return '';
        $word = '';
        $remaining = $len;
        while ($remaining > 0) {
            $chunk = fread($this->socket, $remaining);
            if ($chunk === false || $chunk === '') break;
            $word .= $chunk;
            $remaining -= strlen($chunk);
        }
        return $word;
    }

    private function readLength(): int
    {
        $b = ord(fread($this->socket, 1));
        if ($b & 0x80) {
            if (($b & 0xC0) === 0x80) {
                $b &= ~0x80;
                return ($b << 8) | ord(fread($this->socket, 1));
            } elseif (($b & 0xE0) === 0xC0) {
                $b &= ~0xC0;
                return ($b << 16) | (ord(fread($this->socket, 1)) << 8) | ord(fread($this->socket, 1));
            } elseif (($b & 0xF0) === 0xE0) {
                $b &= ~0xE0;
                return ($b << 24) | (ord(fread($this->socket, 1)) << 16) | (ord(fread($this->socket, 1)) << 8) | ord(fread($this->socket, 1));
            } elseif (($b & 0xF8) === 0xF0) {
                return (ord(fread($this->socket, 1)) << 24) | (ord(fread($this->socket, 1)) << 16) | (ord(fread($this->socket, 1)) << 8) | ord(fread($this->socket, 1));
            }
        }
        return $b;
    }

    private function parseResponse(array $raw): array
    {
        $result = [];
        $currentItem = [];
        foreach ($raw as $word) {
            if ($word === '!done') {
                if (!empty($currentItem)) {
                    $result[] = $currentItem;
                    $currentItem = [];
                }
            } elseif ($word === '!re') {
                if (!empty($currentItem)) {
                    $result[] = $currentItem;
                }
                $currentItem = [];
            } elseif ($word === '!trap' || $word === '!fatal') {
                $currentItem['!error'] = $word;
            } elseif (substr($word, 0, 1) === '=') {
                $parts = explode('=', ltrim($word, '='), 2);
                if (count($parts) === 2) {
                    $currentItem[$parts[0]] = $parts[1];
                }
            }
        }
        if (!empty($currentItem)) {
            $result[] = $currentItem;
        }
        return $result;
    }
}
