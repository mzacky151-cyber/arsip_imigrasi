<?php

include "../config/koneksi.php";
include "../template/session.php";

wajibLevel(["admin"]);

include "../template/header.php";
include "../template/sidebar.php";

/*
|--------------------------------------------------------------------------
| Jenis laporan dan filter
|--------------------------------------------------------------------------
*/

$jenis = $_GET["jenis"] ?? "arsip";

if (!in_array($jenis, ["arsip", "peminjaman"], true)) {
    $jenis = "arsip";
}

$keyword      = trim($_GET["keyword"] ?? "");
$tanggalAwal  = trim($_GET["tanggal_awal"] ?? "");
$tanggalAkhir = trim($_GET["tanggal_akhir"] ?? "");
$kategori     = $_GET["kategori"] ?? "";
$status       = $_GET["status"] ?? "";

if (!in_array($kategori, ["", "WNI", "WNA"], true)) {
    $kategori = "";
}

$statusArsipDiizinkan = [
    "",
    "Tersedia",
    "Dipinjam"
];

$statusPinjamDiizinkan = [
    "",
    "Dipinjam",
    "Dikembalikan"
];

if ($jenis === "arsip") {

    if (!in_array($status, $statusArsipDiizinkan, true)) {
        $status = "";
    }

} else {

    if (!in_array($status, $statusPinjamDiizinkan, true)) {
        $status = "";
    }
}

/*
|--------------------------------------------------------------------------
| Validasi tanggal
|--------------------------------------------------------------------------
*/

function tanggalValid(string $tanggal): bool
{
    if ($tanggal === "") {
        return true;
    }

    $objekTanggal = DateTime::createFromFormat(
        "Y-m-d",
        $tanggal
    );

    return $objekTanggal !== false
        && $objekTanggal->format("Y-m-d") === $tanggal;
}

if (!tanggalValid($tanggalAwal)) {
    $tanggalAwal = "";
}

if (!tanggalValid($tanggalAkhir)) {
    $tanggalAkhir = "";
}

/*
|--------------------------------------------------------------------------
| Escape nilai filter
|--------------------------------------------------------------------------
*/

$keywordSql   = mysqli_real_escape_string($koneksi, $keyword);
$awalSql      = mysqli_real_escape_string($koneksi, $tanggalAwal);
$akhirSql     = mysqli_real_escape_string($koneksi, $tanggalAkhir);
$kategoriSql  = mysqli_real_escape_string($koneksi, $kategori);
$statusSql    = mysqli_real_escape_string($koneksi, $status);

/*
|--------------------------------------------------------------------------
| Statistik umum
|--------------------------------------------------------------------------
*/

$totalSeluruhArsip = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM arsip
        "
    )
);

$totalArsipTersedia = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM arsip
        WHERE status = 'Tersedia'
        "
    )
);

$totalPeminjaman = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM peminjaman
        "
    )
);

$totalPeminjamanAktif = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM peminjaman
        WHERE status = 'Dipinjam'
        "
    )
);

$totalDikembalikan = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM peminjaman
        WHERE status = 'Dikembalikan'
        "
    )
);

/*
|--------------------------------------------------------------------------
| Query laporan
|--------------------------------------------------------------------------
*/

