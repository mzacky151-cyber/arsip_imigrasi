<?php

include "../config/koneksi.php";
include "../template/session.php";
wajibLevel(["admin"]);

$id_rak       = $_POST['id_rak'];
$baris        = $_POST['baris'];
$kolom        = $_POST['kolom'];
$tahun_awal   = $_POST['tahun_awal'];
$tahun_akhir  = $_POST['tahun_akhir'];
$bulan_awal   = $_POST['bulan_awal'];
$bulan_akhir  = $_POST['bulan_akhir'];
$kapasitas    = $_POST['kapasitas'];

$query = mysqli_query($koneksi,"
INSERT INTO lokasi_rak
(
id_rak,
baris,
kolom,
tahun_awal,
tahun_akhir,
bulan_awal,
bulan_akhir,
kapasitas,
kapasitas_terisi,
status
)
VALUES
(
'$id_rak',
'$baris',
'$kolom',
'$tahun_awal',
'$tahun_akhir',
'$bulan_awal',
'$bulan_akhir',
'$kapasitas',
0,
'Aktif'
)
");

if(!$query){
    die(mysqli_error($koneksi));
}

header("Location:index.php");
exit;

?>