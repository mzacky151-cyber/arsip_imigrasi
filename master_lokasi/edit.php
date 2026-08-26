<?php

include "../config/koneksi.php";
include "../template/session.php";

wajibLevel(["admin"]);

include "../template/header.php";
include "../template/sidebar.php";

$id = $_GET['id'];

$data = mysqli_query($koneksi,"
SELECT *
FROM lokasi_rak
WHERE id_lokasi='$id'
");

$d = mysqli_fetch_assoc($data);

$rak = mysqli_query($koneksi,"
SELECT *
FROM rak
WHERE status='Aktif'
ORDER BY nomor_rak
");

?>

<div class="main">

<?php include "../template/topbar.php"; ?>

    <div class="content">

        <div class="card">

            <h2>Edit Lokasi Rak</h2>

            <form action="update.php" method="POST">

                <input type="hidden"
                       name="id_lokasi"
                       value="<?= $d['id_lokasi']; ?>">

                <div class="form-group">

                    <label>Rak</label>

                    <select
                        name="id_rak"
                        class="form-control"
                        required>

                        <?php while($r=mysqli_fetch_assoc($rak)){ ?>

                        <option
                            value="<?= $r['id_rak']; ?>"
                            <?= ($r['id_rak']==$d['id_rak']) ? "selected" : ""; ?>>

                            Rak <?= $r['nomor_rak']; ?>
                            (<?= $r['jenis']; ?>)

                        </option>

                        <?php } ?>

                    </select>

                </div>

                <div class="grid-3">

                    <div class="form-group">

                        <label>Baris</label>

                        <input
                            type="number"
                            name="baris"
                            class="form-control"
                            value="<?= $d['baris']; ?>"
                            min="1"
                            required>

                    </div>

                    <div class="form-group">

                        <label>Kolom</label>

                        <input
                            type="number"
                            name="kolom"
                            class="form-control"
                            value="<?= $d['kolom']; ?>"
                            min="1"
                            required>

                    </div>

                    <div class="form-group">

                        <label>Kapasitas</label>

                        <input
                            type="number"
                            name="kapasitas"
                            class="form-control"
                            value="<?= $d['kapasitas']; ?>"
                            readonly>

                    </div>

                </div>

                <div class="grid-2">

                    <div class="form-group">

                        <label>Tahun Awal</label>

                        <input
                            type="number"
                            name="tahun_awal"
                            class="form-control"
                            value="<?= $d['tahun_awal']; ?>"
                            required>

                    </div>

                    <div class="form-group">

                        <label>Tahun Akhir</label>

                        <input
                            type="number"
                            name="tahun_akhir"
                            class="form-control"
                            value="<?= $d['tahun_akhir']; ?>">

                    </div>

                </div>

                <div class="grid-2">

                    <div class="form-group">

                        <label>Bulan Awal</label>

                        <input
                            type="number"
                            name="bulan_awal"
                            class="form-control"
                            value="<?= $d['bulan_awal']; ?>"
                            min="1"
                            max="12">

                    </div>

                    <div class="form-group">

                        <label>Bulan Akhir</label>

                        <input
                            type="number"
                            name="bulan_akhir"
                            class="form-control"
                            value="<?= $d['bulan_akhir']; ?>"
                            min="1"
                            max="12">

                    </div>

                </div>

                <div class="button-group">

                    <button
                        type="submit"
                        class="btn btn-primary">

                        Simpan Perubahan

                    </button>

                    <a
                        href="index.php"
                        class="btn btn-warning">

                        Kembali

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

<?php
include "../template/footer.php";
?>