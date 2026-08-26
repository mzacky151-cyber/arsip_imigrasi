<?php

include "../config/koneksi.php";
include "../template/session.php";

wajibLevel(["admin"]);

include "../template/header.php";
include "../template/sidebar.php";

?>


<div class="main">


<?php include "../template/topbar.php"; ?>


<div class="content">


<div class="card">


<h3>Tambah User</h3>


<form action="simpan.php" method="POST">


<div class="form-group">

<label>Nama</label>

<input
type="text"
name="nama"
class="form-control"
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
placeholder="Masukkan NIP pegawai"
required>

</div>

<div class="form-group">

<label>Username</label>

<input
type="text"
name="username"
class="form-control"
required>

</div>



<div class="form-group">

<label>Password</label>

<input
type="password"
name="password"
class="form-control"
required>

</div>



<div class="form-group">

<label>Level</label>

<select name="level"
class="form-control"
required>

<option value="petugas">
Petugas
</option>

<option value="admin">
Admin
</option>


</select>


</div>


<div class="button-group">


<button class="btn btn-primary">
Simpan
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