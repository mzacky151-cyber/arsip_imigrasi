<?php

include "../config/koneksi.php";
include "../template/session.php";
include "../template/header.php";
include "../template/sidebar.php";

$jenis = $_GET['jenis'] ?? 'WNI';
$jenis = in_array($jenis, ['WNI', 'WNA'], true) ? $jenis : 'WNI';

$tahun = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT);
if ($tahun !== false && ($tahun < 1900 || $tahun > (int) date('Y'))) {
    $tahun = null;
}

$stmtRak = mysqli_prepare(
    $koneksi,
    "SELECT id_rak, nomor_rak, jenis
     FROM rak
     WHERE jenis = ? AND status = 'Aktif'
     ORDER BY nomor_rak"
);
mysqli_stmt_bind_param($stmtRak, 's', $jenis);
mysqli_stmt_execute($stmtRak);
$hasilRak = mysqli_stmt_get_result($stmtRak);

$daftarRak = [];
while ($barisRak = mysqli_fetch_assoc($hasilRak)) {
    $daftarRak[] = $barisRak;
}
mysqli_stmt_close($stmtRak);

$idRak = filter_input(INPUT_GET, 'id_rak', FILTER_VALIDATE_INT);
$idRakValid = null;

foreach ($daftarRak as $rakItem) {
    if ((int) $rakItem['id_rak'] === (int) $idRak) {
        $idRakValid = (int) $rakItem['id_rak'];
        break;
    }
}

if ($idRakValid === null && !empty($daftarRak)) {
    $idRakValid = (int) $daftarRak[0]['id_rak'];
}

$rakTerpilih = null;
$daftarLokasi = [];
$matriksLokasi = [];
$barisMinimum = 0;
$barisMaksimum = 0;
$kolomMaksimum = 0;

if ($idRakValid !== null) {
    $stmtInfoRak = mysqli_prepare(
        $koneksi,
        "SELECT id_rak, nomor_rak, jenis
         FROM rak
         WHERE id_rak = ? AND jenis = ? AND status = 'Aktif'
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmtInfoRak, 'is', $idRakValid, $jenis);
    mysqli_stmt_execute($stmtInfoRak);
    $rakTerpilih = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtInfoRak));
    mysqli_stmt_close($stmtInfoRak);

    if ($rakTerpilih) {
        if ($tahun) {
            $sqlLokasi = "
                SELECT
                    lokasi_rak.id_lokasi,
                    lokasi_rak.baris,
                    lokasi_rak.kolom,
                    lokasi_rak.tahun_awal,
                    lokasi_rak.tahun_akhir,
                    lokasi_rak.bulan_awal,
                    lokasi_rak.bulan_akhir,
                    lokasi_rak.kapasitas,
                    lokasi_rak.status,
                    COUNT(arsip.id_arsip) AS jumlah_terisi
                FROM lokasi_rak
                LEFT JOIN arsip
                    ON arsip.id_lokasi = lokasi_rak.id_lokasi
                WHERE lokasi_rak.id_rak = ?
                  AND ? BETWEEN lokasi_rak.tahun_awal AND lokasi_rak.tahun_akhir
                GROUP BY
                    lokasi_rak.id_lokasi,
                    lokasi_rak.baris,
                    lokasi_rak.kolom,
                    lokasi_rak.tahun_awal,
                    lokasi_rak.tahun_akhir,
                    lokasi_rak.bulan_awal,
                    lokasi_rak.bulan_akhir,
                    lokasi_rak.kapasitas,
                    lokasi_rak.status
                ORDER BY lokasi_rak.baris, lokasi_rak.kolom
            ";

            $stmtLokasi = mysqli_prepare($koneksi, $sqlLokasi);
            mysqli_stmt_bind_param($stmtLokasi, 'ii', $idRakValid, $tahun);
        } else {
            $sqlLokasi = "
                SELECT
                    lokasi_rak.id_lokasi,
                    lokasi_rak.baris,
                    lokasi_rak.kolom,
                    lokasi_rak.tahun_awal,
                    lokasi_rak.tahun_akhir,
                    lokasi_rak.bulan_awal,
                    lokasi_rak.bulan_akhir,
                    lokasi_rak.kapasitas,
                    lokasi_rak.status,
                    COUNT(arsip.id_arsip) AS jumlah_terisi
                FROM lokasi_rak
                LEFT JOIN arsip
                    ON arsip.id_lokasi = lokasi_rak.id_lokasi
                WHERE lokasi_rak.id_rak = ?
                GROUP BY
                    lokasi_rak.id_lokasi,
                    lokasi_rak.baris,
                    lokasi_rak.kolom,
                    lokasi_rak.tahun_awal,
                    lokasi_rak.tahun_akhir,
                    lokasi_rak.bulan_awal,
                    lokasi_rak.bulan_akhir,
                    lokasi_rak.kapasitas,
                    lokasi_rak.status
                ORDER BY lokasi_rak.baris, lokasi_rak.kolom
            ";

            $stmtLokasi = mysqli_prepare($koneksi, $sqlLokasi);
            mysqli_stmt_bind_param($stmtLokasi, 'i', $idRakValid);
        }

        mysqli_stmt_execute($stmtLokasi);
        $hasilLokasi = mysqli_stmt_get_result($stmtLokasi);

        while ($lokasi = mysqli_fetch_assoc($hasilLokasi)) {
            $lokasi['baris'] = (int) $lokasi['baris'];
            $lokasi['kolom'] = (int) $lokasi['kolom'];
            $lokasi['kapasitas'] = (int) $lokasi['kapasitas'];
            $lokasi['jumlah_terisi'] = (int) $lokasi['jumlah_terisi'];

            $daftarLokasi[] = $lokasi;
            $matriksLokasi[$lokasi['baris']][$lokasi['kolom']] = $lokasi;

            if ($barisMinimum === 0 || $lokasi['baris'] < $barisMinimum) {
                $barisMinimum = $lokasi['baris'];
            }

            $barisMaksimum = max($barisMaksimum, $lokasi['baris']);
            $kolomMaksimum = max($kolomMaksimum, $lokasi['kolom']);
        }

        mysqli_stmt_close($stmtLokasi);
    }
}

