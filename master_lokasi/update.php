<?php

include "../config/koneksi.php";
include "../template/session.php";
wajibLevel(["admin"]);

$id_lokasi   = $_POST['id_lokasi'];
$id_rak      = $_POST['id_rak'];
$baris       = $_POST['baris'];
$kolom       = $_POST['kolom'];
$kapasitas   = $_POST['kapasitas'];
$tahun_awal  = $_POST['tahun_awal'];
$tahun_akhir = $_POST['tahun_akhir'];
$bulan_awal  = $_POST['bulan_awal'];
$bulan_akhir = $_POST['bulan_akhir'];


/* ==========================
   VALIDASI TAHUN
========================== */

if($tahun_akhir != "" && $tahun_awal > $tahun_akhir){

    echo "<script>
    alert('Tahun awal tidak boleh lebih besar dari tahun akhir.');
    history.back();
    </script>";

    exit;

}


/* ==========================
   VALIDASI BULAN
========================== */

if($bulan_awal != "" && $bulan_akhir != ""){

    if($bulan_awal > $bulan_akhir){

        echo "<script>
        alert('Bulan awal tidak boleh lebih besar dari bulan akhir.');
        history.back();
        </script>";

        exit;

    }

}


/* ==========================
   VALIDASI LOKASI DUPLIKAT
========================== */

$cek = mysqli_query($koneksi,"
SELECT *
FROM lokasi_rak
WHERE
id_rak='$id_rak'
AND baris='$baris'
AND kolom='$kolom'
AND id_lokasi<>'$id_lokasi'
");

if(mysqli_num_rows($cek)>0){

    echo "<script>
    alert('Lokasi rak tersebut sudah digunakan.');
    history.back();
    </script>";

    exit;

}


/* ==========================
   UPDATE DATA
========================== */

mysqli_query($koneksi,"
UPDATE lokasi_rak
SET

id_rak='$id_rak',
baris='$baris',
kolom='$kolom',
kapasitas='$kapasitas',
tahun_awal='$tahun_awal',
tahun_akhir='$tahun_akhir',
bulan_awal='$bulan_awal',
bulan_akhir='$bulan_akhir'

WHERE id_lokasi='$id_lokasi'
");


echo "<script>

alert('Data berhasil diperbarui.');

window.location='index.php';

</script>";

?>