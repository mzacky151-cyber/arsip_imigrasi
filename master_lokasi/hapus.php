<?php

include "../config/koneksi.php";
include "../template/session.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
$rak = filter_input(INPUT_GET, "rak", FILTER_VALIDATE_INT);
$halamanRak = filter_input(INPUT_GET, "halaman_rak", FILTER_VALIDATE_INT);

if (!$halamanRak || $halamanRak < 1) {
    $halamanRak = 1;
}

$redirect = "index.php";

$queryString = http_build_query([
    "rak" => $rak,
    "halaman_rak" => $halamanRak
]);

if ($queryString !== "") {
    $redirect .= "?" . $queryString;
}

if (!$id) {
    echo "
    <script>
    alert('ID lokasi tidak valid.');
    window.location='{$redirect}';
    </script>
    ";
    exit;
}

/* cek apakah lokasi masih digunakan arsip */
$cek = mysqli_query($koneksi, "
    SELECT *
    FROM arsip
    WHERE id_lokasi='$id'
");

if (mysqli_num_rows($cek) > 0) {

    echo "
    <script>
    alert('Lokasi rak tidak dapat dihapus karena masih digunakan oleh arsip.');
    window.location='{$redirect}';
    </script>
    ";
    exit;
}

/* hapus lokasi */
mysqli_query($koneksi, "
    DELETE FROM lokasi_rak
    WHERE id_lokasi='$id'
");

echo "
<script>
alert('Lokasi rak berhasil dihapus.');
window.location='{$redirect}';
</script>
";