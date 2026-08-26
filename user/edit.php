<?php

include "../config/koneksi.php";
include "../template/session.php";

wajibLevel(["admin"]);

include "../template/header.php";
include "../template/sidebar.php";


$id = $_GET['id'];


$data = mysqli_query($koneksi,"
SELECT *
FROM user
WHERE id_user='$id'
");


$d = mysqli_fetch_assoc($data);


if(!$d){

    echo "
    <script>
    alert('Data user tidak ditemukan');
    window.location='index.php';
    </script>
    ";

    exit;

}

?>


<div class="main">


<?php include "../template/topbar.php"; ?>



<div class="content">


<div class="card">


<h3>Edit User</h3>


<form action="update.php" method="POST">


<input
type="hidden"
name="id_user"
value="<?= $d['id_user']; ?>">



<div class="form-group">

<label>Nama</label>

<input
type="text"
name="nama"
class="form-control"
value="<?= htmlspecialchars($d['nama']); ?>"
required>

</div>

<div class="form-group">

    <label>NIP Pegawai</label>

    <input
        type="text"
        name="nip"
        class="form-control"
        maxlength="30"
        inputmode="numeric"
        value="<?= htmlspecialchars(
            $d["nip"] ?? "",
            ENT_QUOTES,
            "UTF-8"
        ); ?>"
        required>

</div>

<div class="form-group">

<label>Username</label>

<input
type="text"
name="username"
class="form-control"
value="<?= htmlspecialchars($d['username']); ?>"
required>

</div>



<div class="form-group">

<label>Password Baru</label>

<input
type="password"
name="password"
class="form-control"
placeholder="Kosongkan jika tidak diganti">

</div>



<div class="form-group">

<label>Level</label>


<select
name="level"
class="form-control"
required>


<option value="admin"
<?= $d['level']=="admin" ? "selected" : ""; ?>>

Admin

</option>


<option value="petugas"
<?= $d['level']=="petugas" ? "selected" : ""; ?>>

Petugas

</option>


</select>


</div>



<div class="button-group">


<button
type="submit"
class="btn btn-primary">

Simpan Perubahan

</button>


<a href="index.php"
class="btn btn-warning">

Kembali

</a>


</div>


</form>


</div>


</div>


</div>



<?php include "../template/footer.php"; ?>