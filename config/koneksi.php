<?php

$host = getenv("MYSQLHOST");
$user = getenv("MYSQLUSER");
$pass = getenv("MYSQLPASSWORD");
$db   = getenv("MYSQLDATABASE");
$port = getenv("MYSQLPORT");

$koneksi = mysqli_connect(
    $host,
    $user,
    $pass,
    $db,
    $port
);

if (!$koneksi) {
    die("Terjadi gangguan koneksi database.");
}

mysqli_set_charset($koneksi, "utf8mb4");

date_default_timezone_set("Asia/Jakarta");
