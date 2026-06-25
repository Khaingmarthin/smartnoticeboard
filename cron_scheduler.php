<?php
// cron_scheduler.php
$secretKey = "SmartUni2026_Secret!"; // Change this to a random string

// Check if the script is run from the command line (Cron) OR has the right password
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== $secretKey)) {
    die("Unauthorized access.");
}

if (php_sapi_name() !== 'cli') {
    die("Unauthorized access. This script can only be run by the server.");
}
date_default_timezone_set('Asia/Yangon');

// 1. Database Connection
$host = 'localhost';
$dbname = 'noticeboard_db';
$username = 'root'; 
$password = 'km@24y8*'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 2. Publish scheduled drafts
$publishQuery = "UPDATE notices 
                 SET status = 'published' 
                 WHERE status = 'draft' 
                 AND publish_date IS NOT NULL 
                 AND publish_date <= NOW()";
$publishStmt = $pdo->query($publishQuery);
$publishedCount = $publishStmt->rowCount();

// 3. Expire old notices
$expireQuery = "UPDATE notices 
                SET status = 'expired' 
                WHERE status = 'published' 
                AND expire_date IS NOT NULL 
                AND expire_date <= NOW()";
$expireStmt = $pdo->query($expireQuery);
$expiredCount = $expireStmt->rowCount();

// Optional: Log the activity so you know the cron is working
echo "Scheduled tasks run at " . date('Y-m-d H:i:s') . "\n";
echo "Published: $publishedCount | Expired: $expiredCount\n";
?>