function kelasStatusLokasi(int $terisi, int $kapasitas): string
{
    if ($kapasitas <= 0 || $terisi <= 0) {
        return 'kosong';
    }

    $persentase = ($terisi / $kapasitas) * 100;

    if ($persentase >= 100) {
        return 'penuh';
    }

    if ($persentase > 70) {
        return 'hampir-penuh';
    }

    return 'terisi';
}

function e(string $nilai): string
{
    return htmlspecialchars($nilai, ENT_QUOTES, 'UTF-8');
}

?>

<div class="main">
<?php include "../template/topbar.php"; ?>

    <div class="content visualisasi-page">

        <section class="visualisasi-hero">

            <div class="visualisasi-hero-copy">

                <span class="visualisasi-eyebrow">
                    Monitoring Penyimpanan
                </span>

                <h1>Visualisasi Rak</h1>

                <p>
                    Pantau kapasitas, penggunaan, dan kondisi setiap
                    lokasi penyimpanan arsip secara visual.
                </p>

            </div>

            <div class="visualisasi-hero-info">

                <div class="visualisasi-hero-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true">

                        <path d="M4 4h16v16H4z"></path>
                        <path d="M4 10h16"></path>
                        <path d="M10 4v16"></path>

                    </svg>

                </div>

                <div>

            <small>Halaman aktif</small>

            <strong>
                Visualisasi Rak
            </strong>

        </div>

    </div>

