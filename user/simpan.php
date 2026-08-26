<?php

include "../config/koneksi.php";
include "../template/session.php";

wajibLevel(["admin"]);


$nama = trim($_POST['nama']);
$nip = trim($_POST["nip"]);
$username = trim($_POST['username']);
$password = $_POST['password'];
$level = $_POST['level'];



$passwordHash = password_hash(
$password,
PASSWORD_DEFAULT
);


$cek = mysqli_prepare(
    $koneksi,
    "
    SELECT id_user
    FROM user
    WHERE username = ?
       OR nip = ?
    LIMIT 1
    "
);

mysqli_stmt_bind_param(
    $cek,
    "ss",
    $username,
    $nip
);

mysqli_stmt_execute($cek);

$hasilCek = mysqli_stmt_get_result($cek);

if (mysqli_num_rows($hasilCek) > 0) {

    echo "
    <script>
        alert('Username atau NIP sudah digunakan.');
        history.back();
    </script>
    ";

    exit;
}



$query=mysqli_query($koneksi,"
INSERT INTO user
(
nama,
nip,
username,
password,
level
)

VALUES
(
'$nama',
'$nip',
'$username',
'$passwordHash',
'$level'
)

");


if(!$query){

die(mysqli_error($koneksi));

}



header("Location:index.php");
exit;