if ($jenis === "arsip") {

    $sql = "
        SELECT
            arsip.*,
            lokasi_rak.baris,
            lokasi_rak.kolom,
            rak.nomor_rak
        FROM arsip
        JOIN lokasi_rak
            ON arsip.id_lokasi = lokasi_rak.id_lokasi
        JOIN rak
            ON lokasi_rak.id_rak = rak.id_rak
        WHERE 1 = 1
    ";

    if ($keyword !== "") {
        $sql .= "
            AND (
                arsip.nama LIKE '%$keywordSql%'
                OR arsip.nomor_permohonan LIKE '%$keywordSql%'
            )
        ";
    }

    if ($tanggalAwal !== "") {
        $sql .= "
            AND arsip.tanggal_arsip >= '$awalSql'
        ";
    }

    if ($tanggalAkhir !== "") {
        $sql .= "
            AND arsip.tanggal_arsip <= '$akhirSql'
        ";
    }

    if ($kategori !== "") {
        $sql .= "
            AND arsip.kewarganegaraan = '$kategoriSql'
        ";
    }

    if ($status !== "") {
        $sql .= "
            AND arsip.status = '$statusSql'
        ";
    }

    $sql .= "
        ORDER BY
            arsip.tanggal_arsip DESC,
            arsip.id_arsip DESC
    ";

} else {

    $sql = "
        SELECT
            peminjaman.*,
            arsip.nama,
            arsip.nomor_permohonan,
            arsip.kewarganegaraan,
            lokasi_rak.baris,
            lokasi_rak.kolom,
            rak.nomor_rak
        FROM peminjaman
        JOIN arsip
            ON peminjaman.id_arsip = arsip.id_arsip
        JOIN lokasi_rak
            ON arsip.id_lokasi = lokasi_rak.id_lokasi
        JOIN rak
            ON lokasi_rak.id_rak = rak.id_rak
        WHERE 1 = 1
    ";

    if ($keyword !== "") {
        $sql .= "
            AND (
                arsip.nama LIKE '%$keywordSql%'
                OR arsip.nomor_permohonan LIKE '%$keywordSql%'
                OR peminjaman.nama_peminjam LIKE '%$keywordSql%'
                OR peminjaman.nip_peminjam LIKE '%$keywordSql%'
            )
        ";
    }

    if ($tanggalAwal !== "") {
        $sql .= "
            AND peminjaman.tanggal_pinjam >= '$awalSql'
        ";
    }

    if ($tanggalAkhir !== "") {
        $sql .= "
            AND peminjaman.tanggal_pinjam <= '$akhirSql'
        ";
    }

    if ($kategori !== "") {
        $sql .= "
            AND arsip.kewarganegaraan = '$kategoriSql'
        ";
    }

    if ($status !== "") {
        $sql .= "
            AND peminjaman.status = '$statusSql'
        ";
    }

    $sql .= "
        ORDER BY
            peminjaman.tanggal_pinjam DESC,
            peminjaman.id_peminjaman DESC
    ";
}

$data = mysqli_query($koneksi, $sql);

if (!$data) {
    die(
        "Gagal memuat laporan: " .
        mysqli_error($koneksi)
    );
}

$totalData = mysqli_num_rows($data);

/*
|--------------------------------------------------------------------------
| Parameter ekspor
|--------------------------------------------------------------------------
*/

$queryString = http_build_query([
    "keyword"       => $keyword,
    "tanggal_awal"  => $tanggalAwal,
    "tanggal_akhir" => $tanggalAkhir,
    "kategori"      => $kategori,
    "status"        => $status
]);

$filterAktif =
    $keyword !== ""
    || $tanggalAwal !== ""
    || $tanggalAkhir !== ""
    || $kategori !== ""
    || $status !== "";

$judulJenis = $jenis === "arsip"
    ? "Laporan Data Arsip"
    : "Laporan Peminjaman";

$deskripsiJenis = $jenis === "arsip"
    ? "Rekap arsip berdasarkan tanggal masuk, kewarganegaraan, dan status."
    : "Rekap transaksi peminjaman dan pengembalian arsip.";
?>

<div class="main laporan-modern">

