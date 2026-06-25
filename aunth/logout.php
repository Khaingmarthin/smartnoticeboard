<?php

// 1. Start the session context to find out who is logged in
session_start();

// 2. Unset all session variables
$_SESSION = array();

// 3. Completely destroy the session cookie in the user's browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Destroy the session on the server side
session_destroy();

// 5. Redirect cleanly back to your login page
header("Location: ../aunth/login.php");
exit();
?>