<?php
/**
 * lib/escpos.php — ESC/POS Termal Yazıcı (58mm / 80mm POS Printer) Uyum Motoru
 * (Epson, Xprinter, Bixolon, Citizen, Zjiang, RawBT ve Web Print Tam Uyumlu)
 */

class EscPosPrinter {

    // ESC/POS Kontrol Komutları (Hex / Binary)
    const ESC_INIT        = "\x1B\x40";          // Yazıcıyı sıfırla
    const ALIGN_LEFT      = "\x1B\x61\x00";      // Sola hizala
    const ALIGN_CENTER    = "\x1B\x61\x01";      // Ortala
    const ALIGN_RIGHT     = "\x1B\x61\x02";      // Sağa hizala
    const MODE_NORMAL     = "\x1B\x21\x00";      // Normal yazı
    const MODE_BOLD       = "\x1B\x45\x01";      // Kalın yazı
    const MODE_BOLD_OFF   = "\x1B\x45\x00";      // Normal kalınlık
    const TEXT_DOUBLE_WH  = "\x1D\x21\x11";      // Çift Genişlik + Çift Yükseklik (Kupon Kodu İçin)
    const TEXT_LARGE      = "\x1D\x21\x22";      // Dev Boyut
    const PAPER_CUT       = "\x1D\x56\x42\x00";  // Kağıt Kes (Giotin Cut)
    const FEED_LINES      = "\x1B\x64\x04";      // 4 satır boşluk sür

    /**
     * ESC/POS Ham Binary Komut Dizisi Üretir (Yazıcıya doğrudan ham soket / bluetooth / USB ile gönderilebilir)
     */
    public static function buildRawBinary(
        string $code,
        float $price,
        string $profile,
        string $businessName = 'LAGOON HOTSPOT & WIFI',
        string $phone = '0555 123 4567',
        string $note = '5651 Sayılı Kanun Gereğince İnternet Erişimi Kayıt Altına Alınmaktadır.'
    ): string {
        $bName = self::toAscii(strtoupper($businessName));
        $note  = self::toAscii($note);

        $out  = self::ESC_INIT;
        
        // Başlık (Ortalı & Kalın)
        $out .= self::ALIGN_CENTER . self::MODE_BOLD;
        $out .= "================================\n";
        $out .= "$bName\n";
        if ($phone) {
            $out .= "TEL: $phone\n";
        }
        $out .= "================================\n" . self::MODE_BOLD_OFF;

        // Kupon Bilgisi
        $out .= self::ALIGN_CENTER;
        $out .= "HOTSPOT INTERNET KUPONU\n\n";
        $out .= "KUPON KODU / PIN:\n";

        // Dev Boyutta Kupon Kodu
        $out .= self::TEXT_DOUBLE_WH . self::MODE_BOLD;
        $out .= "  $code  \n";
        $out .= self::MODE_NORMAL . self::MODE_BOLD_OFF . "\n";

        // Paket Detayları
        $out .= self::ALIGN_CENTER;
        $out .= "PAKET: $profile\n";
        $out .= "FIYAT: " . number_format($price, 2) . " TL\n";
        $out .= "TARIH: " . date('Y-m-d H:i:s') . "\n";
        $out .= "--------------------------------\n";

        // Alt Bilgi Notu
        $out .= self::ALIGN_CENTER;
        $out .= wordwrap($note, 32, "\n", true) . "\n";
        $out .= "--------------------------------\n\n";

        // Besleme ve Kağıt Kesme
        $out .= self::FEED_LINES . self::PAPER_CUT;

        return $out;
    }

    /**
     * 58mm / 80mm Termal Yazıcı İçin 32 Karakter Hizalı Düz Metin Üretir
     */
    public static function buildThermalText(
        string $code,
        float $price,
        string $profile,
        string $businessName = 'LAGOON HOTSPOT & WIFI',
        string $phone = '0555 123 4567',
        string $note = '5651 Sayılı Kanun Gereğince İnternet Erişimi Kayıt Altına Alınmaktadır.'
    ): string {
        $lines = [];
        $lines[] = "================================";
        $lines[] = self::centerPad($businessName, 32);
        if ($phone) {
            $lines[] = self::centerPad("Tel: $phone", 32);
        }
        $lines[] = "================================";
        $lines[] = "";
        $lines[] = self::centerPad("HOTSPOT KUPON KODU", 32);
        $lines[] = "";
        $lines[] = self::centerPad("*** $code ***", 32);
        $lines[] = "";
        $lines[] = "Paket : " . $profile;
        $lines[] = "Fiyat : " . number_format($price, 2) . " TL";
        $lines[] = "Tarih : " . date('d.m.Y H:i');
        $lines[] = "--------------------------------";
        $lines[] = wordwrap($note, 32, "\n", true);
        $lines[] = "--------------------------------";
        $lines[] = "";

        return implode("\r\n", $lines) . "\r\n";
    }

    private static function centerPad(string $str, int $width = 32): string {
        $len = mb_strlen($str, 'UTF-8');
        if ($len >= $width) return $str;
        $left = floor(($width - $len) / 2);
        return str_repeat(' ', $left) . $str;
    }

    private static function toAscii(string $str): string {
        $tr = ['ç'=>'c', 'Ç'=>'C', 'ğ'=>'g', 'Ğ'=>'G', 'ı'=>'i', 'İ'=>'I', 'ö'=>'o', 'Ö'=>'O', 'ş'=>'s', 'Ş'=>'S', 'ü'=>'u', 'Ü'=>'U'];
        return strtr($str, $tr);
    }

    /**
     * Ham ESC/POS Komutlarını Ağdaki Termal Yazıcı IP ve Portuna (TCP Socket Port 9100) Doğrudan Gönderir
     */
    public static function printToNetworkPrinter(string $ip, int $port, string $rawBinaryData): array {
        if (empty($ip)) {
            return ['success' => false, 'error' => 'Termal yazıcı IP adresi tanımlanmamış. Lütfen Ayarlar sayfasından yazıcı IP adresini giriniz.'];
        }

        $fp = @fsockopen($ip, $port, $errno, $errstr, 4);
        if (!$fp) {
            return ['success' => false, 'error' => "Termal yazıcıya ulaşılamadı ($ip:$port) — Hata: $errstr ($errno)"];
        }

        fwrite($fp, $rawBinaryData);
        fclose($fp);

        return ['success' => true, 'message' => "Fiş başarıyla termal yazıcıya ($ip:$port) gönderildi ve basıldı!"];
    }
}
