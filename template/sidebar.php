<?php
$halaman = basename($_SERVER['PHP_SELF']);
$folder = basename(dirname($_SERVER['PHP_SELF']));
?>

<div class="sidebar">

    <div class="sidebar-header">

        <img src="/assets/img/logo-imigrasi.png" alt="Logo">

        <h2>ARSIPIN</h2>

        <p>Sistem Informasi<br>Pengelolaan Arsip</p>

    </div>

    <div class="sidebar-menu">

        <a href="/dashboard/index.php"
            class="<?= ($folder=="dashboard") ? "active" : ""; ?>">
            Dashboard
            </a>
       
            <a href="/data_arsip/index.php"
            class="<?= ($folder=="data_arsip") ? "active" : ""; ?>">
            Data Arsip
            </a>

            <?php if (punyaAkses(["admin"])) : ?>
            <a href="/master_lokasi/index.php"
            class="<?= ($folder=="master_lokasi") ? "active" : ""; ?>">
            Master Lokasi Rak
            </a>
            <?php endif; ?>

        
            <a href="/visualisasi_rak/index.php"
            class="<?= ($folder=="visualisasi_rak") ? "active" : ""; ?>">
            Visualisasi Rak
            </a>
        
            <a href="/peminjaman/index.php"
            class="<?= ($folder=="peminjaman") ? "active" : ""; ?>">
            Peminjaman
            </a>
            
            <?php if (punyaAkses(["admin"])) : ?>
            <a href="/user/index.php"
            class="<?= $folder=="user" ? "active" : ""; ?>">
            Manajemen User
            </a>
            <?php endif; ?>
            
            <?php if (punyaAkses(["admin"])) : ?>
            <a href="/laporan/index.php"
            class="<?= ($folder=="laporan") ? "active" : ""; ?>">
            Laporan
            </a>
            <?php endif; ?>
       
            <a href="/logout/logout.php">
            Logout
            </a>

    </div>

</div>
