<?php

include "../config/koneksi.php";
include "../template/session.php";
include "../template/header.php";
include "../template/sidebar.php";

/*
|--------------------------------------------------------------------------
| Pencarian arsip
|--------------------------------------------------------------------------
*/

$keyword = trim($_GET["keyword"] ?? "");
$keywordSql = mysqli_real_escape_string(
    $koneksi,
    $keyword
);

$cari = null;

if ($keyword !== "") {

    $cari = mysqli_query(
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
        WHERE
            arsip.nama LIKE '%$keywordSql%'
            OR arsip.nomor_permohonan LIKE '%$keywordSql%'
        ORDER BY arsip.id_arsip DESC
        LIMIT 1
        "
    );

    if (!$cari) {
        die(
            "Gagal melakukan pencarian arsip: " .
            mysqli_error($koneksi)
        );
    }
}

/*
|--------------------------------------------------------------------------
| Statistik peminjaman
|--------------------------------------------------------------------------
*/

$jumlahDipinjam = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM peminjaman
        WHERE status = 'Dipinjam'
        "
    )
);

$jumlahDikembalikan = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM peminjaman
        WHERE status = 'Dikembalikan'
        "
    )
);

$jumlahTersedia = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM arsip
        WHERE status = 'Tersedia'
        "
    )
);

$jumlahHariIni = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM peminjaman
        WHERE DATE(tanggal_pinjam) = CURDATE()
        "
    )
);

/*
|--------------------------------------------------------------------------
| Daftar arsip yang sedang dipinjam
|--------------------------------------------------------------------------
*/

$data = mysqli_query(
    $koneksi,
    "
    SELECT
        peminjaman.*,
        arsip.nama,
        arsip.nomor_permohonan,
        lokasi_rak.baris,
        lokasi_rak.kolom,
        rak.nomor_rak
    FROM peminjaman
    JOIN arsip
        ON peminjaman.id_arsip = arsip.id_arsip
    JOIN lokasi_rak
        ON arsip.id_lokasi = lokasi_rak.id_lokasi
    JOIN rak
        ON lokasi_rak.id_rak = rak.id_rak
    WHERE peminjaman.status = 'Dipinjam'
    ORDER BY
        peminjaman.tanggal_pinjam DESC,
        peminjaman.id_peminjaman DESC
    "
);

if (!$data) {
    die(
        "Gagal memuat daftar peminjaman: " .
        mysqli_error($koneksi)
    );
}

$totalPeminjamanAktif = mysqli_num_rows($data);
?>

<div class="main peminjaman-modern">

