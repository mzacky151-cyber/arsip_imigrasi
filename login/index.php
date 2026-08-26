<?php

session_start();

if (
    isset($_SESSION["login"]) &&
    $_SESSION["login"] === true
) {
    header("Location: /dashboard/index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SIPA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="login-box">

    <!-- Logo -->
    <div class="logo-group">

        <img src="/assets/img/logo-imigrasi.png" class="logo">

        <img src="/assets/img/logo-jember.png" class="logo">

    </div>

    <h2>ARSIPIN</h2>

    <p>Sistem Informasi Pengelolaan Arsip</p>

    <?php
     ?>

    <form action="proses_login.php" method="POST">

        <input
            type="text"
            name="username"
            placeholder="Masukkan Username"
            autocomplete="username"
            maxlength="50"
            required>

        <input
            type="password"
            name="password"
            placeholder="Masukkan Password"
            autocomplete="current-password"
            required>

        <button type="submit">

            Login

        </button>

    </form>

    <div class="copyright">

        © 2026 Kantor Imigrasi Kelas I TPI Jember

    </div>

</div>

</body>
</html>
