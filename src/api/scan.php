<?php

// PHP settings to suppress error display
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("[" . date('Y-m-d H:i:s') . "] Request received: POST method", 3, __DIR__ . '/../../logs/debug.log');

    $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);

    // Tambahkan "https://" jika tidak ada skema
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = 'https://' . $url;
    }

    // Validasi URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        error_log("[" . date('Y-m-d H:i:s') . "] Invalid URL provided: $url", 3, __DIR__ . '/../../logs/debug.log');
        echo json_encode(['status' => 'error', 'message' => "Invalid URL! Please ensure your URL is in the correct format, e.g., example.com or https://example.com."]);
        exit;
    }

    error_log("[" . date('Y-m-d H:i:s') . "] Valid URL: $url", 3, __DIR__ . '/../../logs/debug.log');

    // Mengambil hostname dari URL
    $parsed_url = parse_url($url);
    $hostname = $parsed_url['host'];

    // Tentukan direktori output untuk laporan Nmap dan Nikto
    $output_dir = 'C:\\tmp\\';
    if (!file_exists($output_dir)) {
        if (!mkdir($output_dir, 0777, true)) {
            error_log("[" . date('Y-m-d H:i:s') . "] Failed to create output directory: $output_dir", 3, __DIR__ . '/../../logs/debug.log');
            echo json_encode(['status' => 'error', 'message' => "Failed to create output directory."]);
            exit;
        }
        error_log("[" . date('Y-m-d H:i:s') . "] Output directory created: $output_dir", 3, __DIR__ . '/../../logs/debug.log');
    }

    // Jalankan Nmap untuk memindai kerentanan
    $nmap_output_file = $output_dir . 'nmap_report.txt';
    $nmap_command = "nmap --script vuln -oN " . escapeshellarg($nmap_output_file) . " " . escapeshellarg($hostname);
    error_log("[" . date('Y-m-d H:i:s') . "] Executing Nmap Command: $nmap_command", 3, __DIR__ . '/../../logs/debug.log');
    $nmap_output = shell_exec($nmap_command . " 2>&1");
    error_log("[" . date('Y-m-d H:i:s') . "] Nmap Output: $nmap_output", 3, __DIR__ . '/../../logs/debug.log');

    // Periksa apakah file hasil Nmap ada
    if (file_exists($nmap_output_file)) {
        $nmap_report = file_get_contents($nmap_output_file);
        error_log("[" . date('Y-m-d H:i:s') . "] Nmap report generated successfully.", 3, __DIR__ . '/../../logs/debug.log');
        error_log("[" . date('Y-m-d H:i:s') . "] Nmap Report Content: $nmap_report", 3, __DIR__ . '/../../logs/debug.log');
    } else {
        $nmap_report = 'No Nmap report generated.';
        error_log("[" . date('Y-m-d H:i:s') . "] Nmap report not generated.", 3, __DIR__ . '/../../logs/debug.log');
    }

    // Tentukan path penuh untuk skrip Nikto
    $nikto_path = 'C:\\Users\\asus\\Downloads\\nikto\\nikto\\program\\nikto.pl';
    $nikto_output_file = $output_dir . 'nikto_report.txt';
    $nikto_command = "perl " . escapeshellarg($nikto_path) . " -h " . escapeshellarg($url) . " -o " . escapeshellarg($nikto_output_file);
    error_log("[" . date('Y-m-d H:i:s') . "] Executing Nikto Command: $nikto_command", 3, __DIR__ . '/../../logs/debug.log');
    $nikto_output = shell_exec($nikto_command . " 2>&1");
    error_log("[" . date('Y-m-d H:i:s') . "] Nikto Output: $nikto_output", 3, __DIR__ . '/../../logs/debug.log');

    // Periksa apakah file hasil Nikto ada
    if (file_exists($nikto_output_file)) {
        $nikto_report = file_get_contents($nikto_output_file);
        error_log("[" . date('Y-m-d H:i:s') . "] Nikto report generated successfully.", 3, __DIR__ . '/../../logs/debug.log');
        error_log("[" . date('Y-m-d H:i:s') . "] Nikto Report Content: $nikto_report", 3, __DIR__ . '/../../logs/debug.log');
    } else {
        $nikto_report = 'No Nikto report generated.';
        error_log("[" . date('Y-m-d H:i:s') . "] Nikto report not generated.", 3, __DIR__ . '/../../logs/debug.log');
    }

    // Kirim pesan ringkas ke klien dan log detail lengkap
    echo json_encode([
        'status' => 'success',
        'message' => "Vulnerabilities found for $hostname."
    ]);
} else {
    error_log("[" . date('Y-m-d H:i:s') . "] Invalid request method: " . $_SERVER['REQUEST_METHOD'], 3, __DIR__ . '/../../logs/debug.log');
    echo json_encode(['status' => 'error', 'message' => "Method not allowed!"]);
}
?>
