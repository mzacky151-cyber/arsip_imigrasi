<?php
include "../config/koneksi.php";
include "../template/session.php";
include "../template/header.php";
include "../template/sidebar.php";
?>

        <div class="main">

        <?php include "../template/topbar.php"; ?>

        <div class="content">

        <div class="card arsip-form-card">

            <div class="arsip-form-header">

                <h3>Tambah Arsip</h3>

                <p>
                    Masukkan data pemohon untuk menentukan lokasi
                    penyimpanan arsip secara otomatis.
                </p>

            </div>

            <form
                action="simpan.php"
                method="POST"
                class="arsip-form">

            <div class="form-group">
            <label>No Permohonan</label>
            <input type="text" name="nomor_permohonan" class="form-control"
            required>
            </div>

            <div class="form-group">
            <label>Nama</label>
            <input type="text" name="nama" class="form-control" required>
            </div>

            <div class="grid-2">

            <div class="form-group">
            <label>Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir" class="form-control" required>
            </div>

            <div class="form-group">
            <label>Kewarganegaraan</label>

            <select name="kewarganegaraan" class="form-control" required>
            <option value="">-- Pilih --</option>
            <option value="WNI">WNI</option>
            <option value="WNA">WNA</option>
            </select>

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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php

$notifikasi = null;

if (isset($_SESSION["success"], $_SESSION["lokasi"])) {

    $lokasi = $_SESSION["lokasi"];

    $rak = htmlspecialchars(
        (string) $lokasi["rak"],
        ENT_QUOTES,
        "UTF-8"
    );

    $baris = htmlspecialchars(
        (string) $lokasi["baris"],
        ENT_QUOTES,
        "UTF-8"
    );

    $kolom = htmlspecialchars(
        (string) $lokasi["kolom"],
        ENT_QUOTES,
        "UTF-8"
    );

    $nomorUrut = htmlspecialchars(
        (string) $lokasi["nomor_urut"],
        ENT_QUOTES,
        "UTF-8"
    );

    $notifikasi = [
        "icon"  => "success",
        "title" => "Arsip Berhasil Disimpan",
        "html"  => "
            <p style='color:#666;margin-bottom:18px;'>
                Simpan berkas pada lokasi berikut.
            </p>

            <div style='
                background:#F8FAFC;
                border:1px solid #E5E7EB;
                border-radius:12px;
                padding:18px;
                text-align:left;
                line-height:2;
            '>
                <b>Rak</b> : {$rak}<br>
                <b>Baris</b> : {$baris}<br>
                <b>Kolom</b> : {$kolom}<br>
                <b>Nomor Urut</b> : {$nomorUrut}
            </div>
        "
    ];

    unset(
        $_SESSION["success"],
        $_SESSION["lokasi"]
    );

} elseif (isset($_SESSION["arsip_error"])) {

    $notifikasi = [
        "icon"  => "error",
        "title" => "Arsip Gagal Disimpan",
        "text"  => $_SESSION["arsip_error"]
    ];

    unset($_SESSION["arsip_error"]);
}

?>

<?php if ($notifikasi !== null) : ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const notifikasi = <?= json_encode(
        $notifikasi,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ); ?>;

    Swal.fire({
        icon: notifikasi.icon,
        title: notifikasi.title,
        text: notifikasi.text || undefined,
        html: notifikasi.html || undefined,
        width: 430,
        confirmButtonText: "Tutup",
        confirmButtonColor: "#123458",
        allowOutsideClick: false,
        customClass: {
            popup: "rounded-popup",
            title: "popup-title",
            confirmButton: "popup-button"
        }
    });

});
</script>

<?php endif; ?>

<?php include "../template/footer.php"; ?>