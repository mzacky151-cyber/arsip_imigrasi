<?php

include "../config/koneksi.php";
include "../template/session.php";
include "../template/header.php";
include "../template/sidebar.php";

/*
|--------------------------------------------------------------------------
| Filter pencarian dan periode
|--------------------------------------------------------------------------
*/

$keyword = trim($_GET["keyword"] ?? "");

$filterTahun = filter_input(
    INPUT_GET,
    "tahun",
    FILTER_VALIDATE_INT
);

$filterBulan = filter_input(
    INPUT_GET,
    "bulan",
    FILTER_VALIDATE_INT
);

if (!$filterTahun || $filterTahun < 2000 || $filterTahun > 2100) {
    $filterTahun = 0;
}

if (!$filterBulan || $filterBulan < 1 || $filterBulan > 12) {
    $filterBulan = 0;
}

/*
 * Ketika bulan dipilih tanpa tahun,
 * gunakan tahun berjalan.
 */
if ($filterBulan > 0 && $filterTahun === 0) {
    $filterTahun = (int) date("Y");
}

$keywordSql = mysqli_real_escape_string(
    $koneksi,
    $keyword
);

$tahunSekarang = (int) date("Y");
$bulanSekarang = (int) date("n");

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

/*
|--------------------------------------------------------------------------
| Pilihan tahun dari data arsip
|--------------------------------------------------------------------------
*/

$daftarTahun = [];

$queryTahun = mysqli_query(
    $koneksi,
    "
    SELECT DISTINCT YEAR(tanggal_arsip) AS tahun
    FROM arsip
    WHERE tanggal_arsip IS NOT NULL
    ORDER BY tahun DESC
    "
);

if ($queryTahun) {
    while ($barisTahun = mysqli_fetch_assoc($queryTahun)) {
        $tahunData = (int) $barisTahun["tahun"];

        if ($tahunData > 0) {
            $daftarTahun[] = $tahunData;
        }
    }
}

if (!in_array($tahunSekarang, $daftarTahun, true)) {
    $daftarTahun[] = $tahunSekarang;
}

rsort($daftarTahun);

/*
|--------------------------------------------------------------------------
| Statistik ringkas
|--------------------------------------------------------------------------
*/

$totalSemua = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM arsip
        "
    )
);

$totalTahunIni = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM arsip
        WHERE YEAR(tanggal_arsip) = $tahunSekarang
        "
    )
);

$totalBulanIni = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM arsip
        WHERE
            YEAR(tanggal_arsip) = $tahunSekarang
            AND MONTH(tanggal_arsip) = $bulanSekarang
        "
    )
);

/*
|--------------------------------------------------------------------------
| Kondisi pencarian dan filter
|--------------------------------------------------------------------------
*/

$kondisi = [];

if ($keyword !== "") {
    $kondisi[] = "
        (
            arsip.nama LIKE '%$keywordSql%'
            OR arsip.nomor_permohonan LIKE '%$keywordSql%'
            OR YEAR(arsip.tanggal_lahir) = '$keywordSql'
        )
    ";
}

if ($filterTahun > 0) {
    $kondisi[] = "
        YEAR(arsip.tanggal_arsip) = $filterTahun
    ";
}

if ($filterBulan > 0) {
    $kondisi[] = "
        MONTH(arsip.tanggal_arsip) = $filterBulan
    ";
}

$where = "";