<?php include "../template/topbar.php"; ?>

    <div class="content">

        <section class="laporan-hero">

            <div class="laporan-hero-copy">

                <span class="laporan-eyebrow">
                    Rekapitulasi Sistem
                </span>

                <h1>Laporan</h1>

                <p>
                    Tampilkan, cetak, dan ekspor rekap data arsip
                    maupun transaksi peminjaman.
                </p>

            </div>

            <div class="laporan-hero-actions">

                <div class="laporan-active-report">

                    <div class="laporan-active-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M5 3h10l4 4v14H5z"></path>
                            <path d="M15 3v5h5"></path>
                            <path d="M8 13h8M8 17h6"></path>
                        </svg>

                    </div>

                    <div>
                        <small>Laporan aktif</small>
                        <strong>
                            <?= $jenis === "arsip"
                                ? "Data Arsip"
                                : "Peminjaman"; ?>
                        </strong>
                    </div>

                </div>

                <button
                    type="button"
                    class="laporan-print-button"
                    onclick="window.print()">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <path d="M6 9V3h12v6"></path>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <path d="M6 14h12v7H6z"></path>
                    </svg>

                    Cetak
                </button>

                <?php if ($jenis === "arsip") : ?>

                    <a
                        href="export_arsip.php?<?= htmlspecialchars(
                            $queryString,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        class="laporan-export-button">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M12 4v12"></path>
                            <path d="m7 11 5 5 5-5"></path>
                            <path d="M5 20h14"></path>
                        </svg>

                        Ekspor CSV
                    </a>

                <?php else : ?>

                    <a
                        href="export_peminjaman.php?<?= htmlspecialchars(
                            $queryString,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        class="laporan-export-button">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M12 4v12"></path>
                            <path d="m7 11 5 5 5-5"></path>
                            <path d="M5 20h14"></path>
                        </svg>

                        Ekspor CSV
                    </a>

                <?php endif; ?>

            </div>

        </section>

        <section class="laporan-stat-grid">

            <article class="laporan-stat-card result">

                <div class="laporan-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <path d="M4 5h16l-6 7v5l-4 2v-7z"></path>
                    </svg>

                </div>

                <div>
                    <span>Hasil Laporan</span>

                    <strong>
                        <?= number_format(
                            $totalData,
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>
                        Data sesuai filter aktif
                    </small>
                </div>

            </article>

            <?php if ($jenis === "arsip") : ?>

                <article class="laporan-stat-card archive">

                    <div class="laporan-stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M4 8h16v12H4z"></path>
                            <path d="M7 8V4h10v4"></path>
                            <path d="M8 13h8"></path>
                        </svg>

                    </div>

                    <div>
                        <span>Total Arsip</span>

                        <strong>
                            <?= number_format(
                                (int) $totalSeluruhArsip["total"],
                                0,
                                ",",
                                "."
                            ); ?>
                        </strong>

                        <small>Seluruh arsip dalam sistem</small>
                    </div>

                </article>

                <article class="laporan-stat-card available">

                    <div class="laporan-stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M4 8h16v12H4z"></path>
                            <path d="m9 14 2 2 4-4"></path>
                        </svg>

                    </div>

                    <div>
                        <span>Arsip Tersedia</span>

                        <strong>
                            <?= number_format(
                                (int) $totalArsipTersedia["total"],
                                0,
                                ",",
                                "."
                            ); ?>
                        </strong>

                        <small>Arsip siap digunakan</small>
                    </div>

                </article>

                <article class="laporan-stat-card borrowed">

                    <div class="laporan-stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M5 4h11v16H5z"></path>
                            <path d="M16 8h3v12H8"></path>
                        </svg>

                    </div>

                    <div>
                        <span>Sedang Dipinjam</span>

                        <strong>
                            <?= number_format(
                                (int) $totalPeminjamanAktif["total"],
                                0,
                                ",",
                                "."
                            ); ?>
                        </strong>

                        <small>Arsip masih berada di peminjam</small>
                    </div>

                </article>

            <?php else : ?>

                <article class="laporan-stat-card transaction">

                    <div class="laporan-stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M5 4h11v16H5z"></path>
                            <path d="M16 8h3v12H8"></path>
                        </svg>

                    </div>

                    <div>
                        <span>Total Transaksi</span>

                        <strong>
                            <?= number_format(
                                (int) $totalPeminjaman["total"],
                                0,
                                ",",
                                "."
                            ); ?>
                        </strong>

                        <small>Seluruh riwayat peminjaman</small>
                    </div>

                </article>

                <article class="laporan-stat-card borrowed">

                    <div class="laporan-stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M5 4h11v16H5z"></path>
                            <path d="M16 8h3v12H8"></path>
                        </svg>

                    </div>

                    <div>
                        <span>Dipinjam Aktif</span>

                        <strong>
                            <?= number_format(
                                (int) $totalPeminjamanAktif["total"],
                                0,
                                ",",
                                "."
                            ); ?>
                        </strong>

                        <small>Belum dikembalikan</small>
                    </div>

                </article>

                <article class="laporan-stat-card returned">

                    <div class="laporan-stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M20 11a8 8 0 1 1-2.3-5.7"></path>
                            <path d="M20 4v7h-7"></path>
                        </svg>

                    </div>

                    <div>
                        <span>Dikembalikan</span>

                        <strong>
                            <?= number_format(
                                (int) $totalDikembalikan["total"],
                                0,
                                ",",
                                "."
                            ); ?>
                        </strong>

                        <small>Transaksi telah selesai</small>
                    </div>

                </article>

            <?php endif; ?>

        </section>

        <nav class="laporan-tabs" aria-label="Jenis laporan">

            <a
                href="index.php?jenis=arsip"
                class="<?= $jenis === "arsip"
                    ? "active"
                    : ""; ?>">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    aria-hidden="true">
                    <path d="M4 8h16v12H4z"></path>
                    <path d="M7 8V4h10v4"></path>
                </svg>

                Data Arsip
            </a>

            <a
                href="index.php?jenis=peminjaman"
                class="<?= $jenis === "peminjaman"
                    ? "active"
                    : ""; ?>">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    aria-hidden="true">
                    <path d="M5 4h11v16H5z"></path>
                    <path d="M16 8h3v12H8"></path>
                </svg>

                Peminjaman
            </a>

        </nav>

        <section class="laporan-filter-panel">

            <div class="laporan-panel-heading">

                <div class="laporan-panel-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <path d="M4 5h16l-6 7v5l-4 2v-7z"></path>
                    </svg>

                </div>

                <div>
                    <h2>Filter Laporan</h2>
                    <p>
                        Pilih kriteria untuk membatasi data
                        yang ditampilkan dan diekspor.
                    </p>
                </div>

            </div>

            <form method="GET">

                <input
                    type="hidden"
                    name="jenis"
                    value="<?= htmlspecialchars(
                        $jenis,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>">

                <div class="laporan-filter-grid">

                    <div class="laporan-filter-field search">

                        <label for="laporan-keyword">
                            Pencarian
                        </label>

                        <div class="laporan-search-input">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-4-4"></path>
                            </svg>

                            <input
                                id="laporan-keyword"
                                type="text"
                                name="keyword"
                                value="<?= htmlspecialchars(
                                    $keyword,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                placeholder="<?= $jenis === "arsip"
                                    ? "Nama atau nomor permohonan"
                                    : "Arsip, peminjam, NIP, atau permohonan"; ?>">

                        </div>

                    </div>

                    <div class="laporan-filter-field">

                        <label for="laporan-awal">
                            Tanggal Awal
                        </label>

                        <input
                            id="laporan-awal"
                            type="date"
                            name="tanggal_awal"
                            value="<?= htmlspecialchars(
                                $tanggalAwal,
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>">

                    </div>

                    <div class="laporan-filter-field">

                        <label for="laporan-akhir">
                            Tanggal Akhir
                        </label>

                        <input
                            id="laporan-akhir"
                            type="date"
                            name="tanggal_akhir"
                            value="<?= htmlspecialchars(
                                $tanggalAkhir,
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>">

                    </div>

                    <div class="laporan-filter-field">

                        <label for="laporan-kategori">
                            Kategori
                        </label>

                        <select
                            id="laporan-kategori"
                            name="kategori">

                            <option value="">Semua Kategori</option>

                            <option
                                value="WNI"
                                <?= $kategori === "WNI"
                                    ? "selected"
                                    : ""; ?>>
                                WNI
                            </option>

                            <option
                                value="WNA"
                                <?= $kategori === "WNA"
                                    ? "selected"
                                    : ""; ?>>
                                WNA
                            </option>

                        </select>

                    </div>

                    <div class="laporan-filter-field">

                        <label for="laporan-status">
                            Status
                        </label>

                        <select
                            id="laporan-status"
                            name="status">

                            <option value="">Semua Status</option>

                            <?php if ($jenis === "arsip") : ?>

                                <option
                                    value="Tersedia"
                                    <?= $status === "Tersedia"
                                        ? "selected"
                                        : ""; ?>>
                                    Tersedia
                                </option>

                                <option
                                    value="Dipinjam"
                                    <?= $status === "Dipinjam"
                                        ? "selected"
                                        : ""; ?>>
                                    Dipinjam
                                </option>

                            <?php else : ?>

                                <option
                                    value="Dipinjam"
                                    <?= $status === "Dipinjam"
                                        ? "selected"
                                        : ""; ?>>
                                    Dipinjam
                                </option>

                                <option
                                    value="Dikembalikan"
                                    <?= $status === "Dikembalikan"
                                        ? "selected"
                                        : ""; ?>>
                                    Dikembalikan
                                </option>

                            <?php endif; ?>

                        </select>

                    </div>

                </div>

                <div class="laporan-filter-actions">

                    <button
                        type="submit"
                        class="laporan-filter-submit">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M4 5h16l-6 7v5l-4 2v-7z"></path>
                        </svg>

                        Tampilkan
                    </button>

                    <a
                        href="index.php?jenis=<?= urlencode($jenis); ?>"
                        class="laporan-filter-reset">
                        Reset Filter
                    </a>

                </div>

            </form>

        </section>

        <section class="laporan-result-panel">

            <div class="laporan-result-header">

                <div>

                    <span class="laporan-eyebrow">
                        Hasil Rekapitulasi
                    </span>

                    <h2><?= $judulJenis; ?></h2>

                    <p><?= $deskripsiJenis; ?></p>

                </div>

                <div class="laporan-result-count">

                    <span>Total hasil</span>

                    <strong>
                        <?= number_format(
                            $totalData,
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>
                        <?= $filterAktif
                            ? "Filter sedang diterapkan"
                            : "Menampilkan seluruh data"; ?>
                    </small>

                </div>

            </div>

            <div class="laporan-table-wrapper">

                <?php if ($jenis === "arsip") : ?>

                    <table class="laporan-table laporan-table-arsip">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal Arsip</th>
                                <th>No Permohonan</th>
                                <th>Nama</th>
                                <th>Tanggal Lahir</th>
                                <th>Jenis</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($totalData === 0) : ?>

                            <tr>
                                <td
                                    colspan="8"
                                    class="laporan-empty-state">

                                    <div class="laporan-empty-icon">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            aria-hidden="true">
                                            <path d="M5 3h10l4 4v14H5z"></path>
                                            <path d="M9 14h6"></path>
                                        </svg>

                                    </div>

                                    <strong>Data tidak ditemukan</strong>

                                    <span>
                                        Ubah atau reset filter untuk
                                        melihat data lainnya.
                                    </span>

                                </td>
                            </tr>

                        <?php else : ?>

                            <?php $nomor = 1; ?>

                            <?php while ($d = mysqli_fetch_assoc($data)) : ?>

                                <?php
                                $kelasStatus =
                                    strtolower($d["status"]) === "tersedia"
                                        ? "available"
                                        : "borrowed";
                                ?>

                                <tr>

                                    <td><?= $nomor++; ?></td>

                                    <td>
                                        <?= date(
                                            "d-m-Y",
                                            strtotime(
                                                $d["tanggal_arsip"]
                                            )
                                        ); ?>
                                    </td>

                                    <td>
                                        <span class="laporan-number">
                                            <?= htmlspecialchars(
                                                $d["nomor_permohonan"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <strong class="laporan-name">
                                            <?= htmlspecialchars(
                                                $d["nama"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= date(
                                            "d-m-Y",
                                            strtotime(
                                                $d["tanggal_lahir"]
                                            )
                                        ); ?>
                                    </td>

                                    <td>

                                        <span class="laporan-category-badge">
                                            <?= htmlspecialchars(
                                                $d["kewarganegaraan"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span class="laporan-location">
                                            Rak <?= htmlspecialchars(
                                                $d["nomor_rak"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>
                                            ·
                                            B<?= (int) $d["baris"]; ?>
                                            ·
                                            K<?= (int) $d["kolom"]; ?>
                                            ·
                                            No <?= (int) $d["nomor_urut"]; ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span
                                            class="laporan-status-badge <?= $kelasStatus; ?>">

                                            <?= htmlspecialchars(
                                                $d["status"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                <?php else : ?>

                    <table class="laporan-table laporan-table-peminjaman">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Permohonan</th>
                                <th>Nama Arsip</th>
                                <th>Peminjam</th>
                                <th>NIP</th>
                                <th>Keperluan</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Kembali</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($totalData === 0) : ?>

                            <tr>
                                <td
                                    colspan="9"
                                    class="laporan-empty-state">

                                    <div class="laporan-empty-icon">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            aria-hidden="true">
                                            <path d="M5 3h10l4 4v14H5z"></path>
                                            <path d="M9 14h6"></path>
                                        </svg>

                                    </div>

                                    <strong>Data tidak ditemukan</strong>

                                    <span>
                                        Ubah atau reset filter untuk
                                        melihat data lainnya.
                                    </span>

                                </td>
                            </tr>

                        <?php else : ?>

                            <?php $nomor = 1; ?>

                            <?php while ($d = mysqli_fetch_assoc($data)) : ?>

                                <?php
                                $kelasStatus =
                                    strtolower($d["status"]) === "dikembalikan"
                                        ? "returned"
                                        : "borrowed";
                                ?>

                                <tr>

                                    <td><?= $nomor++; ?></td>

                                    <td>
                                        <span class="laporan-number">
                                            <?= htmlspecialchars(
                                                $d["nomor_permohonan"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <strong class="laporan-name">
                                            <?= htmlspecialchars(
                                                $d["nama"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $d["nama_peminjam"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>
                                    </td>

                                    <td>
                                        <span class="laporan-nip">
                                            <?= htmlspecialchars(
                                                $d["nip_peminjam"] ?: "-",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>
                                        </span>
                                    </td>

                                    <td>

                                        <span
                                            class="laporan-purpose"
                                            title="<?= htmlspecialchars(
                                                $d["keperluan"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>">

                                            <?= htmlspecialchars(
                                                $d["keperluan"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>

                                        </span>

                                    </td>

                                    <td>
                                        <?= date(
                                            "d-m-Y",
                                            strtotime(
                                                $d["tanggal_pinjam"]
                                            )
                                        ); ?>
                                    </td>

                                    <td>
                                        <?= !empty($d["tanggal_kembali"])
                                            ? date(
                                                "d-m-Y",
                                                strtotime(
                                                    $d["tanggal_kembali"]
                                                )
                                            )
                                            : "-"; ?>
                                    </td>

                                    <td>

                                        <span
                                            class="laporan-status-badge <?= $kelasStatus; ?>">

                                            <?= htmlspecialchars(
                                                $d["status"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                <?php endif; ?>

            </div>

        </section>

    </div>

</div>

<?php include "../template/footer.php"; ?>