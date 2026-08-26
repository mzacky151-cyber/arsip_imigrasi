<?php

include "../config/koneksi.php";
include "../template/session.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$idPeminjaman = filter_input(
    INPUT_POST,
    "id_peminjaman",
    FILTER_VALIDATE_INT
);

if (!$idPeminjaman) {
    $_SESSION["kembali_error"] =
        "Data peminjaman tidak valid.";

    header("Location: index.php");
    exit;
}

mysqli_begin_transaction($koneksi);

try {

    /*
     * Ambil dan kunci transaksi peminjaman.
     */
    $stmtPinjam = mysqli_prepare(
        $koneksi,
        "
        SELECT
            id_peminjaman,
            id_arsip,
            status
        FROM peminjaman
        WHERE id_peminjaman = ?
        LIMIT 1
        FOR UPDATE
        "
    );

    if (!$stmtPinjam) {
        throw new Exception(
            "Gagal memeriksa transaksi peminjaman."
        );
    }

    mysqli_stmt_bind_param(
        $stmtPinjam,
        "i",
        $idPeminjaman
    );

    if (!mysqli_stmt_execute($stmtPinjam)) {
        throw new Exception(
            "Gagal memeriksa transaksi peminjaman."
        );
    }

    $hasilPinjam = mysqli_stmt_get_result($stmtPinjam);
    $peminjaman  = mysqli_fetch_assoc($hasilPinjam);

    mysqli_stmt_close($stmtPinjam);

    if (!$peminjaman) {
        throw new Exception(
            "Transaksi peminjaman tidak ditemukan."
        );
    }

    if ($peminjaman["status"] !== "Dipinjam") {
        throw new Exception(
            "Arsip pada transaksi ini sudah dikembalikan."
        );
    }

    $idArsip = (int) $peminjaman["id_arsip"];

    /*
     * Kunci data arsip.
     */
    $stmtArsip = mysqli_prepare(
        $koneksi,
        "
        SELECT status
        FROM arsip
        WHERE id_arsip = ?
        LIMIT 1
        FOR UPDATE
        "
    );

    if (!$stmtArsip) {
        throw new Exception("Gagal memeriksa status arsip.");
    }

    mysqli_stmt_bind_param(
        $stmtArsip,
        "i",
        $idArsip
    );

    if (!mysqli_stmt_execute($stmtArsip)) {
        throw new Exception("Gagal memeriksa status arsip.");
    }

    $hasilArsip = mysqli_stmt_get_result($stmtArsip);
    $arsip      = mysqli_fetch_assoc($hasilArsip);

    mysqli_stmt_close($stmtArsip);

    if (!$arsip) {
        throw new Exception("Data arsip tidak ditemukan.");
    }

    /*
     * Perbarui transaksi peminjaman.
     */
    $stmtKembali = mysqli_prepare(
        $koneksi,
        "
        UPDATE peminjaman
        SET
            status = 'Dikembalikan',
            tanggal_kembali = CURDATE()
        WHERE
            id_peminjaman = ?
            AND status = 'Dipinjam'
        "
    );

    if (!$stmtKembali) {
        throw new Exception(
            "Gagal menyiapkan proses pengembalian."
        );
    }

    mysqli_stmt_bind_param(
        $stmtKembali,
        "i",
        $idPeminjaman
    );

    if (!mysqli_stmt_execute($stmtKembali)) {
        throw new Exception(
            "Gagal menyimpan pengembalian."
        );
    }

    if (mysqli_stmt_affected_rows($stmtKembali) !== 1) {
        throw new Exception(
            "Status peminjaman sudah berubah."
        );
    }

    mysqli_stmt_close($stmtKembali);

    /*
     * Perbarui status arsip.
     */
    $stmtStatus = mysqli_prepare(
        $koneksi,
        "
        UPDATE arsip
        SET status = 'Tersedia'
        WHERE
            id_arsip = ?
            AND status = 'Dipinjam'
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
            "Status arsip tidak sesuai dengan transaksi peminjaman."
        );
    }

    mysqli_stmt_close($stmtStatus);

    mysqli_commit($koneksi);

    $_SESSION["kembali_success"] = true;

    header("Location: index.php");
    exit;

} catch (Throwable $error) {

    mysqli_rollback($koneksi);

    $_SESSION["kembali_error"] = $error->getMessage();

    header("Location: index.php");
    exit;
}