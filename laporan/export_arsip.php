<?php

include "../config/koneksi.php";
include "../template/session.php";

wajibLevel(["admin"]);

$keyword      = trim($_GET["keyword"] ?? "");
$tanggalAwal  = trim($_GET["tanggal_awal"] ?? "");
$tanggalAkhir = trim($_GET["tanggal_akhir"] ?? "");
$kategori     = $_GET["kategori"] ?? "";
$status       = $_GET["status"] ?? "";

if (!in_array($kategori, ["", "WNI", "WNA"], true)) {
    $kategori = "";
}

if (!in_array($status, ["", "Tersedia", "Dipinjam"], true)) {
    $status = "";
}

$keywordSql  = mysqli_real_escape_string($koneksi, $keyword);
$awalSql     = mysqli_real_escape_string($koneksi, $tanggalAwal);
$akhirSql    = mysqli_real_escape_string($koneksi, $tanggalAkhir);
$kategoriSql = mysqli_real_escape_string($koneksi, $kategori);
$statusSql   = mysqli_real_escape_string($koneksi, $status);

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
    $sql .= " AND arsip.tanggal_arsip >= '$awalSql'";
}

if ($tanggalAkhir !== "") {
    $sql .= " AND arsip.tanggal_arsip <= '$akhirSql'";
}

if ($kategori !== "") {
    $sql .= " AND arsip.kewarganegaraan = '$kategoriSql'";
}

if ($status !== "") {
    $sql .= " AND arsip.status = '$statusSql'";
}

$sql .= " ORDER BY arsip.tanggal_arsip DESC, arsip.id_arsip DESC";

$data = mysqli_query($koneksi, $sql);

if (!$data) {
    die("Gagal mengekspor laporan.");
}

$namaFile = "laporan_arsip_" . date("Y-m-d_H-i-s") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header(
    'Content-Disposition: attachment; filename="' . $namaFile . '"'
);

$output = fopen("php://output", "w");

fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    "No",
    "Tanggal Arsip",
    "Nomor Permohonan",
    "Nama",
    "Tanggal Lahir",
    "Kewarganegaraan",
    "Rak",
    "Baris",
    "Kolom",
    "Nomor Urut",
    "Status"
], ";");

$no = 1;

while ($d = mysqli_fetch_assoc($data)) {

    fputcsv($output, [
        $no++,
        $d["tanggal_arsip"],
        $d["nomor_permohonan"],
        $d["nama"],
        $d["tanggal_lahir"],
        $d["kewarganegaraan"],
        $d["nomor_rak"],
        $d["baris"],
        $d["kolom"],
        $d["nomor_urut"],
        $d["status"]
    ], ";");
}

fclose($output);
exit;