<?php

include "../config/koneksi.php";
include "../template/session.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: tambah.php");
    exit;
}

$nomorPermohonan = trim($_POST["nomor_permohonan"] ?? "");
$nama            = trim($_POST["nama"] ?? "");
$tanggalLahir    = trim($_POST["tanggal_lahir"] ?? "");
$kewarganegaraan = trim($_POST["kewarganegaraan"] ?? "");

if (
    $nomorPermohonan === "" ||
    $nama === "" ||
    $tanggalLahir === "" ||
    $kewarganegaraan === ""
) {
    $_SESSION["arsip_error"] = "Semua data arsip wajib diisi.";

    header("Location: tambah.php");
    exit;
}

if (!in_array($kewarganegaraan, ["WNI", "WNA"], true)) {
    $_SESSION["arsip_error"] = "Kewarganegaraan tidak valid.";

    header("Location: tambah.php");
    exit;
}

$objekTanggal = DateTime::createFromFormat("Y-m-d", $tanggalLahir);

if (
    !$objekTanggal ||
    $objekTanggal->format("Y-m-d") !== $tanggalLahir
) {
    $_SESSION["arsip_error"] = "Tanggal lahir tidak valid.";

    header("Location: tambah.php");
    exit;
}

$hariIni = new DateTime("today");

if ($objekTanggal > $hariIni) {
    $_SESSION["arsip_error"] =
        "Tanggal lahir tidak boleh melebihi tanggal hari ini.";

    header("Location: tambah.php");
    exit;
}

if (strlen($nomorPermohonan) > 100 || strlen($nama) > 150) {
    $_SESSION["arsip_error"] = "Data yang dimasukkan terlalu panjang.";

    header("Location: tambah.php");
    exit;
}

$tahun = (int) $objekTanggal->format("Y");
$bulan = (int) $objekTanggal->format("n");

mysqli_begin_transaction($koneksi);

