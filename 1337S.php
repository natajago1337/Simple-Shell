<?php
/**
 * Backdoor Shell Scanner - Fungsi untuk memindai file mencurigakan
 * 
 * @param string $directory Path direktori yang akan dipindai
 * @param bool $outputToConsole Tampilkan output ke console (CLI)
 * @return array Daftar file yang terindikasi backdoor
 */
function scanBackdoorShell($directory, $outputToConsole = true) {
    // Daftar ekstensi file yang rawan
    $extensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'jsp', 'go', 'py', 'pl', 'cgi'];
    
    // Daftar pola/kata kunci berbahaya (case-insensitive)
    $maliciousPatterns = [
        // Fungsi eksekusi perintah
        '/\b(system|exec|shell_exec|passthru|proc_open|popen|pcntl_exec)\s*\(/i',
        '/\b(eval|assert|create_function|preg_replace.*\/e)\s*\(/i',
        '/\b(base64_decode|str_rot13|gzuncompress|gzinflate).*\(.*\$_/i',
        // Backdoor umum
        '/\b(cmd|wget|curl|backdoor|webshell|shell|bypass|antivirus)\s*=/i',
        // Kombinasi berbahaya
        '/\$_(\w+)\s*=\s*(eval|assert|system|exec)/i',
        '/fwrite\(.*fopen\(/i',
        '/file_put_contents\(.*\$_/i',
        '/<\\?php\s*eval\(\$_/i',
        '/<\\?php\s*if\s*\(\s*isset\s*\(\s*\$\_/i', // Kondisi dengan parameter GET/POST
        '/\$\_REQUEST\[.*\]\s*=\s*eval/i'
    ];
    
    $foundFiles = [];
    
    if (!is_dir($directory)) {
        if ($outputToConsole) echo "Error: Direktori '$directory' tidak ditemukan.\n";
        return $foundFiles;
    }
    
    // Warna untuk output console
    $colors = [
        'red' => "\033[0;31m",
        'green' => "\033[0;32m",
        'yellow' => "\033[1;33m",
        'nc' => "\033[0m"
    ];
    
    if ($outputToConsole) {
        echo $colors['yellow'] . "[Scanning] " . $directory . $colors['nc'] . "\n";
    }
    
    // Iterasi rekursif dengan RecursiveDirectoryIterator
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isDir()) continue;
        
        $filePath = $file->getPathname();
        $extension = strtolower($file->getExtension());
        
        // Periksa ekstensi
        if (!in_array($extension, $extensions)) continue;
        
        // Baca isi file (batasi ukuran maks 5MB untuk performa)
        $fileSize = $file->getSize();
        if ($fileSize > 5 * 1024 * 1024) {
            if ($outputToConsole) echo "  [SKIP] $filePath (ukuran >5MB)\n";
            continue;
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) continue;
        
        $isMalicious = false;
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $isMalicious = true;
                break;
            }
        }
        
        if ($isMalicious) {
            $foundFiles[] = $filePath;
            if ($outputToConsole) {
                echo $colors['red'] . "[BACKDOOR] " . $filePath . $colors['nc'] . "\n";
            }
        }
    }
    
    if ($outputToConsole) {
        if (empty($foundFiles)) {
            echo $colors['green'] . "[Hasil] Tidak ditemukan backdoor shell." . $colors['nc'] . "\n";
        } else {
            echo $colors['green'] . "[Hasil] Ditemukan " . count($foundFiles) . " file mencurigakan." . $colors['nc'] . "\n";
        }
    }
    
    return $foundFiles;
}

// Contoh penggunaan (bisa dihapus jika hanya dijadikan library)
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    global $argv;
    if ($argc < 2) {
        echo "Usage: php " . $argv[0] . " <direktori>\n";
        exit(1);
    }
    scanBackdoorShell($argv[1], true);
}
?>