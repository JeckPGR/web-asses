<?php
// Strict error reporting for development
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Increase execution time limit for long-running scans
set_time_limit(300);

// Function to safely log messages
function safeLog($message, $logFile = 'debug.log') {
    $logDir = __DIR__ . '/../../logs/';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message . "\n", 3, $logDir . $logFile);
}

// Function to send JSON response
function sendJsonResponse($status, $message) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Main logic wrapped in a try-catch block
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed!");
    }

    safeLog("Request received: POST method");

    $url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
    if (!$url) {
        throw new Exception("No URL provided");
    }

    // Add scheme if missing
    if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
        $url = "https://" . $url;
    }

    safeLog("Starting scan...");

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception("Invalid URL format");
    }

    safeLog("Valid URL: $url");

    $parsedUrl = parse_url($url);
    $hostname = $parsedUrl['host'];

    // Use system temp directory and generate unique filename
    $outputDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vulnerability_scan_' . uniqid() . DIRECTORY_SEPARATOR;
    if (!mkdir($outputDir, 0755, true)) {
        throw new Exception("Failed to create output directory");
    }

    safeLog("Output directory created: $outputDir");

    // Nmap scan
    $nmapOutputFile = $outputDir . 'nmap_report.txt';
    $nmapCommand = "nmap --script vuln -oN " . escapeshellarg($nmapOutputFile) . " " . escapeshellarg($hostname);
    
    safeLog("Executing Nmap Command: $nmapCommand");
    $nmapOutput = shell_exec($nmapCommand . " 2>&1");
    
    if ($nmapOutput === null) {
        throw new Exception("Nmap execution failed");
    }
    
    safeLog("Nmap Output: " . substr($nmapOutput, 0, 1000) . "...");  // Log only first 1000 characters

    if (!file_exists($nmapOutputFile)) {
        throw new Exception("Nmap report not generated");
    }

    $nmapReport = file_get_contents($nmapOutputFile);
    safeLog("Nmap report generated successfully.");

    // Nikto scan
    $niktoPath = 'C:\\Users\\asus\\Downloads\\nikto\\nikto\\program\\nikto.pl';
    $niktoOutputFile = $outputDir . 'nikto_report.txt';
    $niktoCommand = "perl " . escapeshellarg($niktoPath) . " -h " . escapeshellarg($url) . " -o " . escapeshellarg($niktoOutputFile);
    
    safeLog("Executing Nikto Command: $niktoCommand");
    $niktoOutput = shell_exec($niktoCommand . " 2>&1");
    
    if ($niktoOutput === null) {
        throw new Exception("Nikto execution failed");
    }
    
    safeLog("Nikto Output: " . substr($niktoOutput, 0, 1000) . "...");  // Log only first 1000 characters

    if (!file_exists($niktoOutputFile)) {
        throw new Exception("Nikto report not generated");
    }

    $niktoReport = file_get_contents($niktoOutputFile);
    safeLog("Nikto report generated successfully.");

    // Process and analyze the reports here
    // This is a placeholder for actual vulnerability analysis
    $vulnerabilities = analyzeReports($nmapReport, $niktoReport);

    // Clean up temporary files
    unlink($nmapOutputFile);
    unlink($niktoOutputFile);
    rmdir($outputDir);

    sendJsonResponse('success', "Scan completed for $hostname. " . count($vulnerabilities) . " potential vulnerabilities found.");

} catch (Exception $e) {
    safeLog("Error: " . $e->getMessage(), 'error.log');
    sendJsonResponse('error', "An error occurred: " . $e->getMessage());
}

// Function to analyze reports and return vulnerabilities
function analyzeReports($nmapReport, $niktoReport) {
    $vulnerabilities = [];

    // Placeholder for Nmap report analysis
    if (strpos($nmapReport, 'VULNERABLE') !== false) {
        $vulnerabilities[] = "Potential vulnerabilities detected by Nmap";
    }

    // Placeholder for Nikto report analysis
    if (strpos($niktoReport, 'OSVDB') !== false) {
        $vulnerabilities[] = "Potential vulnerabilities detected by Nikto";
    }

    // Add more sophisticated analysis here

    return $vulnerabilities;
}
?>