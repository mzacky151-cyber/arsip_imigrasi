<?php

include "../template/session.php";
include "../config/koneksi.php";
include "../template/header.php";
include "../template/sidebar.php";

/*
|--------------------------------------------------------------------------
| Statistik
|--------------------------------------------------------------------------
*/

$total = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "SELECT COUNT(*) AS total FROM arsip"
    )
);

$wni = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM arsip
        WHERE kewarganegaraan = 'WNI'
        "
    )
);

$wna = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM arsip
        WHERE kewarganegaraan = 'WNA'
        "
    )
);

$pinjam = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM arsip
        WHERE status = 'Dipinjam'
        "
    )
);

/*
|--------------------------------------------------------------------------
| Filter grafik
|--------------------------------------------------------------------------
*/

$periodeDiizinkan = [
    "harian",
    "bulanan",
    "tahunan"
];

$periode = $_GET["periode"] ?? "bulanan";

if (!in_array($periode, $periodeDiizinkan, true)) {
    $periode = "bulanan";
}

$bulan = filter_input(
    INPUT_GET,
    "bulan",
    FILTER_VALIDATE_INT
);

$tahun = filter_input(
    INPUT_GET,
    "tahun",
    FILTER_VALIDATE_INT
);

if (!$bulan || $bulan < 1 || $bulan > 12) {
    $bulan = (int) date("n");
}

if (!$tahun || $tahun < 2000 || $tahun > 2100) {
    $tahun = (int) date("Y");
}

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

$namaBulanPendek = [
    1  => "Jan",
    2  => "Feb",
    3  => "Mar",
    4  => "Apr",
    5  => "Mei",
    6  => "Jun",
    7  => "Jul",
    8  => "Agu",
    9  => "Sep",
    10 => "Okt",
    11 => "Nov",
    12 => "Des"
];

$label = [];
$dataChart = [];
$subjudulGrafik = "";

/*
|--------------------------------------------------------------------------
| Grafik harian
|--------------------------------------------------------------------------
*/

if ($periode === "harian") {

    $jumlahHari = cal_days_in_month(
        CAL_GREGORIAN,
        $bulan,
        $tahun
    );

    for ($hari = 1; $hari <= $jumlahHari; $hari++) {
        $label[] = (string) $hari;
        $dataChart[$hari] = 0;
    }

    $query = mysqli_query(
        $koneksi,
        "
        SELECT
            DAY(tanggal_arsip) AS posisi,
            COUNT(*) AS jumlah
        FROM arsip
        WHERE
            MONTH(tanggal_arsip) = $bulan
            AND YEAR(tanggal_arsip) = $tahun
        GROUP BY DAY(tanggal_arsip)
        ORDER BY DAY(tanggal_arsip)
        "
    );

    while ($r = mysqli_fetch_assoc($query)) {
        $posisi = (int) $r["posisi"];
        $dataChart[$posisi] = (int) $r["jumlah"];
    }

    $dataChart = array_values($dataChart);

    $subjudulGrafik =
        $namaBulan[$bulan] . " " . $tahun;

/*
|--------------------------------------------------------------------------
| Grafik bulanan
|--------------------------------------------------------------------------
*/

} elseif ($periode === "bulanan") {

    for ($nomorBulan = 1; $nomorBulan <= 12; $nomorBulan++) {
        $label[] = $namaBulanPendek[$nomorBulan];
        $dataChart[$nomorBulan] = 0;
    }

    $query = mysqli_query(
        $koneksi,
        "
        SELECT
            MONTH(tanggal_arsip) AS posisi,
            COUNT(*) AS jumlah
        FROM arsip
        WHERE YEAR(tanggal_arsip) = $tahun
        GROUP BY MONTH(tanggal_arsip)
        ORDER BY MONTH(tanggal_arsip)
        "
    );

    while ($r = mysqli_fetch_assoc($query)) {
        $posisi = (int) $r["posisi"];
        $dataChart[$posisi] = (int) $r["jumlah"];
    }

    $dataChart = array_values($dataChart);

    $subjudulGrafik = "Tahun " . $tahun;

/*
|--------------------------------------------------------------------------
| Grafik tahunan
|--------------------------------------------------------------------------
*/

} else {

    $tahunAwal = 2024;
    $tahunSekarang = (int) date("Y");

    $rentang = mysqli_fetch_assoc(
        mysqli_query(
            $koneksi,
            "
            SELECT
                MIN(YEAR(tanggal_arsip)) AS tahun_awal,
                MAX(YEAR(tanggal_arsip)) AS tahun_akhir
            FROM arsip
            "
        )
    );

    if (!empty($rentang["tahun_awal"])) {
        $tahunAwal = min(
            $tahunAwal,
            (int) $rentang["tahun_awal"]
        );
    }

    $tahunAkhir = max(
        $tahunSekarang,
        (int) ($rentang["tahun_akhir"] ?? $tahunSekarang)
    );

    for (
        $tahunGrafik = $tahunAwal;
        $tahunGrafik <= $tahunAkhir;
        $tahunGrafik++
    ) {
        $label[] = (string) $tahunGrafik;
        $dataChart[$tahunGrafik] = 0;
    }

    $query = mysqli_query(
        $koneksi,
        "
        SELECT
            YEAR(tanggal_arsip) AS posisi,
            COUNT(*) AS jumlah
        FROM arsip
        GROUP BY YEAR(tanggal_arsip)
        ORDER BY YEAR(tanggal_arsip)
        "
    );

    while ($r = mysqli_fetch_assoc($query)) {
        $posisi = (int) $r["posisi"];

        if (array_key_exists($posisi, $dataChart)) {
            $dataChart[$posisi] = (int) $r["jumlah"];
        }
    }

    $dataChart = array_values($dataChart);

    $subjudulGrafik = "Rekap seluruh tahun";
}

