<?php

include "../config/koneksi.php";
include "../template/session.php";
wajibLevel(["admin"]);
include "../template/header.php";
include "../template/sidebar.php";

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

            <h3>Tambah Lokasi Rak</h3>

            <form action="simpan.php" method="POST">

                <div class="form-group">

                    <label>Rak</label>

                    <select name="id_rak" class="form-control" required>

                        <option value="">-- Pilih Rak --</option>

                        <?php while($r=mysqli_fetch_assoc($rak)){ ?>

                        <option value="<?= $r['id_rak']; ?>">
                            Rak <?= $r['nomor_rak']; ?> (<?= $r['jenis']; ?>)
                        </option>

                        <?php } ?>

                    </select>

                </div>

                <div class="grid-3">

                    <div class="form-group">
                        <label>Baris</label>
                        <input type="number"
                               name="baris"
                               class="form-control"
                               min="1"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Kolom</label>
                        <input type="number"
                               name="kolom"
                               class="form-control"
                               min="1"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Kapasitas</label>
                        <input type="number"
                               name="kapasitas"
                               class="form-control"
                               value="200"
                               required
                               readonly>
                    </div>

                </div>

                <div class="grid-2">

                    <div class="form-group">
                        <label>Tahun Awal</label>
                        <input type="number"
                               name="tahun_awal"
                               class="form-control"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Tahun Akhir</label>
                        <input type="number"
                               name="tahun_akhir"
                               class="form-control"
                               required>
                    </div>

                </div>

                <div class="grid-2">

                    <div class="form-group">
                        <label>Bulan Awal</label>
                        <input type="number"
                               name="bulan_awal"
                               class="form-control"
                               min="1"
                               max="12"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Bulan Akhir</label>
                        <input type="number"
                               name="bulan_akhir"
                               class="form-control"
                               min="1"
                               max="12"
                               required>
                    </div>

                </div>

                <div class="button-group">
                 
                    <button type="submit" class="btn btn-primary">
                        Simpan
                    </button>

                    <a href="index.php" class="btn btn-warning">
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