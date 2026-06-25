<?php
$password = "admin123";

// 1. Simulating Registration
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Generated Hash: " . $hash . "<br>";
echo "Length of hash: " . strlen($hash) . "<br><br>"; // Should be 60+ characters

// 2. Simulating Login verification
if (password_verify($password, $hash)) {
    echo "Success: Password matches perfectly!";
} else {
    echo "Failure: Password mismatch.";
}
?>