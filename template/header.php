<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>ARSIPIN</title>

<?php
$lokasiCss = dirname(__DIR__) . "/assets/css/style.css";
$versiCss = file_exists($lokasiCss)
    ? filemtime($lokasiCss)
    : time();
?>

<link
    rel="stylesheet"
    href="/ARSIP_IMIGRASI/assets/css/style.css?v=<?= $versiCss; ?>">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>

<body>

<div class="wrapper">