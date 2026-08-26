<?php

$host = "mysql.railway.internal";
$user = "root";
$pass = "bkOKHujYKUtadHxHOREYDzCoclQRjcRE";
$db = "railway";
$port = 3306;

$koneksi = mysqli_connect(
    $host,
    $user,
    $pass,
    $db,
    $port
);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, "utf8mb4");

date_default_timezone_set("Asia/Jakarta");