<?php include "../template/topbar.php"; ?>

    <div class="content">

        <section class="peminjaman-hero">

            <div class="peminjaman-hero-copy">

                <span class="peminjaman-eyebrow">
                    Sirkulasi Arsip
                </span>

                <h1>Peminjaman Arsip</h1>

                <p>
                    Cari arsip, catat peminjaman, dan proses
                    pengembalian melalui satu halaman.
                </p>

            </div>

            <div class="peminjaman-hero-status">

                <div class="peminjaman-hero-status-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <path d="M5 4h11v16H5z"></path>
                        <path d="M16 8h3v12H8"></path>
                    </svg>

                </div>

                <div>
                    <small>Sedang dipinjam</small>
                    <strong>
                        <?= number_format(
                            (int) $jumlahDipinjam["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                        arsip
                    </strong>
                </div>

            </div>

        </section>

        <section class="peminjaman-stat-grid">

            <article class="peminjaman-stat-card active">

                <div class="peminjaman-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <path d="M5 4h11v16H5z"></path>
                        <path d="M16 8h3v12H8"></path>
                    </svg>

                </div>

                <div>
                    <span>Dipinjam Aktif</span>

                    <strong>
                        <?= number_format(
                            (int) $jumlahDipinjam["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Arsip masih berada di peminjam</small>
                </div>

            </article>

            <article class="peminjaman-stat-card returned">

                <div class="peminjaman-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <path d="M20 11a8 8 0 1 1-2.3-5.7"></path>
                        <path d="M20 4v7h-7"></path>
                    </svg>

                </div>

                <div>
                    <span>Sudah Dikembalikan</span>

                    <strong>
                        <?= number_format(
                            (int) $jumlahDikembalikan["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Riwayat pengembalian tercatat</small>
                </div>

            </article>

            <article class="peminjaman-stat-card available">

                <div class="peminjaman-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <path d="M4 8h16v12H4z"></path>
                        <path d="M7 8V4h10v4"></path>
                        <path d="m9 14 2 2 4-4"></path>
                    </svg>

                </div>

                <div>
                    <span>Arsip Tersedia</span>

                    <strong>
                        <?= number_format(
                            (int) $jumlahTersedia["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Siap dipinjam atau digunakan</small>
                </div>

            </article>

            <article class="peminjaman-stat-card today">

                <div class="peminjaman-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <rect
                            x="3"
                            y="5"
                            width="18"
                            height="16"
                            rx="2">
                        </rect>
                        <path d="M8 3v4M16 3v4M3 10h18"></path>
                    </svg>

                </div>

                <div>
                    <span>Transaksi Hari Ini</span>

                    <strong>
                        <?= number_format(
                            (int) $jumlahHariIni["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Peminjaman tercatat hari ini</small>
                </div>

            </article>

        </section>

        <section class="peminjaman-search-panel">

            <div class="peminjaman-panel-heading">

                <div class="peminjaman-panel-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-4-4"></path>
                    </svg>

                </div>

                <div>
                    <h2>Pencarian Arsip</h2>
                    <p>
                        Cari berdasarkan nama pemilik arsip atau
                        nomor permohonan.
                    </p>
                </div>

            </div>

            <form method="GET" class="peminjaman-search-form">

                <div class="peminjaman-search-input">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-4-4"></path>
                    </svg>

                    <input
                        type="text"
                        name="keyword"
                        value="<?= htmlspecialchars(
                            $keyword,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        placeholder="Masukkan nama atau nomor permohonan">

                </div>

                <button type="submit">
                    Cari Arsip
                </button>

                <?php if ($keyword !== "") : ?>

                    <a
                        href="index.php"
                        class="peminjaman-reset-button">
                        Reset
                    </a>

                <?php endif; ?>

            </form>

            <?php if ($keyword !== "") : ?>

                <div class="peminjaman-search-result">

                    <?php if (
                        $cari &&
                        mysqli_num_rows($cari) > 0
                    ) : ?>

                        <?php
                        $hasilCari = mysqli_fetch_assoc($cari);

                        $statusTersedia =
                            $hasilCari["status"] === "Tersedia";

                        $statusClass = $statusTersedia
                            ? "available"
                            : "borrowed";
                        ?>

                        <div class="peminjaman-result-heading">

                            <div>
                                <span class="peminjaman-eyebrow">
                                    Hasil Pencarian
                                </span>

                                <h3>
                                    <?= htmlspecialchars(
                                        $hasilCari["nama"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </h3>
                            </div>

                            <span
                                class="peminjaman-status-badge <?= $statusClass; ?>">

                                <?= htmlspecialchars(
                                    $hasilCari["status"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>

                            </span>

                        </div>

                        <div class="peminjaman-result-grid">

                            <div class="peminjaman-result-item">

                                <small>Nomor Permohonan</small>

                                <strong>
                                    <?= htmlspecialchars(
                                        $hasilCari["nomor_permohonan"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </strong>

                            </div>

                            <div class="peminjaman-result-item">

                                <small>Tanggal Lahir</small>

                                <strong>
                                    <?= date(
                                        "d-m-Y",
                                        strtotime(
                                            $hasilCari["tanggal_lahir"]
                                        )
                                    ); ?>
                                </strong>

                            </div>

                            <div class="peminjaman-result-item">

                                <small>Lokasi Penyimpanan</small>

                                <strong>
                                    Rak <?= htmlspecialchars(
                                        $hasilCari["nomor_rak"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                    ·
                                    B<?= (int) $hasilCari["baris"]; ?>
                                    ·
                                    K<?= (int) $hasilCari["kolom"]; ?>
                                    ·
                                    No <?= (int) $hasilCari["nomor_urut"]; ?>
                                </strong>

                            </div>

                            <div class="peminjaman-result-action">

                                <?php if ($statusTersedia) : ?>

                                    <button
                                        type="button"
                                        class="peminjaman-primary-button btnPinjam"
                                        data-id="<?= (int) $hasilCari["id_arsip"]; ?>"
                                        data-nama="<?= htmlspecialchars(
                                            $hasilCari["nama"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                        data-permohonan="<?= htmlspecialchars(
                                            $hasilCari["nomor_permohonan"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            aria-hidden="true">
                                            <path d="M12 5v14M5 12h14"></path>
                                        </svg>

                                        Pinjam Arsip
                                    </button>

                                <?php else : ?>

                                    <button
                                        type="button"
                                        class="peminjaman-disabled-button"
                                        disabled>
                                        Sedang Dipinjam
                                    </button>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php else : ?>

                        <div class="peminjaman-not-found">

                            <div class="peminjaman-not-found-icon">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"></circle>
                                    <path d="m20 20-4-4"></path>
                                    <path d="M8.5 8.5l5 5M13.5 8.5l-5 5"></path>
                                </svg>

                            </div>

                            <strong>Arsip tidak ditemukan</strong>

                            <span>
                                Periksa kembali nama atau nomor permohonan
                                yang dimasukkan.
                            </span>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </section>

        <section class="peminjaman-table-panel">

            <div class="peminjaman-table-header">

                <div>

                    <span class="peminjaman-eyebrow">
                        Daftar Aktif
                    </span>

                    <h2>Arsip Sedang Dipinjam</h2>

                    <p>
                        <?= $totalPeminjamanAktif; ?>
                        arsip masih tercatat berada di peminjam.
                    </p>

                </div>

                <div class="peminjaman-table-count">

                    <span>Aktif</span>

                    <strong>
                        <?= number_format(
                            $totalPeminjamanAktif,
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                </div>

            </div>

            <div class="peminjaman-table-wrapper">

                <table class="peminjaman-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No Permohonan</th>
                            <th>Nama Arsip</th>
                            <th>Nama Peminjam</th>
                            <th>NIP</th>
                            <th>Tanggal Pinjam</th>
                            <th>Lokasi</th>
                            <th>Keperluan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($totalPeminjamanAktif === 0) : ?>

                        <tr>
                            <td
                                colspan="9"
                                class="peminjaman-empty-state">

                                <div class="peminjaman-empty-icon">

                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        aria-hidden="true">
                                        <path d="M4 8h16v12H4z"></path>
                                        <path d="M7 8V4h10v4"></path>
                                        <path d="m9 14 2 2 4-4"></path>
                                    </svg>

                                </div>

                                <strong>
                                    Tidak ada arsip yang sedang dipinjam
                                </strong>

                                <span>
                                    Seluruh arsip saat ini tersedia.
                                </span>

                            </td>
                        </tr>

                    <?php else : ?>

                        <?php
                        $nomor = 1;

                        while ($peminjaman = mysqli_fetch_assoc($data)) :
                        ?>

                            <tr>

                                <td><?= $nomor++; ?></td>

                                <td>
                                    <?= htmlspecialchars(
                                        $peminjaman["nomor_permohonan"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </td>

                                <td>
                                    <strong class="peminjaman-table-name">
                                        <?= htmlspecialchars(
                                            $peminjaman["nama"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $peminjaman["nama_peminjam"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $peminjaman["nip_peminjam"] ?? "-",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </td>

                                <td>
                                    <?= date(
                                        "d-m-Y",
                                        strtotime(
                                            $peminjaman["tanggal_pinjam"]
                                        )
                                    ); ?>
                                </td>

                                <td>

                                    <span class="peminjaman-location-badge">
                                        Rak <?= htmlspecialchars(
                                            $peminjaman["nomor_rak"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>
                                        ·
                                        B<?= (int) $peminjaman["baris"]; ?>
                                        ·
                                        K<?= (int) $peminjaman["kolom"]; ?>
                                    </span>

                                </td>

                                <td>

                                    <span
                                        class="peminjaman-purpose"
                                        title="<?= htmlspecialchars(
                                            $peminjaman["keperluan"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>">

                                        <?= htmlspecialchars(
                                            $peminjaman["keperluan"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>

                                    </span>

                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="peminjaman-return-button btnKembali"
                                        data-id="<?= (int) $peminjaman["id_peminjaman"]; ?>"
                                        data-nama="<?= htmlspecialchars(
                                            $peminjaman["nama"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                        data-peminjam="<?= htmlspecialchars(
                                            $peminjaman["nama_peminjam"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            aria-hidden="true">
                                            <path d="M20 11a8 8 0 1 1-2.3-5.7"></path>
                                            <path d="M20 4v7h-7"></path>
                                        </svg>

                                        Kembalikan
                                    </button>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ==========================
   POPUP PEMINJAMAN
========================== */

document
    .querySelectorAll(".btnPinjam")
    .forEach(function (button) {

        button.addEventListener("click", function () {

            const id = this.dataset.id;
            const nama = this.dataset.nama;
            const permohonan = this.dataset.permohonan;

            Swal.fire({

                title: "Peminjaman Arsip",
                width: 520,
                padding: "1.8rem 1.8rem 1.5rem",
                showCancelButton: true,
                confirmButtonText: "Pinjam",
                cancelButtonText: "Batal",
                confirmButtonColor: "#123458",
                cancelButtonColor: "#9CA3AF",
                reverseButtons: true,
                allowOutsideClick: false,
                focusConfirm: false,

                customClass: {
                    popup: "rounded-popup",
                    title: "popup-title",
                    confirmButton: "popup-button",
                    cancelButton: "popup-button"
                },

                html: `
                    <div class="peminjaman-popup-form">

                        <div class="peminjaman-popup-info">

                            <div>
                                <span>Nomor Permohonan</span>
                                <strong>${permohonan}</strong>
                            </div>

                            <div>
                                <span>Nama Arsip</span>
                                <strong>${nama}</strong>
                            </div>

                        </div>

                        <div class="peminjaman-popup-field">

                            <label for="nama_peminjam">
                                Nama Peminjam
                            </label>

                            <input
                                id="nama_peminjam"
                                type="text"
                                placeholder="Masukkan nama peminjam">

                        </div>

                        <div class="peminjaman-popup-field">

                            <label for="nip">
                                NIP
                            </label>

                            <input
                                id="nip"
                                type="text"
                                inputmode="numeric"
                                placeholder="Masukkan NIP">

                        </div>

                        <div class="peminjaman-popup-field">

                            <label for="keperluan">
                                Keperluan
                            </label>

                            <textarea
                                id="keperluan"
                                placeholder="Tuliskan keperluan peminjaman">
                            </textarea>

                        </div>

                    </div>
                `,

                preConfirm: function () {

                    const namaPeminjam =
                        document
                            .getElementById("nama_peminjam")
                            .value
                            .trim();

                    const nip =
                        document
                            .getElementById("nip")
                            .value
                            .trim();

                    const keperluan =
                        document
                            .getElementById("keperluan")
                            .value
                            .trim();

                    if (
                        namaPeminjam === "" ||
                        nip === "" ||
                        keperluan === ""
                    ) {
                        Swal.showValidationMessage(
                            "Semua data wajib diisi."
                        );

                        return false;
                    }

                    return {
                        nama_peminjam: namaPeminjam,
                        nip: nip,
                        keperluan: keperluan
                    };
                }

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }

                const form = document.createElement("form");

                form.method = "POST";
                form.action = "pinjam.php";

                const dataForm = {
                    id_arsip: id,
                    nama_peminjam:
                        result.value.nama_peminjam,
                    nip:
                        result.value.nip,
                    keperluan:
                        result.value.keperluan
                };

                Object
                    .entries(dataForm)
                    .forEach(function ([namaInput, nilai]) {

                        const input =
                            document.createElement("input");

                        input.type = "hidden";
                        input.name = namaInput;
                        input.value = nilai;

                        form.appendChild(input);
                    });

                document.body.appendChild(form);
                form.submit();

            });

        });

    });


/* ==========================
   POPUP PENGEMBALIAN
========================== */

document
    .querySelectorAll(".btnKembali")
    .forEach(function (button) {

        button.addEventListener("click", function () {

            const id = this.dataset.id;
            const nama = this.dataset.nama;
            const peminjam = this.dataset.peminjam;

            Swal.fire({

                icon: "question",
                title: "Pengembalian Arsip",
                width: 440,

                html: `
                    <div class="peminjaman-return-popup">

                        <div>
                            <span>Nama Arsip</span>
                            <strong>${nama}</strong>
                        </div>

                        <div>
                            <span>Peminjam</span>
                            <strong>${peminjam}</strong>
                        </div>

                        <p>
                            Pastikan arsip fisik sudah diterima
                            sebelum melanjutkan pengembalian.
                        </p>

                    </div>
                `,

                showCancelButton: true,
                confirmButtonText: "Kembalikan",
                cancelButtonText: "Batal",
                confirmButtonColor: "#123458",
                cancelButtonColor: "#9CA3AF",
                reverseButtons: true,
                allowOutsideClick: false,

                customClass: {
                    popup: "rounded-popup",
                    title: "popup-title",
                    confirmButton: "popup-button",
                    cancelButton: "popup-button"
                }

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }

                const form = document.createElement("form");

                form.method = "POST";
                form.action = "kembalikan.php";

                const input = document.createElement("input");

                input.type = "hidden";
                input.name = "id_peminjaman";
                input.value = id;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();

            });

        });

    });
</script>

<?php
$notifikasi = null;

if (isset($_SESSION["pinjam_error"])) {

    $notifikasi = [
        "icon"  => "error",
        "title" => "Peminjaman Gagal",
        "text"  => $_SESSION["pinjam_error"]
    ];

    unset($_SESSION["pinjam_error"]);

} elseif (isset($_SESSION["kembali_error"])) {

    $notifikasi = [
        "icon"  => "error",
        "title" => "Pengembalian Gagal",
        "text"  => $_SESSION["kembali_error"]
    ];

    unset($_SESSION["kembali_error"]);

} elseif (isset($_SESSION["pinjam_success"])) {

    $notifikasi = [
        "icon"  => "success",
        "title" => "Peminjaman Berhasil",
        "text"  => "Arsip berhasil dicatat sebagai dipinjam."
    ];

    unset($_SESSION["pinjam_success"]);

} elseif (isset($_SESSION["kembali_success"])) {

    $notifikasi = [
        "icon"  => "success",
        "title" => "Pengembalian Berhasil",
        "text"  => "Arsip telah dikembalikan dan tersedia kembali."
    ];

    unset($_SESSION["kembali_success"]);
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
        text: notifikasi.text,
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