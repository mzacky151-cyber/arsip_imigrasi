<?php

include "../config/koneksi.php";
include "../template/session.php";

$idArsip = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$idArsip) {
    $_SESSION["arsip_action_error"] = "ID arsip tidak valid.";

    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "
    SELECT
        arsip.*,
        lokasi_rak.baris,
        lokasi_rak.kolom,
        rak.nomor_rak
    FROM arsip
    JOIN lokasi_rak
        ON arsip.id_lokasi = lokasi_rak.id_lokasi
    JOIN rak
        ON lokasi_rak.id_rak = rak.id_rak
    WHERE arsip.id_arsip = ?
    LIMIT 1
    "
);

if (!$stmt) {
    $_SESSION["arsip_action_error"] =
        "Gagal menyiapkan data arsip.";

    header("Location: index.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $idArsip);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    $_SESSION["arsip_action_error"] =
        "Gagal mengambil data arsip.";

    header("Location: index.php");
    exit;
}

$hasil = mysqli_stmt_get_result($stmt);
$data  = mysqli_fetch_assoc($hasil);

mysqli_stmt_close($stmt);

if (!$data) {
    $_SESSION["arsip_action_error"] =
        "Data arsip tidak ditemukan.";

    header("Location: index.php");
    exit;
}

include "../template/header.php";
include "../template/sidebar.php";
?>

<div class="main">

<?php include "../template/topbar.php"; ?>

    <div class="content">

        <div class="card arsip-form-card">

            <div class="arsip-form-header">

                <h3>Edit Data Arsip</h3>

                <p>
                    Perbarui nomor permohonan atau nama pemilik arsip.
                </p>

            </div>

            <form
                action="update.php"
                method="POST"
                class="arsip-form">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        tokenCsrf(),
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>">

                <input
                    type="hidden"
                    name="id_arsip"
                    value="<?= (int) $data["id_arsip"]; ?>">

                <div class="form-group">

                    <label>No Permohonan</label>

                    <input
                        type="text"
                        name="nomor_permohonan"
                        class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars(
                            $data["nomor_permohonan"],
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        required>

                </div>

                <div class="form-group">

                    <label>Nama</label>

                    <input
                        type="text"
                        name="nama"
                        class="form-control"
                        maxlength="150"
                        value="<?= htmlspecialchars(
                            $data["nama"],
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        required>

                </div>

                <div class="grid-2">

                    <div class="form-group">

                        <label>Tanggal Lahir</label>

                        <input
                            type="date"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $data["tanggal_lahir"],
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            readonly>

                    </div>

                    <div class="form-group">

                        <label>Kewarganegaraan</label>

                        <input
                            type="text"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $data["kewarganegaraan"],
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            readonly>

                    </div>

                </div>

                <div class="form-group">

                    <label>Status Arsip</label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $data["status"],
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        readonly>

                </div>

                <div class="arsip-location-box">
                    <h4 style="margin-bottom:15px;color:#123458;">
                        Lokasi Penyimpanan
                    </h4>

                    <div class="grid-2">

                        <div>

                            <p>
                                <b>Rak</b>:
                                <?= htmlspecialchars(
                                    $data["nomor_rak"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>
                            </p>

                            <p>
                                <b>Baris</b>:
                                <?= (int) $data["baris"]; ?>
                            </p>

                        </div>

                        <div>

                            <p>
                                <b>Kolom</b>:
                                <?= (int) $data["kolom"]; ?>
                            </p>

                            <p>
                                <b>Nomor Urut</b>:
                                <?= (int) $data["nomor_urut"]; ?>
                            </p>

                        </div>

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

<?php include "../template/footer.php"; ?>