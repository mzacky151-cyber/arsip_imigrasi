<?php

$namaPengguna = $_SESSION["nama"] ?? "Pengguna";
$levelPengguna = $_SESSION["level"] ?? "petugas";

$namaLevel = strtolower($levelPengguna) === "admin"
    ? "Administrator"
    : "Petugas Arsip";

$inisialPengguna = strtoupper(
    substr(
        trim($namaPengguna),
        0,
        1
    )
);

$namaHari = [
    "Sunday"   => "Minggu",
    "Monday"   => "Senin",
    "Tuesday"  => "Selasa",
    "Wednesday"=> "Rabu",
    "Thursday" => "Kamis",
    "Friday"   => "Jumat",
    "Saturday" => "Sabtu"
];

$namaBulan = [
    1  => "Januari",
    2  => "Februari",
    3  => "Maret",
    4  => "April",
    5  => "Mei",
    6  => "Juni",
    7  => "Juli",
    8  => "Agustus",
    9  => "September",
    10 => "Oktober",
    11 => "November",
    12 => "Desember"
];

$hariSekarang = $namaHari[date("l")] ?? date("l");

$tanggalSekarang =
    $hariSekarang .
    ", " .
    date("d") .
    " " .
    $namaBulan[(int) date("n")] .
    " " .
    date("Y");
?>

<header class="topbar topbar-modern">

    <div class="topbar-brand">

        <div class="topbar-brand-logo">

            <img
                src="../assets/img/logo-imigrasi.png"
                alt="Logo Imigrasi">

        </div>

        <div class="topbar-brand-divider"></div>

        <div class="topbar-brand-text">

            <h2>ARSIPIN</h2>

            <p>
                Sistem Informasi Pengelolaan Arsip
            </p>

        </div>

    </div>

    <div class="topbar-right">

        <div class="topbar-date">

            <div class="topbar-date-icon">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    aria-hidden="true">

                    <rect
                        x="3"
                        y="5"
                        width="18"
                        height="16"
                        rx="2">
                    </rect>

                    <path d="M8 3v4"></path>
                    <path d="M16 3v4"></path>
                    <path d="M3 10h18"></path>

                </svg>

            </div>

            <div class="topbar-date-text">

                <small>Hari ini</small>

                <strong>
                    <?= htmlspecialchars(
                        $tanggalSekarang,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>
                </strong>

            </div>

        </div>

        <div class="topbar-user-separator"></div>

        <div class="topbar-user-profile">

            <div class="topbar-user-avatar">

                <?= htmlspecialchars(
                    $inisialPengguna,
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>

            </div>

            <div class="topbar-user-info">

                <small>Selamat datang,</small>

                <strong>
                    <?= htmlspecialchars(
                        $namaPengguna,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>
                </strong>

                <span>
                    <?= htmlspecialchars(
                        $namaLevel,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>
                </span>

            </div>

        </div>

    </div>

</header>