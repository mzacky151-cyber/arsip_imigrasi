<?php

include "../config/koneksi.php";
include "../template/session.php";
include "../template/header.php";
include "../template/sidebar.php";

function e(string $nilai): string
{
    return htmlspecialchars($nilai, ENT_QUOTES, 'UTF-8');
}

$idLokasi = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idLokasi) {
    header('Location: index.php');
    exit;
}

$stmtLokasi = mysqli_prepare(
    $koneksi,
    "SELECT
        lokasi_rak.id_lokasi,
        lokasi_rak.baris,
        lokasi_rak.kolom,
        lokasi_rak.tahun_awal,
        lokasi_rak.tahun_akhir,
        lokasi_rak.bulan_awal,
        lokasi_rak.bulan_akhir,
        lokasi_rak.kapasitas,
        rak.id_rak,
        rak.nomor_rak,
        rak.jenis,
        COUNT(arsip.id_arsip) AS jumlah_terisi
     FROM lokasi_rak
     JOIN rak ON rak.id_rak = lokasi_rak.id_rak
     LEFT JOIN arsip ON arsip.id_lokasi = lokasi_rak.id_lokasi
     WHERE lokasi_rak.id_lokasi = ?
     GROUP BY
        lokasi_rak.id_lokasi,
        lokasi_rak.baris,
        lokasi_rak.kolom,
        lokasi_rak.tahun_awal,
        lokasi_rak.tahun_akhir,
        lokasi_rak.bulan_awal,
        lokasi_rak.bulan_akhir,
        lokasi_rak.kapasitas,
        rak.id_rak,
        rak.nomor_rak,
        rak.jenis
     LIMIT 1"
);
mysqli_stmt_bind_param($stmtLokasi, 'i', $idLokasi);
mysqli_stmt_execute($stmtLokasi);
$lokasi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtLokasi));
mysqli_stmt_close($stmtLokasi);

if (!$lokasi) {
    header('Location: index.php');
    exit;
}

$keyword = trim($_GET['keyword'] ?? '');
$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$batas = 20;
$offset = ($halaman - 1) * $batas;

if ($keyword !== '') {
    $pola = '%' . $keyword . '%';

    $stmtJumlah = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM arsip
         WHERE id_lokasi = ?
           AND (nama LIKE ? OR nomor_permohonan LIKE ?)"
    );
    mysqli_stmt_bind_param($stmtJumlah, 'iss', $idLokasi, $pola, $pola);

    $stmtData = mysqli_prepare(
        $koneksi,
        "SELECT
            id_arsip,
            nomor_permohonan,
            nama,
            tanggal_lahir,
            kewarganegaraan,
            nomor_urut,
            tanggal_arsip,
            status
         FROM arsip
         WHERE id_lokasi = ?
           AND (nama LIKE ? OR nomor_permohonan LIKE ?)
         ORDER BY nomor_urut, nama
         LIMIT ? OFFSET ?"
    );
    mysqli_stmt_bind_param($stmtData, 'issii', $idLokasi, $pola, $pola, $batas, $offset);
} else {
    $stmtJumlah = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM arsip
         WHERE id_lokasi = ?"
    );
    mysqli_stmt_bind_param($stmtJumlah, 'i', $idLokasi);

    $stmtData = mysqli_prepare(
        $koneksi,
        "SELECT
            id_arsip,
            nomor_permohonan,
            nama,
            tanggal_lahir,
            kewarganegaraan,
            nomor_urut,
            tanggal_arsip,
            status
         FROM arsip
         WHERE id_lokasi = ?
         ORDER BY nomor_urut, nama
         LIMIT ? OFFSET ?"
    );
    mysqli_stmt_bind_param($stmtData, 'iii', $idLokasi, $batas, $offset);
}

mysqli_stmt_execute($stmtJumlah);
$totalData = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmtJumlah))['total'];
mysqli_stmt_close($stmtJumlah);

$totalHalaman = max(1, (int) ceil($totalData / $batas));
if ($halaman > $totalHalaman) {
    $halaman = $totalHalaman;
    $offset = ($halaman - 1) * $batas;
}

mysqli_stmt_execute($stmtData);
$hasilData = mysqli_stmt_get_result($stmtData);
mysqli_stmt_close($stmtData);

$kapasitas = max(0, (int) $lokasi['kapasitas']);
$jumlahTerisi = max(0, (int) $lokasi['jumlah_terisi']);
$persentase = $kapasitas > 0 ? min(100, (int) round(($jumlahTerisi / $kapasitas) * 100)) : 0;

$queryDasar = http_build_query([
    'id' => $idLokasi,
    'keyword' => $keyword
]);

