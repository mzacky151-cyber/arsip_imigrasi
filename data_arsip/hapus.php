<?php

include "../config/koneksi.php";
include "../template/session.php";

wajibLevel(["admin"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    $_SESSION["arsip_action_error"] =
        "Metode penghapusan tidak valid.";

    header("Location: index.php");
    exit;
}

$tokenCsrf = $_POST["csrf_token"] ?? "";

if (!verifikasiCsrf($tokenCsrf)) {

    $_SESSION["arsip_action_error"] =
        "Permintaan penghapusan tidak valid. Silakan ulangi.";

    header("Location: index.php");
    exit;
}

$idArsip = filter_input(
    INPUT_POST,
    "id_arsip",
    FILTER_VALIDATE_INT
);

if (!$idArsip) {

    $_SESSION["arsip_action_error"] =
        "ID arsip tidak valid.";

    header("Location: index.php");
    exit;
}

mysqli_begin_transaction($koneksi);

try {

    /*
    |--------------------------------------------------------------------------
    | Ambil dan kunci arsip
    |--------------------------------------------------------------------------
    */

    $stmtArsip = mysqli_prepare(
        $koneksi,
        "
        SELECT
            id_arsip,
            id_lokasi,
            status
        FROM arsip
        WHERE id_arsip = ?
        LIMIT 1
        FOR UPDATE
        "
    );

    if (!$stmtArsip) {
        throw new Exception("Gagal memeriksa data arsip.");
    }

    mysqli_stmt_bind_param(
        $stmtArsip,
        "i",
        $idArsip
    );

    if (!mysqli_stmt_execute($stmtArsip)) {
        throw new Exception("Gagal memeriksa data arsip.");
    }

    $hasilArsip = mysqli_stmt_get_result($stmtArsip);
    $arsip = mysqli_fetch_assoc($hasilArsip);

    mysqli_stmt_close($stmtArsip);

    if (!$arsip) {
        throw new Exception("Data arsip tidak ditemukan.");
    }

    if ($arsip["status"] !== "Tersedia") {
        throw new Exception(
            "Arsip yang sedang dipinjam tidak dapat dihapus."
        );
    }

    $idLokasi = (int) $arsip["id_lokasi"];

    /*
    |--------------------------------------------------------------------------
    | Periksa riwayat peminjaman
    |--------------------------------------------------------------------------
    */

    $stmtRiwayat = mysqli_prepare(
        $koneksi,
        "
        SELECT id_peminjaman
        FROM peminjaman
        WHERE id_arsip = ?
        LIMIT 1
        "
    );

    if (!$stmtRiwayat) {
        throw new Exception(
            "Gagal memeriksa riwayat peminjaman."
        );
    }

    mysqli_stmt_bind_param(
        $stmtRiwayat,
        "i",
        $idArsip
    );

    if (!mysqli_stmt_execute($stmtRiwayat)) {
        throw new Exception(
            "Gagal memeriksa riwayat peminjaman."
        );
    }

    $hasilRiwayat = mysqli_stmt_get_result($stmtRiwayat);
    $punyaRiwayat = mysqli_num_rows($hasilRiwayat) > 0;

    mysqli_stmt_close($stmtRiwayat);

    if ($punyaRiwayat) {
        throw new Exception(
            "Arsip tidak dapat dihapus karena memiliki riwayat peminjaman."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Hapus arsip
    |--------------------------------------------------------------------------
    */

    $stmtHapus = mysqli_prepare(
        $koneksi,
        "
        DELETE FROM arsip
        WHERE
            id_arsip = ?
            AND status = 'Tersedia'
        "
    );

    if (!$stmtHapus) {
        throw new Exception(
            "Gagal menyiapkan penghapusan arsip."
        );
    }

    mysqli_stmt_bind_param(
        $stmtHapus,
        "i",
        $idArsip
    );

    if (!mysqli_stmt_execute($stmtHapus)) {
        throw new Exception("Data arsip gagal dihapus.");
    }

    if (mysqli_stmt_affected_rows($stmtHapus) !== 1) {
        throw new Exception(
            "Data arsip tidak berhasil dihapus."
        );
    }

    mysqli_stmt_close($stmtHapus);

    /*
    |--------------------------------------------------------------------------
    | Hitung ulang kapasitas lokasi
    |--------------------------------------------------------------------------
    */

    $stmtJumlah = mysqli_prepare(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM arsip
        WHERE id_lokasi = ?
        "
    );

    if (!$stmtJumlah) {
        throw new Exception(
            "Gagal menghitung kapasitas lokasi."
        );
    }

    mysqli_stmt_bind_param(
        $stmtJumlah,
        "i",
        $idLokasi
    );

    if (!mysqli_stmt_execute($stmtJumlah)) {
        throw new Exception(
            "Gagal menghitung kapasitas lokasi."
        );
    }

    $hasilJumlah = mysqli_stmt_get_result($stmtJumlah);
    $jumlahArsip = mysqli_fetch_assoc($hasilJumlah);

    mysqli_stmt_close($stmtJumlah);

    $totalTerisi = (int) $jumlahArsip["total"];

    $stmtKapasitas = mysqli_prepare(
        $koneksi,
        "
        UPDATE lokasi_rak
        SET kapasitas_terisi = ?
        WHERE id_lokasi = ?
        "
    );

    if (!$stmtKapasitas) {
        throw new Exception(
            "Gagal memperbarui kapasitas lokasi."
        );
    }

    mysqli_stmt_bind_param(
        $stmtKapasitas,
        "ii",
        $totalTerisi,
        $idLokasi
    );

    if (!mysqli_stmt_execute($stmtKapasitas)) {
        throw new Exception(
            "Gagal memperbarui kapasitas lokasi."
        );
    }

    mysqli_stmt_close($stmtKapasitas);

    mysqli_commit($koneksi);

    $_SESSION["delete_success"] = true;

    header("Location: index.php");
    exit;

} catch (Throwable $error) {

    mysqli_rollback($koneksi);

    $_SESSION["arsip_action_error"] =
        $error->getMessage();

    header("Location: index.php");
    exit;
}