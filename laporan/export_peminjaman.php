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

if (!in_array($status, ["", "Dipinjam", "Dikembalikan"], true)) {
    $status = "";
}

$keywordSql  = mysqli_real_escape_string($koneksi, $keyword);
$awalSql     = mysqli_real_escape_string($koneksi, $tanggalAwal);
$akhirSql    = mysqli_real_escape_string($koneksi, $tanggalAkhir);
$kategoriSql = mysqli_real_escape_string($koneksi, $kategori);
$statusSql   = mysqli_real_escape_string($koneksi, $status);

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
    $sql .= " AND peminjaman.tanggal_pinjam >= '$awalSql'";
}

if ($tanggalAkhir !== "") {
    $sql .= " AND peminjaman.tanggal_pinjam <= '$akhirSql'";
}

if ($kategori !== "") {
    $sql .= " AND arsip.kewarganegaraan = '$kategoriSql'";
}

if ($status !== "") {
    $sql .= " AND peminjaman.status = '$statusSql'";
}

$sql .= "
    ORDER BY
        peminjaman.tanggal_pinjam DESC,
        peminjaman.id_peminjaman DESC
";

$data = mysqli_query($koneksi, $sql);

if (!$data) {
    die("Gagal mengekspor laporan.");
}

$namaFile = "laporan_peminjaman_" . date("Y-m-d_H-i-s") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header(
    'Content-Disposition: attachment; filename="' . $namaFile . '"'
);

$output = fopen("php://output", "w");

fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    "No",
    "Nomor Permohonan",
    "Nama Arsip",
    "Kewarganegaraan",
    "Nama Peminjam",
    "NIP Peminjam",
    "Keperluan",
    "Tanggal Pinjam",
    "Tanggal Kembali",
    "Status",
    "Rak",
    "Baris",
    "Kolom"
], ";");

$no = 1;

while ($d = mysqli_fetch_assoc($data)) {

    fputcsv($output, [
        $no++,
        $d["nomor_permohonan"],
        $d["nama"],
        $d["kewarganegaraan"],
        $d["nama_peminjam"],
        $d["nip_peminjam"],
        $d["keperluan"],
        $d["tanggal_pinjam"],
        $d["tanggal_kembali"] ?: "-",
        $d["status"],
        $d["nomor_rak"],
        $d["baris"],
        $d["kolom"]
    ], ";");
}

fclose($output);
exit;