<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_arsip_imigrasi";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Terjadi gangguan koneksi database.");
}

mysqli_set_charset($koneksi, "utf8mb4");

date_default_timezone_set("Asia/Jakarta");