</section>
        <div class="card filter-visualisasi-card">
            <form method="GET" class="filter-visualisasi-form">
                <div class="filter-field">
                    <label for="jenis">Pilih Kategori</label>
                    <select name="jenis" id="jenis" class="form-control">
                        <option value="WNI" <?= $jenis === 'WNI' ? 'selected' : ''; ?>>WNI</option>
                        <option value="WNA" <?= $jenis === 'WNA' ? 'selected' : ''; ?>>WNA</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="id_rak">Pilih Rak</label>
                    <select name="id_rak" id="id_rak" class="form-control" required>
                        <?php if (empty($daftarRak)) : ?>
                            <option value="">Tidak ada rak aktif</option>
                        <?php else : ?>
                            <?php foreach ($daftarRak as $rakItem) : ?>
                                <option
                                    value="<?= (int) $rakItem['id_rak']; ?>"
                                    <?= (int) $rakItem['id_rak'] === (int) $idRakValid ? 'selected' : ''; ?>
                                >
                                    Rak <?= (int) $rakItem['nomor_rak']; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="tahun">Tahun Lahir</label>
                    <input
                        type="number"
                        name="tahun"
                        id="tahun"
                        class="form-control"
                        min="1900"
                        max="<?= date('Y'); ?>"
                        value="<?= $tahun ? (int) $tahun : ''; ?>"
                        placeholder="Semua tahun"
                    >
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-visualisasi">
                        Tampilkan
                    </button>

                    <?php if ($tahun) : ?>
                        <a href="index.php?jenis=<?= e($jenis); ?>&id_rak=<?= (int) $idRakValid; ?>"
                           class="btn btn-warning btn-visualisasi">
                            Reset Tahun
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="visualisasi-layout">
            <div class="card rak-visual-card">
                <?php if (!$rakTerpilih) : ?>
                    <div class="empty-state">
                        <h3>Rak tidak tersedia</h3>
                        <p>Belum ada rak aktif untuk kategori <?= e($jenis); ?>.</p>
                    </div>
                <?php elseif (empty($daftarLokasi)) : ?>
                    <div class="empty-state">
                        <h3>Lokasi tidak ditemukan</h3>
                        <p>
                            Tidak ada lokasi pada Rak <?= (int) $rakTerpilih['nomor_rak']; ?>
                            <?= $tahun ? 'untuk tahun lahir ' . (int) $tahun : ''; ?>.
                        </p>
                    </div>
                <?php else : ?>
                    <div class="rak-card-header">
                        <div>
                            <h3>
                                Rak <?= (int) $rakTerpilih['nomor_rak']; ?>
                                (<?= e($rakTerpilih['jenis']); ?>)
                            </h3>
                            <p>
                                <?= $tahun ? 'Lokasi untuk tahun lahir ' . (int) $tahun : 'Seluruh lokasi aktif pada rak'; ?>
                            </p>
                        </div>

                        <span class="jumlah-lokasi-badge">
                            <?= count($daftarLokasi); ?> lokasi
                        </span>
                    </div>

                    <div class="rak-scroll">
                        <div
                            class="rak-grid"
                            style="--jumlah-kolom: <?= max(1, $kolomMaksimum); ?>;"
                        >
                            <div class="grid-corner"></div>

                            <?php for ($kolom = 1; $kolom <= $kolomMaksimum; $kolom++) : ?>
                                <div class="grid-column-label"><?= $kolom; ?></div>
                            <?php endfor; ?>

                            <?php for ($baris = $barisMinimum; $baris <= $barisMaksimum; $baris++) : ?>
                                <div class="grid-row-label"><?= $baris; ?></div>

                                <?php for ($kolom = 1; $kolom <= $kolomMaksimum; $kolom++) : ?>
                                    <?php $lokasi = $matriksLokasi[$baris][$kolom] ?? null; ?>

                                    <?php if ($lokasi) : ?>
                                        <?php
                                        $kelasStatus = kelasStatusLokasi(
                                            $lokasi['jumlah_terisi'],
                                            $lokasi['kapasitas']
                                        );
                                        ?>

                                        <button
                                            type="button"
                                            class="lokasi-cell status-<?= e($kelasStatus); ?>"
                                            data-id-lokasi="<?= (int) $lokasi['id_lokasi']; ?>"
                                            aria-label="Lihat lokasi baris <?= $baris; ?> kolom <?= $kolom; ?>"
                                        >
                                            <span class="lokasi-code">B<?= $baris; ?>-K<?= $kolom; ?></span>
                                            <span class="lokasi-capacity">
                                                <?= $lokasi['jumlah_terisi']; ?> / <?= $lokasi['kapasitas']; ?>
                                            </span>
                                        </button>
                                    <?php else : ?>
                                        <div class="lokasi-cell lokasi-belum-diatur">
                                            <span>Belum diatur</span>
                                        </div>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="rak-legend">
                        <div class="legend-title">Keterangan</div>

                        <div class="legend-items">
                            <div class="legend-item">
                                <span class="legend-box status-kosong"></span>
                                <div><b>Kosong</b><small>0%</small></div>
                            </div>

                            <div class="legend-item">
                                <span class="legend-box status-terisi"></span>
                                <div><b>Terisi Sebagian</b><small>1% sampai 70%</small></div>
                            </div>

                            <div class="legend-item">
                                <span class="legend-box status-hampir-penuh"></span>
                                <div><b>Hampir Penuh</b><small>71% sampai 99%</small></div>
                            </div>

                            <div class="legend-item">
                                <span class="legend-box status-penuh"></span>
                                <div><b>Penuh</b><small>100%</small></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="card lokasi-detail-card" id="panelDetailLokasi">
                <div class="detail-empty">
                    <div class="detail-empty-icon">▦</div>
                    <h3>Detail Lokasi</h3>
                    <p>Klik salah satu kotak lokasi untuk melihat kapasitas dan daftar arsip.</p>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