try {

    /*
     * Cek nomor permohonan.
     */
    $stmtCek = mysqli_prepare(
        $koneksi,
        "
        SELECT id_arsip
        FROM arsip
        WHERE nomor_permohonan = ?
        LIMIT 1
        "
    );

    if (!$stmtCek) {
        throw new Exception("Gagal memeriksa nomor permohonan.");
    }

    mysqli_stmt_bind_param(
        $stmtCek,
        "s",
        $nomorPermohonan
    );

    if (!mysqli_stmt_execute($stmtCek)) {
        throw new Exception("Gagal memeriksa nomor permohonan.");
    }

    $hasilCek = mysqli_stmt_get_result($stmtCek);

    if (mysqli_num_rows($hasilCek) > 0) {
        throw new Exception(
            "Nomor permohonan sudah terdaftar."
        );
    }

    mysqli_stmt_close($stmtCek);

    /*
     * Cari dan kunci lokasi rak yang masih tersedia.
     */
    $stmtLokasi = mysqli_prepare(
        $koneksi,
        "
        SELECT
            lokasi_rak.id_lokasi,
            lokasi_rak.baris,
            lokasi_rak.kolom,
            lokasi_rak.kapasitas,
            lokasi_rak.kapasitas_terisi,
            rak.nomor_rak
        FROM lokasi_rak
        JOIN rak
            ON lokasi_rak.id_rak = rak.id_rak
        WHERE
            rak.jenis = ?
            AND rak.status = 'Aktif'
            AND lokasi_rak.status = 'Aktif'
            AND ? BETWEEN
                lokasi_rak.tahun_awal
                AND lokasi_rak.tahun_akhir
            AND (
                lokasi_rak.bulan_awal IS NULL
                OR lokasi_rak.bulan_awal = 0
                OR ? BETWEEN
                    lokasi_rak.bulan_awal
                    AND lokasi_rak.bulan_akhir
            )
            AND lokasi_rak.kapasitas_terisi
                < lokasi_rak.kapasitas
        ORDER BY
            rak.nomor_rak,
            lokasi_rak.baris,
            lokasi_rak.kolom
        LIMIT 1
        FOR UPDATE
        "
    );

    if (!$stmtLokasi) {
        throw new Exception("Gagal mencari lokasi rak.");
    }

    mysqli_stmt_bind_param(
        $stmtLokasi,
        "sii",
        $kewarganegaraan,
        $tahun,
        $bulan
    );

    if (!mysqli_stmt_execute($stmtLokasi)) {
        throw new Exception("Gagal mencari lokasi rak.");
    }

    $hasilLokasi = mysqli_stmt_get_result($stmtLokasi);
    $lokasi      = mysqli_fetch_assoc($hasilLokasi);

    mysqli_stmt_close($stmtLokasi);

    if (!$lokasi) {
        throw new Exception(
            "Lokasi rak yang sesuai tidak ditemukan atau sudah penuh."
        );
    }

    $idLokasi = (int) $lokasi["id_lokasi"];

    /*
     * Tentukan nomor urut.
     * Baris lokasi sudah dikunci oleh FOR UPDATE.
     */
    $stmtUrut = mysqli_prepare(
        $koneksi,
        "
        SELECT COALESCE(MAX(nomor_urut), 0) + 1
            AS nomor_berikutnya
        FROM arsip
        WHERE id_lokasi = ?
        "
    );

    if (!$stmtUrut) {
        throw new Exception("Gagal menentukan nomor urut.");
    }

    mysqli_stmt_bind_param(
        $stmtUrut,
        "i",
        $idLokasi
    );

    if (!mysqli_stmt_execute($stmtUrut)) {
        throw new Exception("Gagal menentukan nomor urut.");
    }

    $hasilUrut = mysqli_stmt_get_result($stmtUrut);
    $dataUrut  = mysqli_fetch_assoc($hasilUrut);

    mysqli_stmt_close($stmtUrut);

    $nomorUrut = (int) $dataUrut["nomor_berikutnya"];

    /*
     * Simpan arsip.
     */
    $stmtSimpan = mysqli_prepare(
        $koneksi,
        "
        INSERT INTO arsip
        (
            nomor_permohonan,
            nama,
            tanggal_lahir,
            kewarganegaraan,
            id_lokasi,
            nomor_urut,
            tanggal_arsip,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            CURDATE(),
            'Tersedia'
        )
        "
    );

    if (!$stmtSimpan) {
        throw new Exception("Gagal menyiapkan penyimpanan arsip.");
    }

    mysqli_stmt_bind_param(
        $stmtSimpan,
        "ssssii",
        $nomorPermohonan,
        $nama,
        $tanggalLahir,
        $kewarganegaraan,
        $idLokasi,
        $nomorUrut
    );

    if (!mysqli_stmt_execute($stmtSimpan)) {
        throw new Exception("Gagal menyimpan data arsip.");
    }

    mysqli_stmt_close($stmtSimpan);

    /*
     * Perbarui kapasitas lokasi.
     */
    $stmtKapasitas = mysqli_prepare(
        $koneksi,
        "
        UPDATE lokasi_rak
        SET kapasitas_terisi = kapasitas_terisi + 1
        WHERE
            id_lokasi = ?
            AND kapasitas_terisi < kapasitas
        "
    );

    if (!$stmtKapasitas) {
        throw new Exception("Gagal memperbarui kapasitas rak.");
    }

    mysqli_stmt_bind_param(
        $stmtKapasitas,
        "i",
        $idLokasi
    );

    if (!mysqli_stmt_execute($stmtKapasitas)) {
        throw new Exception("Gagal memperbarui kapasitas rak.");
    }

    if (mysqli_stmt_affected_rows($stmtKapasitas) !== 1) {
        throw new Exception(
            "Kapasitas lokasi sudah penuh."
        );
    }

    mysqli_stmt_close($stmtKapasitas);

    mysqli_commit($koneksi);

    $_SESSION["success"] = true;

    $_SESSION["lokasi"] = [
        "rak"              => $lokasi["nomor_rak"],
        "baris"            => $lokasi["baris"],
        "kolom"            => $lokasi["kolom"],
        "nomor_urut"       => $nomorUrut,
        "nama"             => $nama,
        "nomor_permohonan" => $nomorPermohonan
    ];

    header("Location: tambah.php");
    exit;

} catch (Throwable $error) {

    mysqli_rollback($koneksi);

    $_SESSION["arsip_error"] = $error->getMessage();

    header("Location: tambah.php");
    exit;
}