<?php

include "../config/koneksi.php";
include "../template/session.php";

wajibLevel(["admin"]);


$id = $_GET['id'];



/*
CEK JANGAN HAPUS DIRI SENDIRI
*/

if($id == $_SESSION['id_user']){


echo "
<script>

alert('Akun yang sedang digunakan tidak dapat dihapus.');

window.location='index.php';

</script>
";


exit;

}



/*
CEK DATA USER
*/

$cek = mysqli_query($koneksi,"
SELECT *
FROM user
WHERE id_user='$id'
");


if(mysqli_num_rows($cek)==0){


echo "
<script>

alert('User tidak ditemukan.');

window.location='index.php';

</script>
";


exit;

}




mysqli_query($koneksi,"
DELETE FROM user
WHERE id_user='$id'
");



echo "

<script>

alert('User berhasil dihapus.');

window.location='index.php';

</script>

";