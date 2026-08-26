<?php

include "../config/koneksi.php";
include "../template/session.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$idArsip      = filter_input(
    INPUT_POST,
    "id_arsip",
    FILTER_VALIDATE_INT
);

$namaPeminjam = trim($_POST["nama_peminjam"] ?? "");
$nip          = preg_replace(
    "/\s+/",
    "",
    trim($_POST["nip"] ?? "")
);

$keperluan = trim($_POST["keperluan"] ?? "");

if (
    !$idArsip ||
    $namaPeminjam === "" ||
    $nip === "" ||
    $keperluan === ""
) {
    $_SESSION["pinjam_error"] =
        "Semua data peminjaman wajib diisi.";

    header("Location: index.php");
    exit;
}

if (!preg_match("/^[0-9]{5,30}$/", $nip)) {
    $_SESSION["pinjam_error"] =
        "NIP hanya boleh berisi angka.";

    header("Location: index.php");
    exit;
}

if (
    strlen($namaPeminjam) > 150 ||
    strlen($keperluan) > 500
) {
    $_SESSION["pinjam_error"] =
        "Data peminjaman terlalu panjang.";

    header("Location: index.php");
    exit;
}

mysqli_begin_transaction($koneksi);

try {

    /*
     * Ambil dan kunci data arsip.
     */
    $stmtArsip = mysqli_prepare(
        $koneksi,
        "
        SELECT id_arsip, status
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
    $arsip      = mysqli_fetch_assoc($hasilArsip);

    mysqli_stmt_close($stmtArsip);

    if (!$arsip) {
        throw new Exception("Data arsip tidak ditemukan.");
    }

    if ($arsip["status"] !== "Tersedia") {
        throw new Exception(
            "Arsip sedang dipinjam dan tidak dapat dipinjam kembali."
        );
    }

    /*
     * Pastikan tidak ada peminjaman aktif.
     */
    $stmtAktif = mysqli_prepare(
        $koneksi,
        "
        SELECT id_peminjaman
        FROM peminjaman
        WHERE
            id_arsip = ?
            AND status = 'Dipinjam'
        LIMIT 1
        FOR UPDATE
        "
    );

    if (!$stmtAktif) {
        throw new Exception(
            "Gagal memeriksa peminjaman aktif."
        );
    }

    mysqli_stmt_bind_param(
        $stmtAktif,
        "i",
        $idArsip
    );

    if (!mysqli_stmt_execute($stmtAktif)) {
        throw new Exception(
            "Gagal memeriksa peminjaman aktif."
        );
    }

    $hasilAktif = mysqli_stmt_get_result($stmtAktif);

    if (mysqli_num_rows($hasilAktif) > 0) {
        throw new Exception(
            "Arsip sudah memiliki transaksi peminjaman aktif."
        );
    }

    mysqli_stmt_close($stmtAktif);

    /*
     * Simpan transaksi peminjaman.
     */
    $stmtPinjam = mysqli_prepare(
        $koneksi,
        "
        INSERT INTO peminjaman
        (
            id_arsip,
            nama_peminjam,
            nip_peminjam,
            keperluan,
            tanggal_pinjam,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            CURDATE(),
            'Dipinjam'
        )
        "
    );

    if (!$stmtPinjam) {
        throw new Exception(
            "Gagal menyiapkan transaksi peminjaman."
        );
    }

    mysqli_stmt_bind_param(
        $stmtPinjam,
        "isss",
        $idArsip,
        $namaPeminjam,
        $nip,
        $keperluan
    );

    if (!mysqli_stmt_execute($stmtPinjam)) {
        throw new Exception(
            "Gagal menyimpan transaksi peminjaman."
        );
    }

    mysqli_stmt_close($stmtPinjam);

    /*
     * Perbarui status arsip.
     */
    $stmtStatus = mysqli_prepare(
        $koneksi,
        "
        UPDATE arsip
        SET status = 'Dipinjam'
        WHERE
            id_arsip = ?
            AND status = 'Tersedia'
        "
    );

    if (!$stmtStatus) {
        throw new Exception(
            "Gagal memperbarui status arsip."
        );
    }

    mysqli_stmt_bind_param(
        $stmtStatus,
        "i",
        $idArsip
    );

    if (!mysqli_stmt_execute($stmtStatus)) {
        throw new Exception(
            "Gagal memperbarui status arsip."
        );
    }

    if (mysqli_stmt_affected_rows($stmtStatus) !== 1) {
        throw new Exception(
            "Status arsip sudah berubah. Silakan ulangi proses."
        );
    }

    mysqli_stmt_close($stmtStatus);

    mysqli_commit($koneksi);

    $_SESSION["pinjam_success"] = true;

    header("Location: index.php");
    exit;

} catch (Throwable $error) {

    mysqli_rollback($koneksi);

    $_SESSION["pinjam_error"] = $error->getMessage();

    header("Location: index.php");
    exit;
}