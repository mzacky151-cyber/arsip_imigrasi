<?php

session_start();

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $parameterCookie = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $parameterCookie["path"],
        $parameterCookie["domain"],
        $parameterCookie["secure"],
        $parameterCookie["httponly"]
    );
}

session_destroy();

header("Location: ../login/index.php");
exit;