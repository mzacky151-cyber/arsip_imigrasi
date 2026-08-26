<?php

include "../config/koneksi.php";
include "../template/session.php";
wajibLevel(["admin"]);
include "../template/header.php";
include "../template/sidebar.php";

/* ==========================
   AMBIL DAFTAR RAK
========================== */

$semuaRak = [];
$queryRak = mysqli_query($koneksi, "
    SELECT *
    FROM rak
    ORDER BY nomor_rak ASC
");

/* =========================================================
   DAFTAR RAK
========================================================= */

$daftarRak = [];

$queryRak = mysqli_query(
    $koneksi,
    "
    SELECT
        id_rak,
        nomor_rak,
        jenis,
        status
    FROM rak
    ORDER BY nomor_rak ASC
    "
);

if (!$queryRak) {
    die("Gagal memuat daftar rak: " . mysqli_error($koneksi));
}

while ($rak = mysqli_fetch_assoc($queryRak)) {
    $daftarRak[] = $rak;
}

$totalRak = count($daftarRak);

/* =========================================================
   RAK TERPILIH
========================================================= */

$rakDipilih = filter_input(
    INPUT_GET,
    "rak",
    FILTER_VALIDATE_INT
);

$indexRakAktif = 0;
$rakDitemukan = false;

if ($totalRak > 0) {

    foreach ($daftarRak as $index => $rak) {

        if (
            $rakDipilih &&
            (int) $rak["nomor_rak"] === $rakDipilih
        ) {
            $indexRakAktif = $index;
            $rakDitemukan = true;
            break;
        }
    }

    if (!$rakDitemukan) {
        $indexRakAktif = 0;
        $rakDipilih = (int) $daftarRak[0]["nomor_rak"];
    }
}

/* =========================================================
   FILTER RAK, MAKSIMAL 5 TOMBOL
========================================================= */

$jumlahTombolRak = 5;

$totalGrupRak = max(
    1,
    (int) ceil($totalRak / $jumlahTombolRak)
);

$grupRakAktif = $totalRak > 0
    ? (int) floor($indexRakAktif / $jumlahTombolRak) + 1
    : 1;

$offsetRak = ($grupRakAktif - 1) * $jumlahTombolRak;

$rakDalamGrup = array_slice(
    $daftarRak,
    $offsetRak,
    $jumlahTombolRak
);

$rakSebelumnya = null;
$rakBerikutnya = null;

if ($grupRakAktif > 1) {

    $indexSebelumnya =
        ($grupRakAktif - 2) * $jumlahTombolRak;

    if (isset($daftarRak[$indexSebelumnya])) {
        $rakSebelumnya =
            (int) $daftarRak[$indexSebelumnya]["nomor_rak"];
    }
}

if ($grupRakAktif < $totalGrupRak) {

    $indexBerikutnya =
        $grupRakAktif * $jumlahTombolRak;

    if (isset($daftarRak[$indexBerikutnya])) {
        $rakBerikutnya =
            (int) $daftarRak[$indexBerikutnya]["nomor_rak"];
    }
}

$informasiRak = null;

if ($totalRak > 0 && isset($daftarRak[$indexRakAktif])) {
    $informasiRak = $daftarRak[$indexRakAktif];
}

/* =========================================================
   DATA LOKASI RAK TERPILIH
========================================================= */

$daftarLokasi = [];

$totalLokasi = 0;
$totalKapasitas = 0;
$totalTerisi = 0;

if ($informasiRak !== null) {

    $stmt = mysqli_prepare(
        $koneksi,
        "
        SELECT
            lokasi_rak.*,
            rak.nomor_rak,
            rak.jenis,
            (
                SELECT COUNT(*)
                FROM arsip
                WHERE arsip.id_lokasi = lokasi_rak.id_lokasi
            ) AS kapasitas_terisi_aktual
        FROM lokasi_rak
        JOIN rak
            ON lokasi_rak.id_rak = rak.id_rak
        WHERE rak.nomor_rak = ?
        ORDER BY
            lokasi_rak.baris ASC,
            lokasi_rak.kolom ASC
        "
    );

    if (!$stmt) {
        die("Gagal menyiapkan data lokasi: " . mysqli_error($koneksi));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $rakDipilih
    );

    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);

    while ($lokasi = mysqli_fetch_assoc($hasil)) {

        $lokasi["kapasitas"] =
            (int) $lokasi["kapasitas"];

        $lokasi["kapasitas_terisi_aktual"] =
            (int) $lokasi["kapasitas_terisi_aktual"];

        $daftarLokasi[] = $lokasi;

        $totalLokasi++;
        $totalKapasitas += $lokasi["kapasitas"];
        $totalTerisi += $lokasi["kapasitas_terisi_aktual"];
    }

    mysqli_stmt_close($stmt);
}

