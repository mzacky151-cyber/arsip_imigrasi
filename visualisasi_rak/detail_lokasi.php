<?php

header('Content-Type: application/json; charset=utf-8');

include "../config/koneksi.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sesi login telah berakhir.'
    ]);
    exit;
}

$idLokasi = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idLokasi) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'ID lokasi tidak valid.'
    ]);
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
        lokasi_rak.status,
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
        lokasi_rak.status,
        rak.nomor_rak,
        rak.jenis
     LIMIT 1"
);

mysqli_stmt_bind_param($stmtLokasi, 'i', $idLokasi);
mysqli_stmt_execute($stmtLokasi);
$lokasi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtLokasi));
mysqli_stmt_close($stmtLokasi);

if (!$lokasi) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Lokasi rak tidak ditemukan.'
    ]);
    exit;
}

$stmtArsip = mysqli_prepare(
    $koneksi,
    "SELECT
        id_arsip,
        nomor_permohonan,
        nama,
        tanggal_lahir,
        nomor_urut,
        status
     FROM arsip
     WHERE id_lokasi = ?
     ORDER BY nomor_urut, nama
     LIMIT 8"
);

mysqli_stmt_bind_param($stmtArsip, 'i', $idLokasi);
mysqli_stmt_execute($stmtArsip);
$hasilArsip = mysqli_stmt_get_result($stmtArsip);

$daftarArsip = [];
while ($arsip = mysqli_fetch_assoc($hasilArsip)) {
    $daftarArsip[] = $arsip;
}
mysqli_stmt_close($stmtArsip);

$kapasitas = max(0, (int) $lokasi['kapasitas']);
$jumlahTerisi = max(0, (int) $lokasi['jumlah_terisi']);
$persentase = $kapasitas > 0
    ? (int) round(($jumlahTerisi / $kapasitas) * 100)
    : 0;

if ($jumlahTerisi <= 0) {
    $kelasStatus = 'kosong';
    $labelStatus = 'KOSONG';
} elseif ($persentase >= 100) {
    $kelasStatus = 'penuh';
    $labelStatus = 'PENUH';
} elseif ($persentase > 70) {
    $kelasStatus = 'hampir-penuh';
    $labelStatus = 'HAMPIR PENUH';
} else {
    $kelasStatus = 'terisi';
    $labelStatus = 'TERISI SEBAGIAN';
}

$rentangTahun = (int) $lokasi['tahun_awal'] === (int) $lokasi['tahun_akhir']
    ? (string) $lokasi['tahun_awal']
    : $lokasi['tahun_awal'] . ' - ' . $lokasi['tahun_akhir'];

$rentangBulan = (int) $lokasi['bulan_awal'] === (int) $lokasi['bulan_akhir']
    ? (string) $lokasi['bulan_awal']
    : $lokasi['bulan_awal'] . ' - ' . $lokasi['bulan_akhir'];

echo json_encode([
    'success' => true,
    'data' => [
        'id_lokasi' => (int) $lokasi['id_lokasi'],
        'nomor_rak' => (int) $lokasi['nomor_rak'],
        'jenis' => $lokasi['jenis'],
        'baris' => (int) $lokasi['baris'],
        'kolom' => (int) $lokasi['kolom'],
        'rentang_tahun' => $rentangTahun,
        'rentang_bulan' => $rentangBulan,
        'kapasitas' => $kapasitas,
        'jumlah_terisi' => $jumlahTerisi,
        'persentase' => min(100, $persentase),
        'kelas_status' => $kelasStatus,
        'label_status' => $labelStatus,
        'arsip' => $daftarArsip
    ]
], JSON_UNESCAPED_UNICODE);