if (count($kondisi) > 0) {
    $where = "WHERE " . implode(" AND ", $kondisi);
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$dataPerHalaman = 10;

$halaman = filter_input(
    INPUT_GET,
    "halaman",
    FILTER_VALIDATE_INT
);

if (!$halaman || $halaman < 1) {
    $halaman = 1;
}

$queryJumlah = mysqli_query(
    $koneksi,
    "
    SELECT COUNT(*) AS total
    FROM arsip
    $where
    "
);

if (!$queryJumlah) {
    die("Gagal menghitung data arsip: " . mysqli_error($koneksi));
}

$jumlahData = mysqli_fetch_assoc($queryJumlah);
$totalData = (int) $jumlahData["total"];

$totalHalaman = max(
    1,
    (int) ceil($totalData / $dataPerHalaman)
);

if ($halaman > $totalHalaman) {
    $halaman = $totalHalaman;
}

$offset = ($halaman - 1) * $dataPerHalaman;

/*
|--------------------------------------------------------------------------
| Data arsip
|--------------------------------------------------------------------------
*/

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
$where
ORDER BY arsip.id_arsip DESC
LIMIT $dataPerHalaman
OFFSET $offset
";

$data = mysqli_query($koneksi, $sql);

if (!$data) {
    die("Gagal memuat data arsip: " . mysqli_error($koneksi));
}

$awalData = $totalData > 0
    ? $offset + 1
    : 0;

$akhirData = min(
    $offset + $dataPerHalaman,
    $totalData
);

/*
|--------------------------------------------------------------------------
| Parameter URL untuk pagination
|--------------------------------------------------------------------------
*/

function urlHalamanArsip(
    int $nomorHalaman,
    string $keyword,
    int $filterTahun,
    int $filterBulan
): string {
    return "index.php?" . http_build_query([
        "keyword" => $keyword,
        "tahun"   => $filterTahun ?: "",
        "bulan"   => $filterBulan ?: "",
        "halaman" => $nomorHalaman
    ]);
}
?>

<div class="main arsip-modern">

    <?php include "../template/topbar.php"; ?>

    <div class="content">

    <section class="arsip-hero">

        <div class="arsip-hero-copy">

            <span class="arsip-eyebrow">
                Pengelolaan Arsip
            </span>

            <h1>Data Arsip</h1>

            <p>
                Kelola dan lihat seluruh data arsip permohonan yang tersimpan di dalam sistem SIPA.
            </p>

        </div>

        <div class="arsip-hero-actions">

            <button
                type="button"
                class="arsip-import-button"
                disabled
                title="Fitur integrasi Excel akan ditambahkan pada tahap berikutnya">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    aria-hidden="true">

                    <path d="M12 16V4"></path>
                    <path d="m7 9 5-5 5 5"></path>
                    <path d="M5 14v5h14v-5"></path>

                </svg>

                Import Excel

            </button>

            <a
                href="tambah.php"
                class="arsip-add-button">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true">

                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>

                </svg>

                Tambah Arsip

            </a>

        </div>

    </section>

        <div class="arsip-summary-grid">

            <div class="card arsip-summary-card total">

                <div class="arsip-summary-icon">
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
                    <div class="arsip-summary-label">Total Arsip</div>
                    <div class="arsip-summary-value">
                        <?= number_format(
                            (int) $totalSemua["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </div>
                    <div class="arsip-summary-note">
                        Seluruh arsip yang tersimpan
                    </div>
                </div>

            </div>

            <div class="card arsip-summary-card year">

                <div class="arsip-summary-icon">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                        <path d="M8 3v4M16 3v4M3 10h18"></path>
                    </svg>
                </div>

                <div>
                    <div class="arsip-summary-label">Arsip Tahun Ini</div>
                    <div class="arsip-summary-value">
                        <?= number_format(
                            (int) $totalTahunIni["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </div>
                    <div class="arsip-summary-note">
                        Tahun <?= $tahunSekarang; ?>
                    </div>
                </div>

            </div>

            <div class="card arsip-summary-card month">

                <div class="arsip-summary-icon">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                        <path d="M8 3v4M16 3v4M3 10h18"></path>
                        <path d="M8 14h3M13 14h3M8 17h3"></path>
                    </svg>
                </div>

                <div>
                    <div class="arsip-summary-label">Arsip Bulan Ini</div>
                    <div class="arsip-summary-value">
                        <?= number_format(
                            (int) $totalBulanIni["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </div>
                    <div class="arsip-summary-note">
                        <?= $namaBulan[$bulanSekarang]; ?>
                        <?= $tahunSekarang; ?>
                    </div>
                </div>

            </div>

            <div class="card arsip-summary-card filter">

                <div class="arsip-summary-icon">
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
                    <div class="arsip-summary-label">Hasil Filter</div>
                    <div class="arsip-summary-value">
                        <?= number_format(
                            $totalData,
                            0,
                            ",",
                            "."
                        ); ?>
                    </div>
                    <div class="arsip-summary-note">
                        Sesuai pencarian dan periode
                    </div>
                </div>

            </div>

        </div>

        <div class="card arsip-filter-card">

            <form method="GET" class="arsip-filter-layout">

                <input
                    type="hidden"
                    name="keyword"
                    value="<?= htmlspecialchars(
                        $keyword,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>">

                <div class="arsip-filter-intro">
                    <h3>Filter Arsip Masuk</h3>
                    <p>
                        Pilih tahun atau bulan untuk melihat jumlah dan
                        data arsip pada periode tertentu.
                    </p>
                </div>

                <div class="arsip-filter-field">
                    <label for="filter-tahun">Tahun</label>

                    <select
                        id="filter-tahun"
                        name="tahun"
                        class="form-control">

                        <option value="">Semua Tahun</option>

                        <?php foreach ($daftarTahun as $tahunPilihan) : ?>

                            <option
                                value="<?= $tahunPilihan; ?>"
                                <?= $filterTahun === $tahunPilihan
                                    ? "selected"
                                    : ""; ?>>
                                <?= $tahunPilihan; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="arsip-filter-field">
                    <label for="filter-bulan">Bulan</label>

                    <select
                        id="filter-bulan"
                        name="bulan"
                        class="form-control">

                        <option value="">Semua Bulan</option>

                        <?php foreach ($namaBulan as $nomor => $nama) : ?>

                            <option
                                value="<?= $nomor; ?>"
                                <?= $filterBulan === $nomor
                                    ? "selected"
                                    : ""; ?>>
                                <?= $nama; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="arsip-filter-actions">

                    <button
                        type="submit"
                        class="btn btn-primary">
                        Tampilkan
                    </button>

                    <a
                        href="index.php"
                        class="btn btn-warning">
                        Reset
                    </a>

                </div>

            </form>

        </div>

        <div class="card arsip-data-card">

            <form method="GET" class="arsip-search-row">

                <input
                    type="hidden"
                    name="tahun"
                    value="<?= $filterTahun ?: ""; ?>">

                <input
                    type="hidden"
                    name="bulan"
                    value="<?= $filterBulan ?: ""; ?>">

                <input
                    type="text"
                    name="keyword"
                    class="form-control"
                    placeholder="Cari nama, nomor permohonan, atau tahun lahir..."
                    value="<?= htmlspecialchars(
                        $keyword,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>">

                <button type="submit" class="btn btn-primary">
                    Cari
                </button>

            </form>

            <?php if (
                $keyword !== "" ||
                $filterTahun > 0 ||
                $filterBulan > 0
            ) : ?>

                <div class="arsip-result-info">

                    Ditemukan

                    <b><?= $totalData; ?></b>

                    data sesuai pencarian dan periode yang dipilih.

                </div>

            <?php endif; ?>

            <div class="arsip-table-wrapper">

                <table class="arsip-data-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Permohonan</th>
                            <th>Nama</th>
                            <th>Tanggal Lahir</th>
                            <th>Kewarganegaraan</th>
                            <th>Tanggal Arsip</th>
                            <th>Lokasi Rak</th>
                            <th>Nomor Urut</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($totalData === 0) : ?>

                        <tr>
                            <td colspan="10" class="arsip-empty-row">
                                Data arsip tidak ditemukan.
                            </td>
                        </tr>

                    <?php else : ?>

                        <?php
                        $no = $offset + 1;

                        while ($d = mysqli_fetch_assoc($data)) :
                        ?>

                            <?php
                            $statusClass = strtolower(
                                preg_replace(
                                    "/[^a-zA-Z0-9]+/",
                                    "-",
                                    $d["status"]
                                )
                            );
                            ?>

                            <tr>

                                <td><?= $no++; ?></td>

                                <td>
                                    <?= htmlspecialchars(
                                        $d["nomor_permohonan"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $d["nama"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </td>

                                <td>
                                    <?= date(
                                        "d-m-Y",
                                        strtotime($d["tanggal_lahir"])
                                    ); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $d["kewarganegaraan"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </td>

                                <td>
                                    <?= date(
                                        "d-m-Y",
                                        strtotime($d["tanggal_arsip"])
                                    ); ?>
                                </td>

                                <td>
                                    Rak <?= htmlspecialchars(
                                        $d["nomor_rak"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                    -
                                    Baris <?= (int) $d["baris"]; ?>
                                    -
                                    Kolom <?= (int) $d["kolom"]; ?>
                                </td>

                                <td>
                                    <?= str_pad(
                                        (string) (int) $d["nomor_urut"],
                                        3,
                                        "0",
                                        STR_PAD_LEFT
                                    ); ?>
                                </td>

                                <td>
                                    <span class="arsip-status <?= $statusClass; ?>">
                                        <?= htmlspecialchars(
                                            $d["status"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>
                                    </span>
                                </td>

                                <td>

                                    <div class="arsip-action-group">

                                        <a
                                            href="edit.php?id=<?= (int) $d["id_arsip"]; ?>"
                                            class="arsip-action-button edit"
                                            title="Edit arsip"
                                            aria-label="Edit arsip">

                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true">
                                                <path d="M4 20h4l10-10-4-4L4 16v4z"></path>
                                                <path d="m13 7 4 4"></path>
                                            </svg>

                                        </a>

                                        <?php if (punyaAkses(["admin"])) : ?>

                                            <form
                                                action="hapus.php"
                                                method="POST"
                                                class="arsip-action-form form-hapus-arsip"
                                                data-nama="<?= htmlspecialchars(
                                                    $d["nama"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ); ?>">

                                                <input
                                                    type="hidden"
                                                    name="id_arsip"
                                                    value="<?= (int) $d["id_arsip"]; ?>">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= htmlspecialchars(
                                                        tokenCsrf(),
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ); ?>">

                                                <button
                                                    type="submit"
                                                    class="arsip-action-button delete"
                                                    title="Hapus arsip"
                                                    aria-label="Hapus arsip">

                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        aria-hidden="true">
                                                        <path d="M4 7h16"></path>
                                                        <path d="M9 7V4h6v3"></path>
                                                        <path d="m6 7 1 13h10l1-13"></path>
                                                        <path d="M10 11v5M14 11v5"></path>
                                                    </svg>

                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="arsip-pagination-wrapper">

                <div class="arsip-pagination-info">

                    Menampilkan

                    <b><?= $awalData; ?></b>

                    sampai

                    <b><?= $akhirData; ?></b>

                    dari

                    <b><?= $totalData; ?></b>

                    data

                </div>

                <?php if ($totalHalaman > 1) : ?>

                    <div class="arsip-pagination">

                        <?php if ($halaman > 1) : ?>

                            <a
                                href="<?= htmlspecialchars(
                                    urlHalamanArsip(
                                        $halaman - 1,
                                        $keyword,
                                        $filterTahun,
                                        $filterBulan
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                class="arsip-page-button"
                                aria-label="Halaman sebelumnya">
                                ‹
                            </a>

                        <?php endif; ?>

                        <?php
                        $awalHalaman = max(1, $halaman - 2);
                        $akhirHalaman = min(
                            $totalHalaman,
                            $halaman + 2
                        );
                        ?>

                        <?php if ($awalHalaman > 1) : ?>

                            <a
                                href="<?= htmlspecialchars(
                                    urlHalamanArsip(
                                        1,
                                        $keyword,
                                        $filterTahun,
                                        $filterBulan
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                class="arsip-page-button">
                                1
                            </a>

                            <?php if ($awalHalaman > 2) : ?>
                                <span class="arsip-page-dots">...</span>
                            <?php endif; ?>

                        <?php endif; ?>

                        <?php
                        for (
                            $nomorHalaman = $awalHalaman;
                            $nomorHalaman <= $akhirHalaman;
                            $nomorHalaman++
                        ) :
                        ?>

                            <a
                                href="<?= htmlspecialchars(
                                    urlHalamanArsip(
                                        $nomorHalaman,
                                        $keyword,
                                        $filterTahun,
                                        $filterBulan
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                class="arsip-page-button <?= $nomorHalaman === $halaman
                                    ? "active"
                                    : ""; ?>">
                                <?= $nomorHalaman; ?>
                            </a>

                        <?php endfor; ?>

                        <?php if ($akhirHalaman < $totalHalaman) : ?>

                            <?php if (
                                $akhirHalaman < $totalHalaman - 1
                            ) : ?>
                                <span class="arsip-page-dots">...</span>
                            <?php endif; ?>

                            <a
                                href="<?= htmlspecialchars(
                                    urlHalamanArsip(
                                        $totalHalaman,
                                        $keyword,
                                        $filterTahun,
                                        $filterBulan
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                class="arsip-page-button">
                                <?= $totalHalaman; ?>
                            </a>

                        <?php endif; ?>

                        <?php if ($halaman < $totalHalaman) : ?>

                            <a
                                href="<?= htmlspecialchars(
                                    urlHalamanArsip(
                                        $halaman + 1,
                                        $keyword,
                                        $filterTahun,
                                        $filterBulan
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                class="arsip-page-button"
                                aria-label="Halaman berikutnya">
                                ›
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.querySelectorAll(".form-hapus-arsip").forEach(function (form) {

    form.addEventListener("submit", function (event) {

        event.preventDefault();

        const nama = form.dataset.nama;

        Swal.fire({

            icon: "warning",
            title: "Hapus Arsip?",
            width: 430,

            html: `
                <p style="margin-bottom:16px;">
                    Arsip berikut akan dihapus secara permanen.
                </p>

                <div style="
                    background:#F8FAFC;
                    border:1px solid #E5E7EB;
                    border-radius:12px;
                    padding:16px;
                    text-align:left;
                ">
                    <b>Nama Arsip</b><br>
                    ${nama}
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
                form.submit();
            }

        });

    });

});
</script>

<?php
$notifikasi = null;

if (isset($_SESSION["update_success"])) {

    $notifikasi = [
        "icon"  => "success",
        "title" => "Data Berhasil Diperbarui",
        "text"  => "Perubahan data arsip berhasil disimpan."
    ];

    unset($_SESSION["update_success"]);

} elseif (isset($_SESSION["delete_success"])) {

    $notifikasi = [
        "icon"  => "success",
        "title" => "Arsip Berhasil Dihapus",
        "text"  => "Data arsip telah dihapus dari sistem."
    ];

    unset($_SESSION["delete_success"]);

} elseif (isset($_SESSION["arsip_action_error"])) {

    $notifikasi = [
        "icon"  => "error",
        "title" => "Proses Gagal",
        "text"  => $_SESSION["arsip_action_error"]
    ];

    unset($_SESSION["arsip_action_error"]);
}
?>

<?php if ($notifikasi !== null) : ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const notifikasi = <?= json_encode(
        $notifikasi,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ); ?>;

    Swal.fire({
        icon: notifikasi.icon,
        title: notifikasi.title,
        text: notifikasi.text,
        width: 430,
        confirmButtonText: "Tutup",
        confirmButtonColor: "#123458",
        allowOutsideClick: false,
        customClass: {
            popup: "rounded-popup",
            title: "popup-title",
            confirmButton: "popup-button"
        }
    });

});
</script>

<?php endif; ?>

<?php include "../template/footer.php"; ?>