$totalSisa = max(
    0,
    $totalKapasitas - $totalTerisi
);

$persentaseRak = $totalKapasitas > 0
    ? round(($totalTerisi / $totalKapasitas) * 100)
    : 0;

$persentaseRak = min(
    100,
    max(0, $persentaseRak)
);
?>

<div class="main master-modern">

<?php include "../template/topbar.php"; ?>

    <div class="content">

        <section class="master-hero">

            <div class="master-hero-copy">

                <span class="master-eyebrow">
                    Pengaturan Penyimpanan
                </span>

                <h1>Master Lokasi Rak</h1>

                <p>
                    Kelola lokasi, kapasitas, dan rentang penyimpanan
                    arsip pada setiap rak.
                </p>

            </div>

            <div class="master-hero-actions">

                <?php if ($informasiRak !== null) : ?>

                    <div class="master-selected-rack">

                        <span class="master-selected-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8">
                                <path d="M4 4h16v16H4z"></path>
                                <path d="M4 10h16M10 4v16"></path>
                            </svg>

                        </span>

                        <div>
                            <small>Rak terpilih</small>
                            <strong>
                                Rak <?= htmlspecialchars(
                                    $informasiRak["nomor_rak"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>
                                ·
                                <?= htmlspecialchars(
                                    $informasiRak["jenis"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>
                            </strong>
                        </div>

                    </div>

                <?php endif; ?>

                <a
                    href="tambah.php"
                    class="master-add-button">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>

                    Tambah Lokasi
                </a>

            </div>

        </section>

        <?php if ($totalRak > 0) : ?>

            <section class="master-rack-filter">

                <div class="master-filter-title">

                    <span class="master-filter-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8">
                            <path d="M4 5h16l-6 7v5l-4 2v-7z"></path>
                        </svg>

                    </span>

                    <div>
                        <h2>Pilih Nomor Rak</h2>
                        <p>
                            Tabel hanya menampilkan lokasi pada rak
                            yang sedang dipilih.
                        </p>
                    </div>

                </div>

                <div class="master-rack-buttons">

                    <?php if ($rakSebelumnya !== null) : ?>

                        <a
                            href="index.php?<?= http_build_query([
                                "rak" => $rakSebelumnya
                            ]); ?>"
                            class="master-rack-button arrow">
                            ‹
                        </a>

                    <?php else : ?>

                        <span class="master-rack-button arrow disabled">
                            ‹
                        </span>

                    <?php endif; ?>

                    <?php foreach ($rakDalamGrup as $rak) : ?>

                        <?php
                        $nomorRak = (int) $rak["nomor_rak"];
                        $aktif = $nomorRak === (int) $rakDipilih;
                        ?>

                        <a
                            href="index.php?<?= http_build_query([
                                "rak" => $nomorRak
                            ]); ?>"
                            class="master-rack-button <?= $aktif
                                ? "active"
                                : ""; ?>">
                            <?= $nomorRak; ?>
                        </a>

                    <?php endforeach; ?>

                    <?php if ($rakBerikutnya !== null) : ?>

                        <a
                            href="index.php?<?= http_build_query([
                                "rak" => $rakBerikutnya
                            ]); ?>"
                            class="master-rack-button arrow">
                            ›
                        </a>

                    <?php else : ?>

                        <span class="master-rack-button arrow disabled">
                            ›
                        </span>

                    <?php endif; ?>

                </div>

            </section>

            <section class="master-stat-grid">

                <article class="master-stat-card location">

                    <span class="master-stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8">
                            <path d="M4 4h16v16H4z"></path>
                            <path d="M4 10h16M10 4v16"></path>
                        </svg>

                    </span>

                    <div>
                        <span>Total Lokasi</span>
                        <strong><?= $totalLokasi; ?></strong>
                        <small>
                            Lokasi pada Rak <?= (int) $rakDipilih; ?>
                        </small>
                    </div>

                </article>

                <article class="master-stat-card capacity">

                    <span class="master-stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8">
                            <path d="M4 7h16v13H4z"></path>
                            <path d="M7 7V4h10v3"></path>
                        </svg>

                    </span>

                    <div>
                        <span>Total Kapasitas</span>
                        <strong>
                            <?= number_format(
                                $totalKapasitas,
                                0,
                                ",",
                                "."
                            ); ?>
                        </strong>
                        <small>Kapasitas seluruh lokasi</small>
                    </div>

                </article>

                <article class="master-stat-card used">

                    <span class="master-stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8">
                            <path d="M5 4h14v16H5z"></path>
                            <path d="M8 8h8M8 12h8M8 16h5"></path>
                        </svg>

                    </span>

                    <div>
                        <span>Arsip Terisi</span>
                        <strong>
                            <?= number_format(
                                $totalTerisi,
                                0,
                                ",",
                                "."
                            ); ?>
                        </strong>
                        <small><?= $persentaseRak; ?>% terpakai</small>
                    </div>

                </article>

                <article class="master-stat-card remaining">

                    <span class="master-stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M8 12h8"></path>
                        </svg>

                    </span>

                    <div>
                        <span>Sisa Kapasitas</span>
                        <strong>
                            <?= number_format(
                                $totalSisa,
                                0,
                                ",",
                                "."
                            ); ?>
                        </strong>
                        <small>Ruang masih tersedia</small>
                    </div>

                </article>

            </section>

            <section class="master-table-panel">

                <div class="master-table-header">

                    <div>

                        <span class="master-eyebrow">
                            Detail Lokasi
                        </span>

                        <h2>
                            Rak <?= (int) $rakDipilih; ?>
                            ·
                            <?= htmlspecialchars(
                                $informasiRak["jenis"],
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>
                        </h2>

                        <p>
                            <?= $totalLokasi; ?> lokasi terdapat pada rak ini.
                        </p>

                    </div>

                    <div class="master-capacity-summary">

                        <div class="master-capacity-label">

                            <span>Penggunaan rak</span>

                            <strong>
                                <?= $totalTerisi; ?>
                                /
                                <?= $totalKapasitas; ?>
                            </strong>

                        </div>

                        <div class="master-capacity-track">

                            <span
                                style="width:<?= $persentaseRak; ?>%;">
                            </span>

                        </div>

                    </div>

                </div>

                <div class="master-table-wrapper">

                    <table class="master-location-table">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Baris</th>
                                <th>Kolom</th>
                                <th>Rentang Tahun</th>
                                <th>Rentang Bulan</th>
                                <th>Kapasitas</th>
                                <th>Terisi</th>
                                <th>Penggunaan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (count($daftarLokasi) === 0) : ?>

                            <tr>

                                <td
                                    colspan="10"
                                    class="master-empty-state">

                                    <span class="master-empty-icon">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8">
                                            <path d="M4 4h16v16H4z"></path>
                                            <path d="M8 12h8"></path>
                                        </svg>

                                    </span>

                                    <strong>Belum ada lokasi</strong>

                                    <span>
                                        Tambahkan lokasi penyimpanan
                                        untuk rak ini.
                                    </span>

                                </td>

                            </tr>

                        <?php else : ?>

                            <?php
                            $nomor = 1;

                            foreach ($daftarLokasi as $lokasi) :
                            ?>

                                <?php
                                $kapasitas =
                                    (int) $lokasi["kapasitas"];

                                $terisi =
                                    (int) $lokasi["kapasitas_terisi_aktual"];

                                $persentase = $kapasitas > 0
                                    ? round(($terisi / $kapasitas) * 100)
                                    : 0;

                                $persentase = min(
                                    100,
                                    max(0, $persentase)
                                );

                                if ($persentase >= 100) {
                                    $kelasPenggunaan = "full";
                                } elseif ($persentase >= 71) {
                                    $kelasPenggunaan = "high";
                                } elseif ($persentase > 0) {
                                    $kelasPenggunaan = "partial";
                                } else {
                                    $kelasPenggunaan = "empty";
                                }

                                $kelasStatus =
                                    strtolower($lokasi["status"]) === "aktif"
                                        ? "active"
                                        : "inactive";
                                ?>

                                <tr>

                                    <td><?= $nomor++; ?></td>

                                    <td>
                                        <span class="master-location-code">
                                            B<?= (int) $lokasi["baris"]; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="master-location-code">
                                            K<?= (int) $lokasi["kolom"]; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $lokasi["tahun_awal"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>
                                        -
                                        <?= htmlspecialchars(
                                            $lokasi["tahun_akhir"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $lokasi["bulan_awal"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>
                                        -
                                        <?= htmlspecialchars(
                                            $lokasi["bulan_akhir"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $kapasitas,
                                            0,
                                            ",",
                                            "."
                                        ); ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $terisi,
                                            0,
                                            ",",
                                            "."
                                        ); ?>
                                    </td>

                                    <td>

                                        <div class="master-row-usage">

                                            <div class="master-row-progress">

                                                <span
                                                    class="<?= $kelasPenggunaan; ?>"
                                                    style="width:<?= $persentase; ?>%;">
                                                </span>

                                            </div>

                                            <small>
                                                <?= $persentase; ?>%
                                            </small>

                                        </div>

                                    </td>

                                    <td>

                                        <span
                                            class="master-status-badge <?= $kelasStatus; ?>">

                                            <?= htmlspecialchars(
                                                $lokasi["status"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>

                                        </span>

                                    </td>

                                    <td>

                                        <div class="master-action-group">

                                            <a
                                                href="edit.php?id=<?= (int) $lokasi["id_lokasi"]; ?>"
                                                class="master-action-button edit"
                                                title="Edit lokasi">

                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2">
                                                    <path d="M4 20h4l10-10-4-4L4 16v4z"></path>
                                                    <path d="m13 7 4 4"></path>
                                                </svg>

                                            </a>

                                            <button
                                                type="button"
                                                class="master-action-button delete btn-hapus-lokasi"
                                                title="Hapus lokasi"
                                                data-id="<?= (int) $lokasi["id_lokasi"]; ?>"
                                                data-rak="<?= htmlspecialchars(
                                                    $lokasi["nomor_rak"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ); ?>"
                                                data-baris="<?= (int) $lokasi["baris"]; ?>"
                                                data-kolom="<?= (int) $lokasi["kolom"]; ?>">

                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2">
                                                    <path d="M4 7h16"></path>
                                                    <path d="M9 7V4h6v3"></path>
                                                    <path d="m6 7 1 13h10l1-13"></path>
                                                    <path d="M10 11v5M14 11v5"></path>
                                                </svg>

                                            </button>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        <?php else : ?>

            <section class="master-no-rack">

                <span class="master-no-rack-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8">
                        <path d="M4 4h16v16H4z"></path>
                        <path d="M8 12h8"></path>
                    </svg>

                </span>

                <h2>Data rak belum tersedia</h2>

                <p>
                    Tambahkan data rak terlebih dahulu sebelum
                    membuat lokasi penyimpanan.
                </p>

            </section>

        <?php endif; ?>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document
    .querySelectorAll(".btn-hapus-lokasi")
    .forEach(function (button) {

        button.addEventListener("click", function () {

            const id = this.dataset.id;
            const rak = this.dataset.rak;
            const baris = this.dataset.baris;
            const kolom = this.dataset.kolom;

            Swal.fire({

                icon: "warning",
                title: "Hapus Lokasi Rak?",
                width: 430,

                html: `
                    <p style="margin-bottom:16px;">
                        Lokasi berikut akan dihapus.
                    </p>

                    <div style="
                        background:#F8FAFC;
                        border:1px solid #E5E7EB;
                        border-radius:12px;
                        padding:16px;
                        text-align:left;
                        line-height:2;
                    ">
                        <b>Rak</b> : ${rak}<br>
                        <b>Baris</b> : ${baris}<br>
                        <b>Kolom</b> : ${kolom}
                    </div>
                `,

                showCancelButton: true,
                confirmButtonText: "Ya, Hapus",
                cancelButtonText: "Batal",
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d",
                reverseButtons: true,
                allowOutsideClick: false,

                customClass: {
                    popup: "rounded-popup",
                    title: "popup-title",
                    confirmButton: "popup-button"
                }

            }).then(function (result) {

                if (result.isConfirmed) {
                    window.location =
                        "hapus.php?id=" +
                        encodeURIComponent(id);
                }

            });

        });

    });
</script>

<?php include "../template/footer.php"; ?>