(function () {
    const lokasiButtons = document.querySelectorAll('.lokasi-cell[data-id-lokasi]');
    const panel = document.getElementById('panelDetailLokasi');

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatTanggal(tanggal) {
        if (!tanggal) return '-';
        const bagian = tanggal.split('-');
        return bagian.length === 3 ? `${bagian[2]}-${bagian[1]}-${bagian[0]}` : tanggal;
    }

    function renderDetail(data) {
        const arsipRows = data.arsip.length
            ? data.arsip.map((arsip, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <b>${escapeHtml(arsip.nama || '-')}</b>
                        <small>${escapeHtml(arsip.nomor_permohonan)}</small>
                    </td>
                    <td>${formatTanggal(arsip.tanggal_lahir)}</td>
                    <td>
                        <span class="arsip-status ${arsip.status === 'Dipinjam' ? 'dipinjam' : 'tersedia'}">
                            ${escapeHtml(arsip.status)}
                        </span>
                    </td>
                </tr>
            `).join('')
            : `
                <tr>
                    <td colspan="4" class="detail-table-empty">
                        Belum ada arsip pada lokasi ini.
                    </td>
                </tr>
            `;

        panel.innerHTML = `
            <div class="detail-panel-header">
                <div>
                    <span class="detail-eyebrow">Detail Lokasi</span>
                    <h3>Rak ${escapeHtml(data.nomor_rak)} (${escapeHtml(data.jenis)})</h3>
                </div>
                <span class="detail-code">B${escapeHtml(data.baris)}-K${escapeHtml(data.kolom)}</span>
            </div>

            <div class="detail-location-box">
                <div><span>Rak</span><b>Rak ${escapeHtml(data.nomor_rak)} (${escapeHtml(data.jenis)})</b></div>
                <div><span>Baris</span><b>${escapeHtml(data.baris)}</b></div>
                <div><span>Kolom</span><b>${escapeHtml(data.kolom)}</b></div>
                <div><span>Rentang Tahun</span><b>${escapeHtml(data.rentang_tahun)}</b></div>
                <div><span>Rentang Bulan</span><b>${escapeHtml(data.rentang_bulan)}</b></div>
            </div>

            <div class="capacity-section">
                <div class="capacity-heading">
                    <div>
                        <span>Kapasitas</span>
                        <b>${escapeHtml(data.jumlah_terisi)} / ${escapeHtml(data.kapasitas)} arsip</b>
                    </div>
                    <strong>${escapeHtml(data.persentase)}%</strong>
                </div>

                <div class="capacity-track">
                    <div
                        class="capacity-fill status-${escapeHtml(data.kelas_status)}"
                        style="width:${Math.min(100, Number(data.persentase))}%"
                    ></div>
                </div>
            </div>

            <div class="detail-status-section">
                <span>Status Lokasi</span>
                <b class="detail-status status-${escapeHtml(data.kelas_status)}">
                    ${escapeHtml(data.label_status)}
                </b>
            </div>

            <div class="detail-list-heading">
                <div>
                    <span>Daftar Arsip</span>
                    <small>Menampilkan maksimal 8 arsip</small>
                </div>
            </div>

            <div class="detail-table-wrapper">
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Arsip</th>
                            <th>Tanggal Lahir</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>${arsipRows}</tbody>
                </table>
            </div>

            <a href="daftar_arsip.php?id=${encodeURIComponent(data.id_lokasi)}"
               class="detail-all-button">
                Lihat Semua Arsip di Lokasi Ini
            </a>
        `;
    }

    async function muatDetail(button) {
        lokasiButtons.forEach((item) => item.classList.remove('selected'));
        button.classList.add('selected');

        panel.innerHTML = `
            <div class="detail-loading">
                <div class="loading-spinner"></div>
                <p>Memuat detail lokasi...</p>
            </div>
        `;

        try {
            const response = await fetch(
                `detail_lokasi.php?id=${encodeURIComponent(button.dataset.idLokasi)}`,
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Detail lokasi gagal dimuat.');
            }

            renderDetail(data.data);
        } catch (error) {
            panel.innerHTML = `
                <div class="detail-error">
                    <h3>Detail gagal dimuat</h3>
                    <p>${escapeHtml(error.message)}</p>
                </div>
            `;
        }
    }

    lokasiButtons.forEach((button) => {
        button.addEventListener('click', () => muatDetail(button));
    });

    if (lokasiButtons.length > 0) {
        muatDetail(lokasiButtons[0]);
    }
})();
</script>

<?php include "../template/footer.php"; ?>
