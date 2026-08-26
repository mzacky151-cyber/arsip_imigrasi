<?php

include "../config/koneksi.php";
include "../template/session.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$tokenCsrf = $_POST["csrf_token"] ?? null;

if (!verifikasiCsrf($tokenCsrf)) {
    $_SESSION["arsip_action_error"] =
        "Permintaan tidak valid. Silakan ulangi proses.";

    header("Location: index.php");
    exit;
}

$idArsip = filter_input(
    INPUT_POST,
    "id_arsip",
    FILTER_VALIDATE_INT
);

$nomorPermohonan = trim(
    $_POST["nomor_permohonan"] ?? ""
);

$nama = trim($_POST["nama"] ?? "");

if (!$idArsip) {
    $_SESSION["arsip_action_error"] =
        "ID arsip tidak valid.";

    header("Location: index.php");
    exit;
}

if ($nomorPermohonan === "" || $nama === "") {
    $_SESSION["arsip_action_error"] =
        "Nomor permohonan dan nama wajib diisi.";

    header("Location: edit.php?id=" . $idArsip);
    exit;
}

if (
    mb_strlen($nomorPermohonan) > 100 ||
    mb_strlen($nama) > 150
) {
    $_SESSION["arsip_action_error"] =
        "Data yang dimasukkan terlalu panjang.";

    header("Location: edit.php?id=" . $idArsip);
    exit;
}

/*
|--------------------------------------------------------------------------
| Pastikan arsip tersedia
|--------------------------------------------------------------------------
*/

$stmtArsip = mysqli_prepare(
    $koneksi,
    "
    SELECT id_arsip
    FROM arsip
    WHERE id_arsip = ?
    LIMIT 1
    "
);

if (!$stmtArsip) {
    $_SESSION["arsip_action_error"] =
        "Gagal memeriksa data arsip.";

    header("Location: index.php");
    exit;
}

mysqli_stmt_bind_param(
    $stmtArsip,
    "i",
    $idArsip
);

mysqli_stmt_execute($stmtArsip);

$hasilArsip = mysqli_stmt_get_result($stmtArsip);
$arsip      = mysqli_fetch_assoc($hasilArsip);

mysqli_stmt_close($stmtArsip);

if (!$arsip) {
    $_SESSION["arsip_action_error"] =
        "Data arsip tidak ditemukan.";

    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Cek nomor permohonan duplikat
|--------------------------------------------------------------------------
*/

$stmtCek = mysqli_prepare(
    $koneksi,
    "
    SELECT id_arsip
    FROM arsip
    WHERE
        nomor_permohonan = ?
        AND id_arsip <> ?
    LIMIT 1
    "
);

if (!$stmtCek) {
    $_SESSION["arsip_action_error"] =
        "Gagal memeriksa nomor permohonan.";

    header("Location: edit.php?id=" . $idArsip);
    exit;
}

mysqli_stmt_bind_param(
    $stmtCek,
    "si",
    $nomorPermohonan,
    $idArsip
);

mysqli_stmt_execute($stmtCek);

$hasilCek = mysqli_stmt_get_result($stmtCek);

if (mysqli_num_rows($hasilCek) > 0) {
    mysqli_stmt_close($stmtCek);

    $_SESSION["arsip_action_error"] =
        "Nomor permohonan sudah digunakan oleh arsip lain.";

    header("Location: edit.php?id=" . $idArsip);
    exit;
}

mysqli_stmt_close($stmtCek);

/*
|--------------------------------------------------------------------------
| Update data
|--------------------------------------------------------------------------
*/

$stmtUpdate = mysqli_prepare(
    $koneksi,
    "
    UPDATE arsip
    SET
        nomor_permohonan = ?,
        nama = ?
    WHERE id_arsip = ?
    "
);

if (!$stmtUpdate) {
    $_SESSION["arsip_action_error"] =
        "Gagal menyiapkan perubahan data.";

    header("Location: edit.php?id=" . $idArsip);
    exit;
}

mysqli_stmt_bind_param(
    $stmtUpdate,
    "ssi",
    $nomorPermohonan,
    $nama,
    $idArsip
);

if (!mysqli_stmt_execute($stmtUpdate)) {
    mysqli_stmt_close($stmtUpdate);

    $_SESSION["arsip_action_error"] =
        "Data arsip gagal diperbarui.";

    header("Location: edit.php?id=" . $idArsip);
    exit;
}

mysqli_stmt_close($stmtUpdate);

$_SESSION["update_success"] = true;

header("Location: index.php");
exit;