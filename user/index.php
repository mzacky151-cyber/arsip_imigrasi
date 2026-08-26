<?php

include "../config/koneksi.php";
include "../template/session.php";

wajibLevel(["admin"]);

include "../template/header.php";
include "../template/sidebar.php";

/*
|--------------------------------------------------------------------------
| Statistik user
|--------------------------------------------------------------------------
*/

$totalUser = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM user
        "
    )
);

$totalAdmin = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM user
        WHERE level = 'admin'
        "
    )
);

$totalPetugas = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM user
        WHERE level = 'petugas'
        "
    )
);

$totalNip = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "
        SELECT COUNT(*) AS total
        FROM user
        WHERE nip IS NOT NULL
          AND nip <> ''
        "
    )
);

/*
|--------------------------------------------------------------------------
| Daftar user
|--------------------------------------------------------------------------
*/

$data = mysqli_query(
    $koneksi,
    "
    SELECT
        id_user,
        nama,
        nip,
        username,
        level
    FROM user
    ORDER BY id_user DESC
    "
);

if (!$data) {
    die(
        "Gagal memuat data user: " .
        mysqli_error($koneksi)
    );
}

$totalDataUser = mysqli_num_rows($data);
?>

<div class="main user-modern">

<?php include "../template/topbar.php"; ?>

    <div class="content">

        <section class="user-hero">

            <div class="user-hero-copy">

                <span class="user-eyebrow">
                    Administrasi Akun
                </span>

                <h1>Manajemen User</h1>

                <p>
                    Kelola akun admin dan petugas yang memiliki
                    akses ke dalam sistem SIPA.
                </p>

            </div>

            <div class="user-hero-actions">

                <div class="user-current-account">

                    <div class="user-current-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <circle cx="12" cy="8" r="3"></circle>
                            <path d="M5 20c.8-4 3.1-6 7-6s6.2 2 7 6"></path>
                        </svg>

                    </div>

                    <div>
                        <small>Akun aktif</small>

                        <strong>
                            <?= htmlspecialchars(
                                $_SESSION["nama"],
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>
                        </strong>
                    </div>

                </div>

                <a
                    href="tambah.php"
                    class="user-add-button">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>

                    Tambah User
                </a>

            </div>

        </section>

        <section class="user-stat-grid">

            <article class="user-stat-card total">

                <div class="user-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <circle cx="9" cy="8" r="3"></circle>
                        <circle cx="17" cy="9" r="2"></circle>
                        <path d="M3 20c.7-4 2.8-6 6-6s5.3 2 6 6"></path>
                        <path d="M15 15c2.7.2 4.4 1.8 5 5"></path>
                    </svg>

                </div>

                <div>
                    <span>Total User</span>

                    <strong>
                        <?= number_format(
                            (int) $totalUser["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Seluruh akun sistem</small>
                </div>

            </article>

            <article class="user-stat-card admin">

                <div class="user-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <path d="M12 3 19 6v5c0 4.5-2.8 8-7 10-4.2-2-7-5.5-7-10V6z"></path>
                        <path d="m9 12 2 2 4-4"></path>
                    </svg>

                </div>

                <div>
                    <span>Admin</span>

                    <strong>
                        <?= number_format(
                            (int) $totalAdmin["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Akses pengelolaan penuh</small>
                </div>

            </article>

            <article class="user-stat-card officer">

                <div class="user-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <circle cx="12" cy="8" r="3"></circle>
                        <path d="M5 20c.8-4 3.1-6 7-6s6.2 2 7 6"></path>
                        <path d="M17 5h4v4"></path>
                    </svg>

                </div>

                <div>
                    <span>Petugas</span>

                    <strong>
                        <?= number_format(
                            (int) $totalPetugas["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Akun operasional SIPA</small>
                </div>

            </article>

            <article class="user-stat-card nip">

                <div class="user-stat-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <circle cx="8" cy="11" r="2"></circle>
                        <path d="M13 9h5M13 12h5M6 16h12"></path>
                    </svg>

                </div>

                <div>
                    <span>NIP Terisi</span>

                    <strong>
                        <?= number_format(
                            (int) $totalNip["total"],
                            0,
                            ",",
                            "."
                        ); ?>
                    </strong>

                    <small>Akun dengan identitas pegawai</small>
                </div>

            </article>

        </section>

        <section class="user-table-panel">

            <div class="user-table-header">

                <div>

                    <span class="user-eyebrow">
                        Daftar Akun
                    </span>

                    <h2>Pengguna SIPA</h2>

                    <p>
                        <?= $totalDataUser; ?>
                        akun terdaftar dan dapat dikelola oleh admin.
                    </p>

                </div>

                <div class="user-table-info">

                    <div class="user-table-info-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 10v6M12 7h.01"></path>
                        </svg>

                    </div>

                    <div>
                        <span>Keamanan akun</span>
                        <strong>
                            Akun sendiri tidak dapat dihapus
                        </strong>
                    </div>

                </div>

            </div>

            <div class="user-table-wrapper">

                <table class="user-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pegawai</th>
                            <th>NIP</th>
                            <th>Username</th>
                            <th>Level</th>
                            <th>Status Akun</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($totalDataUser === 0) : ?>

                        <tr>
                            <td
                                colspan="7"
                                class="user-empty-state">

                                <div class="user-empty-icon">

                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        aria-hidden="true">
                                        <circle cx="12" cy="8" r="3"></circle>
                                        <path d="M5 20c.8-4 3.1-6 7-6s6.2 2 7 6"></path>
                                    </svg>

                                </div>

                                <strong>Belum ada data user</strong>

                                <span>
                                    Tambahkan akun untuk mulai
                                    menggunakan sistem.
                                </span>

                            </td>
                        </tr>

                    <?php else : ?>

                        <?php
                        $nomor = 1;

                        while ($userData = mysqli_fetch_assoc($data)) :
                        ?>

                            <?php
                            $idUser = (int) $userData["id_user"];

                            $akunSendiri =
                                $idUser === (int) $_SESSION["id_user"];

                            $akunUtama =
                                $userData["username"] === "admin";

                            $bolehDihapus =
                                !$akunSendiri && !$akunUtama;

                            $kelasLevel =
                                $userData["level"] === "admin"
                                    ? "admin"
                                    : "officer";
                            ?>

                            <tr>

                                <td><?= $nomor++; ?></td>

                                <td>

                                    <div class="user-name-cell">

                                        <div class="user-avatar">

                                            <?= strtoupper(
                                                substr(
                                                    $userData["nama"],
                                                    0,
                                                    1
                                                )
                                            ); ?>

                                        </div>

                                        <div>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $userData["nama"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ); ?>
                                            </strong>

                                            <?php if ($akunSendiri) : ?>

                                                <small>
                                                    Akun yang sedang digunakan
                                                </small>

                                            <?php else : ?>

                                                <small>
                                                    Pengguna SIPA
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </td>

                                <td>

                                    <?php if (!empty($userData["nip"])) : ?>

                                        <span class="user-nip-value">
                                            <?= htmlspecialchars(
                                                $userData["nip"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>
                                        </span>

                                    <?php else : ?>

                                        <span class="user-nip-empty">
                                            Belum diisi
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <span class="user-username">
                                        @<?= htmlspecialchars(
                                            $userData["username"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>
                                    </span>

                                </td>

                                <td>

                                    <span
                                        class="user-level-badge <?= $kelasLevel; ?>">

                                        <?= ucfirst(
                                            htmlspecialchars(
                                                $userData["level"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            )
                                        ); ?>

                                    </span>

                                </td>

                                <td>

                                    <?php if ($akunSendiri) : ?>

                                        <span class="user-account-status current">
                                            Aktif Sekarang
                                        </span>

                                    <?php elseif ($akunUtama) : ?>

                                        <span class="user-account-status protected">
                                            Akun Utama
                                        </span>

                                    <?php else : ?>

                                        <span class="user-account-status normal">
                                            Aktif
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div class="user-action-group">

                                        <a
                                            href="edit.php?id=<?= $idUser; ?>"
                                            class="user-action-button edit"
                                            title="Edit user"
                                            aria-label="Edit user">

                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true">
                                                <path d="M4 20h4l10-10-4-4L4 16v4z"></path>
                                                <path d="m13 7 4 4"></path>
                                            </svg>

                                        </a>

                                        <?php if ($bolehDihapus) : ?>

                                            <button
                                                type="button"
                                                class="user-action-button delete btn-hapus-user"
                                                title="Hapus user"
                                                aria-label="Hapus user"
                                                data-id="<?= $idUser; ?>"
                                                data-nama="<?= htmlspecialchars(
                                                    $userData["nama"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ); ?>"
                                                data-username="<?= htmlspecialchars(
                                                    $userData["username"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ); ?>"
                                                data-nip="<?= htmlspecialchars(
                                                    $userData["nip"] ?: "-",
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ); ?>"
                                                data-level="<?= htmlspecialchars(
                                                    ucfirst(
                                                        $userData["level"]
                                                    ),
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ); ?>">

                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    aria-hidden="true">
                                                    <path d="M4 7h16"></path>
                                                    <path d="M9 7V4h6v3"></path>
                                                    <path d="m6 7 1 13h10l1-13"></path>
                                                    <path d="M10 11v5M14 11v5"></path>
                                                </svg>

                                            </button>

                                        <?php else : ?>

                                            <span
                                                class="user-action-button locked"
                                                title="Akun dilindungi"
                                                aria-label="Akun dilindungi">

                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    aria-hidden="true">
                                                    <rect
                                                        x="5"
                                                        y="10"
                                                        width="14"
                                                        height="10"
                                                        rx="2">
                                                    </rect>
                                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                                                </svg>

                                            </span>

                                        <?php endif; ?>

                                    </div>

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
document
    .querySelectorAll(".btn-hapus-user")
    .forEach(function (button) {

        button.addEventListener("click", function () {

            const id = this.dataset.id;
            const nama = this.dataset.nama;
            const username = this.dataset.username;
            const nip = this.dataset.nip;
            const level = this.dataset.level;

            Swal.fire({

                icon: "warning",
                title: "Hapus Akun?",
                width: 450,

                html: `
                    <p style="
                        margin-bottom:16px;
                        color:#667085;
                        font-size:13px;
                    ">
                        Akun berikut akan dihapus secara permanen.
                    </p>

                    <div class="user-delete-popup">

                        <div>
                            <span>Nama Pegawai</span>
                            <strong>${nama}</strong>
                        </div>

                        <div>
                            <span>NIP</span>
                            <strong>${nip}</strong>
                        </div>

                        <div>
                            <span>Username</span>
                            <strong>@${username}</strong>
                        </div>

                        <div>
                            <span>Level</span>
                            <strong>${level}</strong>
                        </div>

                    </div>
                `,

                showCancelButton: true,
                confirmButtonText: "Ya, Hapus",
                cancelButtonText: "Batal",
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d",
                reverseButtons: true,
                allowOutsideClick: false,

                customClass: {
                    popup: "rounded-popup",
                    title: "popup-title",
                    confirmButton: "popup-button",
                    cancelButton: "popup-button"
                }

            }).then(function (result) {

                if (result.isConfirmed) {
                    window.location =
                        "hapus.php?id=" +
                        encodeURIComponent(id);
                }

            });

        });

    });
</script>

<?php include "../template/footer.php"; ?>