/*
|--------------------------------------------------------------------------
| Pencarian arsip
|--------------------------------------------------------------------------
*/

$keyword = trim($_GET["keyword"] ?? "");
$hasil = null;

if ($keyword !== "") {

    $keywordSql = mysqli_real_escape_string(
        $koneksi,
        $keyword
    );

    $hasil = mysqli_query(
        $koneksi,
        "
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
        WHERE
            arsip.nama LIKE '%$keywordSql%'
            OR arsip.nomor_permohonan LIKE '%$keywordSql%'
            OR YEAR(arsip.tanggal_lahir) = '$keywordSql'
        LIMIT 1
        "
    );
}
?>

<div class="main dashboard-modern">

<?php include "../template/topbar.php"; ?>

    <div class="content">

        <section class="dashboard-hero">

            <div class="dashboard-hero-copy">
                <span class="dashboard-eyebrow">
                    Ringkasan Sistem
                </span>

                <h1>Dashboard</h1>

                <p>
                    Pantau jumlah arsip, status peminjaman, dan tren
                    arsip masuk dalam satu tampilan.
                </p>
            </div>

        </section>

        <section class="dashboard-stat-grid">

            <article class="dashboard-stat-card total">

                <div class="dashboard-stat-icon">

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

                <div class="dashboard-stat-content">

                    <span>Total Arsip</span>

                    <strong>
                        <?= number_format(
                            (int) $total["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Seluruh data arsip</small>

                </div>

            </article>

            <article class="dashboard-stat-card wni">

                <div class="dashboard-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <circle cx="12" cy="8" r="3"></circle>
                        <path d="M5 20c.8-4 3.1-6 7-6s6.2 2 7 6"></path>
                    </svg>

                </div>

                <div class="dashboard-stat-content">

                    <span>Arsip WNI</span>

                    <strong>
                        <?= number_format(
                            (int) $wni["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Warga negara Indonesia</small>

                </div>

            </article>

            <article class="dashboard-stat-card wna">

                <div class="dashboard-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"></path>
                    </svg>

                </div>

                <div class="dashboard-stat-content">

                    <span>Arsip WNA</span>

                    <strong>
                        <?= number_format(
                            (int) $wna["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Warga negara asing</small>

                </div>

            </article>

            <article class="dashboard-stat-card borrowed">

                <div class="dashboard-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <path d="M5 4h11v16H5z"></path>
                        <path d="M16 8h3v12H8"></path>
                        <path d="M8 9h5M8 13h5"></path>
                    </svg>

                </div>

                <div class="dashboard-stat-content">

                    <span>Dipinjam</span>

                    <strong>
                        <?= number_format(
                            (int) $pinjam["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Arsip sedang keluar</small>

                </div>

            </article>

        </section>

        <section class="dashboard-main-grid">

            <div class="dashboard-search-panel">

                <div class="dashboard-panel-heading">

                    <div class="dashboard-panel-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-4-4"></path>
                        </svg>

                    </div>

                    <div>
                        <h2>Pencarian Arsip</h2>
                        <p>
                            Cari berdasarkan nama, nomor permohonan,
                            atau tahun lahir.
                        </p>
                    </div>

                </div>

                <form method="GET" class="dashboard-search-form">

                    <div class="dashboard-search-input">

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
                            type="text"
                            name="keyword"
                            placeholder="Masukkan nama atau nomor permohonan"
                            value="<?= htmlspecialchars(
                                $keyword,
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>">

                    </div>

                    <button type="submit">
                        Cari Arsip
                    </button>

                </form>

            </div>

            <div class="dashboard-quick-info">

                <div class="dashboard-quick-info-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 10v6M12 7h.01"></path>
                    </svg>

                </div>

                <div>
                    <span>Status sistem</span>
                    <strong>Data arsip siap digunakan</strong>
                    <small>
                        Pencarian dan grafik membaca data terkini.
                    </small>
                </div>

            </div>

        </section>

        <section class="dashboard-chart-panel">

            <div class="dashboard-chart-header">

                <div class="dashboard-chart-title">

                    <span class="dashboard-eyebrow">
                        Statistik Arsip
                    </span>

                    <h2>Grafik Arsip Masuk</h2>

                    <p>
                        <?= htmlspecialchars(
                            $subjudulGrafik,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>
                    </p>

                </div>

                <form
                    method="GET"
                    class="dashboard-chart-filter">

                    <select name="periode">

                        <option
                            value="harian"
                            <?= $periode === "harian"
                                ? "selected"
                                : ""; ?>>
                            Harian
                        </option>

                        <option
                            value="bulanan"
                            <?= $periode === "bulanan"
                                ? "selected"
                                : ""; ?>>
                            Bulanan
                        </option>

                        <option
                            value="tahunan"
                            <?= $periode === "tahunan"
                                ? "selected"
                                : ""; ?>>
                            Tahunan
                        </option>

                    </select>

                    <?php if ($periode === "harian") : ?>

                        <select name="bulan">

                            <?php
                            for ($i = 1; $i <= 12; $i++) :
                            ?>

                                <option
                                    value="<?= $i; ?>"
                                    <?= $bulan === $i
                                        ? "selected"
                                        : ""; ?>>
                                    <?= $namaBulan[$i]; ?>
                                </option>

                            <?php endfor; ?>

                        </select>

                    <?php endif; ?>

                    <?php if (
                        $periode === "harian" ||
                        $periode === "bulanan"
                    ) : ?>

                        <select name="tahun">

                            <?php
                            for (
                                $i = (int) date("Y");
                                $i >= 2024;
                                $i--
                            ) :
                            ?>

                                <option
                                    value="<?= $i; ?>"
                                    <?= $tahun === $i
                                        ? "selected"
                                        : ""; ?>>
                                    <?= $i; ?>
                                </option>

                            <?php endfor; ?>

                        </select>

                    <?php endif; ?>

                    <button type="submit">
                        Tampilkan
                    </button>

                </form>

            </div>

            <div class="dashboard-chart-body">

                <div class="dashboard-chart-summary">

                    <div>
                        <span>Total periode</span>
                        <strong>
                            <?= number_format(
                                array_sum($dataChart),
                                0,
                                ",",
                                "."
                            ); ?>
                        </strong>
                        <small>arsip masuk</small>
                    </div>

                    <div class="dashboard-chart-legend">
                        <span></span>
                        Jumlah Arsip
                    </div>

                </div>

                <div class="dashboard-chart-canvas">
                    <canvas id="grafikArsip"></canvas>
                </div>

            </div>

        </section>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (
    isset($_GET["akses"]) &&
    $_GET["akses"] === "ditolak"
) : ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    Swal.fire({
        icon: "warning",
        title: "Akses Ditolak",
        text: "Anda tidak memiliki hak akses ke halaman tersebut.",
        confirmButtonText: "Tutup",
        confirmButtonColor: "#123458",
        width: 430,
        customClass: {
            popup: "rounded-popup",
            title: "popup-title",
            confirmButton: "popup-button"
        }
    });

});
</script>

<?php endif; ?>

<?php if ($keyword !== "") : ?>

    <?php if (
        $hasil &&
        mysqli_num_rows($hasil) > 0
    ) : ?>

        <?php
        $d = mysqli_fetch_assoc($hasil);

        $statusClass = $d["status"] === "Tersedia"
            ? "badge-tersedia"
            : "badge-dipinjam";

        $namaArsip = htmlspecialchars(
            $d["nama"],
            ENT_QUOTES,
            "UTF-8"
        );

        $nomorPermohonan = htmlspecialchars(
            $d["nomor_permohonan"],
            ENT_QUOTES,
            "UTF-8"
        );

        $nomorRak = htmlspecialchars(
            $d["nomor_rak"],
            ENT_QUOTES,
            "UTF-8"
        );

        $statusArsip = htmlspecialchars(
            $d["status"],
            ENT_QUOTES,
            "UTF-8"
        );

        $popupHtml = "
            <div>
                <div style='
                    font-size:20px;
                    font-weight:700;
                    color:#123458;
                '>
                    {$namaArsip}
                </div>

                <div style='
                    font-size:13px;
                    color:#888;
                    margin-bottom:18px;
                '>
                    {$nomorPermohonan}
                </div>

                <div class='popup-card'>

                    <div class='popup-label'>
                        Lokasi Penyimpanan
                    </div>

                    <div class='popup-rak'>
                        Rak {$nomorRak}
                    </div>

                    <div class='popup-detail'>
                        Baris " . (int) $d["baris"] . "
                        •
                        Kolom " . (int) $d["kolom"] . "
                        •
                        No " . (int) $d["nomor_urut"] . "
                    </div>

                </div>

                <div style='margin-top:16px;'>
                    <span class='{$statusClass}'>
                        ● {$statusArsip}
                    </span>
                </div>
            </div>
        ";
        ?>

        <script>
        document.addEventListener("DOMContentLoaded", function () {

            Swal.fire({
                icon: "success",
                title: "Arsip Ditemukan",
                width: 380,
                confirmButtonText: "Tutup",
                confirmButtonColor: "#123458",
                html: <?= json_encode(
                    $popupHtml,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                ); ?>,
                customClass: {
                    popup: "rounded-popup",
                    title: "popup-title",
                    confirmButton: "popup-button"
                }
            });

        });
        </script>

    <?php else : ?>

        <script>
        document.addEventListener("DOMContentLoaded", function () {

            Swal.fire({
                icon: "error",
                title: "Arsip Tidak Ditemukan",
                text: "Data arsip yang Anda cari tidak tersedia.",
                confirmButtonColor: "#123458",
                confirmButtonText: "Tutup",
                width: 400,
                customClass: {
                    popup: "rounded-popup",
                    title: "popup-title",
                    confirmButton: "popup-button"
                }
            });

        });
        </script>

    <?php endif; ?>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const canvas = document.getElementById("grafikArsip");
const context = canvas.getContext("2d");

const gradient = context.createLinearGradient(
    0,
    0,
    0,
    360
);

gradient.addColorStop(
    0,
    "rgba(18, 52, 88, 0.28)"
);

gradient.addColorStop(
    1,
    "rgba(18, 52, 88, 0.01)"
);

new Chart(context, {

    type: "line",

    data: {

        labels: <?= json_encode(
            $label,
            JSON_UNESCAPED_UNICODE
        ); ?>,

        datasets: [
            {
                label: "Jumlah Arsip",
                data: <?= json_encode($dataChart); ?>,
                borderColor: "#123458",
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.38,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointBackgroundColor: "#FFD65A",
                pointBorderColor: "#123458",
                pointBorderWidth: 2
            }
        ]

    },

    options: {

        responsive: true,
        maintainAspectRatio: false,

        interaction: {
            mode: "index",
            intersect: false
        },

        plugins: {

            legend: {
                display: false
            },

            tooltip: {

                backgroundColor: "#123458",
                titleColor: "#ffffff",
                bodyColor: "#ffffff",
                padding: 12,
                displayColors: false,
                cornerRadius: 8,

                callbacks: {
                    label: function (context) {
                        return context.parsed.y + " arsip";
                    }
                }

            }

        },

        scales: {

            x: {

                grid: {
                    display: false
                },

                border: {
                    display: false
                },

                ticks: {
                    color: "#7B8794",
                    font: {
                        family: "Poppins",
                        size: 11
                    }
                }

            },

            y: {

                beginAtZero: true,

                border: {
                    display: false
                },

                grid: {
                    color: "rgba(18, 52, 88, 0.08)"
                },

                ticks: {

                    precision: 0,
                    stepSize: 1,
                    color: "#7B8794",

                    font: {
                        family: "Poppins",
                        size: 11
                    }

                }

            }

        }

    }

});
</script>

<?php include "../template/footer.php"; ?>