?>

<link rel="stylesheet" href="../assets/css/visualisasi-rak.css">

<div class="main">
<?php include "../template/topbar.php"; ?>

    <div class="content visualisasi-page">
        <div class="visualisasi-heading">
            <div>
                <h2>Daftar Arsip Lokasi</h2>
                <p>Rincian seluruh arsip yang tersimpan pada lokasi terpilih.</p>
            </div>

            <a
                href="index.php?jenis=<?= e($lokasi['jenis']); ?>&id_rak=<?= (int) $lokasi['id_rak']; ?>"
                class="btn btn-warning btn-visualisasi"
            >
                Kembali ke Rak
            </a>
        </div>

        <div class="card location-summary-card">
            <div class="location-summary-main">
                <div>
                    <span class="detail-eyebrow">Lokasi Penyimpanan</span>
                    <h3>
                        Rak <?= (int) $lokasi['nomor_rak']; ?>
                        (<?= e($lokasi['jenis']); ?>),
                        B<?= (int) $lokasi['baris']; ?>-K<?= (int) $lokasi['kolom']; ?>
                    </h3>
                </div>

                <div class="summary-capacity">
                    <b><?= $jumlahTerisi; ?> / <?= $kapasitas; ?></b>
                    <span><?= $persentase; ?>% terisi</span>
                </div>
            </div>

            <div class="capacity-track summary-track">
                <div class="capacity-fill" style="width:<?= $persentase; ?>%"></div>
            </div>
        </div>

        <div class="card">
            <div class="arsip-list-toolbar">
                <div>
                    <h3>Daftar Arsip</h3>
                    <p><?= $totalData; ?> data ditemukan</p>
                </div>

                <form method="GET" class="arsip-location-search">
                    <input type="hidden" name="id" value="<?= $idLokasi; ?>">
                    <input
                        type="text"
                        name="keyword"
                        class="form-control"
                        value="<?= e($keyword); ?>"
                        placeholder="Cari nama atau nomor permohonan"
                    >
                    <button type="submit" class="btn btn-primary btn-visualisasi">Cari</button>

                    <?php if ($keyword !== '') : ?>
                        <a href="daftar_arsip.php?id=<?= $idLokasi; ?>"
                           class="btn btn-warning btn-visualisasi">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No Urut</th>
                            <th>No Permohonan</th>
                            <th>Nama</th>
                            <th>Tanggal Lahir</th>
                            <th>Tanggal Arsip</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($hasilData) === 0) : ?>
                            <tr>
                                <td colspan="6" class="table-empty-cell">
                                    Tidak ada data arsip pada lokasi ini.
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php while ($arsip = mysqli_fetch_assoc($hasilData)) : ?>
                                <tr>
                                    <td><?= (int) $arsip['nomor_urut']; ?></td>
                                    <td><?= e($arsip['nomor_permohonan']); ?></td>
                                    <td><?= e($arsip['nama'] ?? '-'); ?></td>
                                    <td>
                                        <?= $arsip['tanggal_lahir']
                                            ? date('d-m-Y', strtotime($arsip['tanggal_lahir']))
                                            : '-'; ?>
                                    </td>
                                    <td>
                                        <?= $arsip['tanggal_arsip']
                                            ? date('d-m-Y', strtotime($arsip['tanggal_arsip']))
                                            : '-'; ?>
                                    </td>
                                    <td>
                                        <span class="arsip-status <?= $arsip['status'] === 'Dipinjam' ? 'dipinjam' : 'tersedia'; ?>">
                                            <?= e($arsip['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalHalaman > 1) : ?>
                <div class="location-pagination">
                    <?php if ($halaman > 1) : ?>
                        <a href="?<?= $queryDasar; ?>&halaman=<?= $halaman - 1; ?>" class="page-btn">‹</a>
                    <?php endif; ?>

                    <?php
                    $mulai = max(1, $halaman - 2);
                    $selesai = min($totalHalaman, $halaman + 2);
                    ?>

                    <?php for ($nomorHalaman = $mulai; $nomorHalaman <= $selesai; $nomorHalaman++) : ?>
                        <a
                            href="?<?= $queryDasar; ?>&halaman=<?= $nomorHalaman; ?>"
                            class="page-btn <?= $nomorHalaman === $halaman ? 'active' : ''; ?>"
                        >
                            <?= $nomorHalaman; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($halaman < $totalHalaman) : ?>
                        <a href="?<?= $queryDasar; ?>&halaman=<?= $halaman + 1; ?>" class="page-btn">›</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include "../template/footer.php"; ?>
