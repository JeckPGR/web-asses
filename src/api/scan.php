<?php
// Strict error reporting for development
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
set_time_limit(600);
// Increase execution time limit for long-running scans 

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
        throw new Exception("Hanya terbuka untuk post method");
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
    // $nmapCommand = "nmap --script vuln -oN " . escapeshellarg($nmapOutputFile) . " " . escapeshellarg($hostname); 
    // faster demo cek port 80 & 443 lalu --open cuma nampilin port yang kebuka saja
    $nmapCommand = "nmap -T4 -p 80,443 --open --script vuln -oN " . escapeshellarg($nmapOutputFile) . " " . escapeshellarg($hostname);


    safeLog("Executing Nmap Command: $nmapCommand");

 
     // Cek Runtime
     $startTime = time();
     $nmapOutput = shell_exec($nmapCommand . " 2>&1");
     $endTime = time();
    
    if ($nmapOutput === null) {
        throw new Exception("Nmap execution failed");
    }

 
    
    safeLog("Nmap Output: " . substr($nmapOutput, 0, 1000) . "...");  // Log only first 1000 characters

     
   
       safeLog("Nmap Execution Time: " . ($endTime - $startTime) . " seconds");

    if (!file_exists($nmapOutputFile)) {
        throw new Exception("Nmap report not generated");
    }

    $nmapReport = file_get_contents($nmapOutputFile);
    safeLog("Nmap report generated successfully.");

    // Nikto scan
    $niktoPath = 'C:\\nikto\\nikto\\program\\nikto.pl';
    $niktoOutputFile = $outputDir . 'nikto_report.txt';
    // $niktoCommand = "perl " . escapeshellarg($niktoPath) . " -h " . escapeshellarg($url) . " -o " . escapeshellarg($niktoOutputFile);

    //new command for now idk
    $niktoCommand = "perl " . escapeshellarg($niktoPath) . " -h " . escapeshellarg($url) . " -Tuning 123 -Cgidirs none -o " . escapeshellarg($niktoOutputFile);

    
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

    $vulnerabilityAnalysis = analyzeReports($nmapReport, $niktoReport);
    $seriousVulnerabilities = $vulnerabilityAnalysis['seriousVulnerabilities'];
    $recommendations = $vulnerabilityAnalysis['recommendations'];

    // Prepare the message for serious vulnerabilities
    $message = "Scan completed for $hostname.<br>";
    if (count($seriousVulnerabilities) > 0) {
        $message .= "<p><strong>Serious Vulnerabilities:</strong></p><ul>";
        foreach ($seriousVulnerabilities as $vuln) {
            $message .= "<li>" . htmlspecialchars($vuln) . "</li>";
        }
        $message .= "</ul>";
    } else {
        $message .= "<p>No serious vulnerabilities found.</p>";
    }

    // Prepare the message for recommendations
    if (count($recommendations) > 0) {
        $message .= "<p><strong>Recommendations:</strong></p><ul>";
        foreach ($recommendations as $rec) {
            $message .= "<li>" . htmlspecialchars($rec) . "</li>";
        }
        $message .= "</ul>";
    } else {
        $message .= "<p>No recommendations.</p>";
    }

    sendJsonResponse('success', $message);

} catch (Exception $e) {
    safeLog("Error: " . $e->getMessage(), 'error.log');
    sendJsonResponse('error', "An error occurred: " . $e->getMessage());
}

// Function to analyze reports and return vulnerabilities
function analyzeReports($nmapReport, $niktoReport) {
    $seriousVulnerabilities = [];
    $recommendations = [];

    // Analyze Nmap report for vulnerabilities
    if (strpos($nmapReport, 'VULNERABLE') !== false) {
        $seriousVulnerabilities[] = "Potential vulnerabilities detected by Nmap:";
        $nmapLines = explode("\n", $nmapReport);
        foreach ($nmapLines as $line) {
            if (strpos($line, 'VULNERABLE') !== false) {
                $seriousVulnerabilities[] = "Nmap: " . trim($line);  // Add Nmap vulnerability line to serious vulnerabilities
            }
        }
    } else {
        $recommendations[] = "No serious vulnerabilities detected by Nmap.";
    }

    // Analyze Nikto report for vulnerabilities
    if (strpos($niktoReport, 'OSVDB') !== false) {
        $seriousVulnerabilities[] = "Potential vulnerabilities detected by Nikto:";
        $niktoLines = explode("\n", $niktoReport);
        foreach ($niktoLines as $line) {
            if (strpos($line, 'OSVDB') !== false) {
                $seriousVulnerabilities[] = "Nikto: " . trim($line);  // Add Nikto vulnerability line to serious vulnerabilities
            }
        }
    } else {
        $recommendations[] = "No serious vulnerabilities detected by Nikto.";
    }

    // Check for recommendations in the Nikto report
    $niktoLines = explode("\n", $niktoReport);
    foreach ($niktoLines as $line) {
        if (strpos($line, 'X-Frame-Options') !== false || strpos($line, 'Link header') !== false) {
            $recommendations[] = "Nikto recommendation: " . trim($line);  // Add recommendations to the recommendations list
        }
    }

    return [
        'seriousVulnerabilities' => $seriousVulnerabilities,
        'recommendations' => $recommendations,
    ];
}



?>