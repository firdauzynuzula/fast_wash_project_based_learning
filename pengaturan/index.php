<?php
$title = 'Pengaturan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// Halaman khusus pemilik. Hapus blok ini kalau auth.php sudah punya pengecekan role sendiri.
if (!in_array($_SESSION['role'] ?? '', ['pemilik', 'admin'], true)) {
    header('Location: ' . $base . 'dashboard');
    exit;
}

$breadcrumb = [
    ['label' => 'Pengaturan', 'url' => ''],
];

// Penyimpanan sementara (session) supaya tambah / edit / hapus langsung terlihat hasilnya.
// Ganti dengan query ke tabel layanan kalau struktur tabelnya sudah ada.
require_once __DIR__ . '/data_layanan.php';
$daftarLayanan = layananSemua();

// Hasil proses tambah / edit / hapus, ditampilkan sebagai modal
$flash = $_SESSION['flash_layanan'] ?? null;
unset($_SESSION['flash_layanan']);

$rupiah = fn(int $n): string => 'Rp ' . number_format($n, 0, ',', '.');

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .pg-card { background:#FFFFFF; border-radius:24px; padding:32px; border:1px solid #EEF2F7; box-shadow:0 4px 20px rgba(15,23,42,.04); margin-bottom:32px; }
    .pg-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .pg-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; background:#EAF3FE; color:#0A5CC7; font-size:12px; font-weight:600; }
    .pg-title { font-size:30px; line-height:1.2; font-weight:800; color:#0B2545; margin:10px 0 6px; letter-spacing:-.01em; }
    .pg-desc { font-size:14px; line-height:1.85; color:#475569; margin:0 0 30px; }
    .pg-tambah { height:40px; display:inline-flex; align-items:center; gap:8px; padding:0 18px; background:#0085FF; color:#FFFFFF; font-size:14px; font-weight:600; border:0; border-radius:12px; box-shadow:0 2px 6px rgba(0,133,255,.25); cursor:pointer; white-space:nowrap; }
    .pg-tambah:hover { background:#0070D8; }

    .pg-table-wrap { border:1px solid #E5EAF2; border-radius:16px; overflow:hidden; overflow-x:auto; }
    .pg-table { width:100%; min-width:820px; border-collapse:collapse; table-layout:fixed; text-align:center; }
    .pg-table thead th { background:#0085FF; color:#FFFFFF; font-size:14px; font-weight:600; padding:17px 24px; }
    .pg-table thead th.pg-left { text-align:left; padding-left:64px; }
    .pg-table tbody td { height:100px; padding:0 24px; vertical-align:middle; font-size:14px; color:#475569; }
    .pg-table tbody td.pg-left { text-align:left; padding-left:48px; }
    .pg-table tbody tr:hover { background:#FAFCFF; }

    .pg-svc { display:flex; align-items:center; gap:24px; }
    .pg-svc-ico { flex:0 0 28px; display:flex; }
    .pg-svc-nama { font-size:15px; font-weight:700; color:#0F172A; line-height:1.3; }
    .pg-svc-desc { font-size:13px; color:#64748B; margin-top:2px; }
    .pg-harga { font-size:14px; font-weight:600; color:#0F172A; }

    .pg-status { display:inline-flex; align-items:center; gap:7px; padding:5px 16px; border-radius:999px; font-size:13px; font-weight:600; }
    .pg-status i { width:7px; height:7px; border-radius:50%; display:block; }
    .pg-status.aktif { background:#E6F7EE; color:#16A34A; }
    .pg-status.aktif i { background:#16A34A; }
    .pg-status.nonaktif { background:#F1F5F9; color:#64748B; }
    .pg-status.nonaktif i { background:#94A3B8; }

    .pg-aksi { display:inline-flex; align-items:center; gap:10px; }
    .pg-btn { width:36px; height:36px; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; cursor:pointer; padding:0; transition:filter .15s; }
    .pg-btn:hover { filter:brightness(.96); }
    .pg-btn.edit { background:#E8F2FE; border:1px solid #D3E5FB; color:#0085FF; }
    .pg-btn.hapus { background:#FDEDED; border:1px solid #F8D4D4; color:#EF4444; }

    /* Modal */
    .pg-modal { position:fixed; inset:0; z-index:60; display:none; align-items:center; justify-content:center; padding:16px; background:rgba(15,23,42,.4); backdrop-filter:blur(2px); }
    .pg-modal.open { display:flex; }
    .pg-dialog { width:100%; max-width:440px; max-height:92vh; overflow-y:auto; background:#FFFFFF; border-radius:20px; padding:24px; box-shadow:0 20px 50px rgba(15,23,42,.25); }
    .pg-dialog-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; }
    .pg-dialog-title { font-size:18px; font-weight:700; color:#1E293B; margin:0; }
    .pg-x { display:flex; padding:4px; color:#94A3B8; background:none; border:0; cursor:pointer; }
    .pg-x:hover { color:#475569; }
    .pg-field { margin-bottom:14px; }
    .pg-label { display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px; }
    .pg-input { width:100%; height:40px; box-sizing:border-box; background:#FFFFFF; border:1px solid #E5EAF2; border-radius:10px; padding:0 14px; font-size:14px; color:#0F172A; outline:none; font-family:inherit; }
    .pg-input:focus { border-color:#0085FF; }
    textarea.pg-input { height:auto; min-height:72px; padding:10px 14px; resize:vertical; }
    .pg-rp { position:relative; }
    .pg-rp span { position:absolute; left:14px; top:0; bottom:0; display:flex; align-items:center; font-size:14px; font-weight:600; color:#64748B; pointer-events:none; }
    .pg-rp .pg-input { padding-left:42px; }
    .pg-foot { display:flex; align-items:center; justify-content:flex-end; gap:10px; margin-top:20px; }
    .pg-batal { padding:10px 16px; font-size:14px; color:#475569; background:none; border:0; border-radius:10px; cursor:pointer; }
    .pg-batal:hover { background:#F1F5F9; }
    .pg-primary { padding:10px 20px; font-size:14px; font-weight:600; color:#FFFFFF; background:#0085FF; border:0; border-radius:10px; cursor:pointer; }
    .pg-primary:hover { background:#0070D8; }
    .pg-danger { padding:10px 20px; font-size:14px; font-weight:600; color:#FFFFFF; background:#EF4444; border:0; border-radius:10px; cursor:pointer; }
    .pg-danger:hover { background:#DC2626; }
    .pg-confirm { text-align:center; }
    .pg-confirm-ico { width:48px; height:48px; margin:0 auto 14px; border-radius:50%; background:#FDEDED; color:#EF4444; display:flex; align-items:center; justify-content:center; }
    .pg-confirm-ico.ok { background:#E6F7EE; color:#16A34A; }
    .pg-confirm p { font-size:14px; line-height:1.6; color:#475569; margin:8px 0 0; }
    .pg-confirm p b { color:#0F172A; }
</style>

<div class="pg-card">

    <div class="pg-head">
        <div>
            <!-- Badge -->
            <span class="pg-badge">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                Operasional
            </span>
            <h1 class="pg-title">Pengaturan Tarif &amp; Layanan</h1>
        </div>

        <!-- Tombol tambah (pembuka modal Tambah Layanan) -->
        <button type="button" class="pg-tambah" onclick="bukaModalTambah()">
            <svg width="16" height="16" fill="none" stroke="#FFFFFF" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Layanan
        </button>
    </div>
    <p class="pg-desc">Kelola jenis layanan laundry yang tersedia, atur harga, dan tambahkan layanan baru sesuai kebutuhan</p>

    <!-- Tabel layanan -->
    <div class="pg-table-wrap">
        <table class="pg-table">
            <thead>
                <tr>
                    <th class="pg-left" style="width:32%;">Jenis Layanan</th>
                    <th style="width:26%;">Harga</th>
                    <th style="width:20%;">status</th>
                    <th style="width:22%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftarLayanan)): ?>
                    <tr>
                        <td colspan="4" style="height:auto; padding:40px 24px;">
                            <p style="font-size:14px; font-weight:600; color:#334155; margin:0;">Belum ada layanan</p>
                            <p style="font-size:13px; color:#94A3B8; margin:6px 0 0;">Klik "Tambah Layanan" untuk membuat layanan pertama.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftarLayanan as $row): ?>
                        <tr>
                            <!-- Jenis layanan -->
                            <td class="pg-left">
                                <div class="pg-svc">
                                    <span class="pg-svc-ico">
                                        <svg width="28" height="28" fill="none" stroke="#0085FF" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 3l4 3-2 5-3-1v10H9V10L6 11 4 6l4-3c1 1.5 3 2 4 2s3-.5 4-2z"/></svg>
                                    </span>
                                    <div>
                                        <div class="pg-svc-nama"><?php echo e($row['nama']); ?></div>
                                        <div class="pg-svc-desc"><?php echo e($row['deskripsi']); ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Harga -->
                            <td><span class="pg-harga"><?php echo e($rupiah((int) $row['harga'])); ?></span></td>

                            <!-- Status -->
                            <td>
                                <?php if ($row['aktif']): ?>
                                    <span class="pg-status aktif"><i></i>Aktif</span>
                                <?php else: ?>
                                    <span class="pg-status nonaktif"><i></i>Nonaktif</span>
                                <?php endif; ?>
                            </td>

                            <!-- Aksi -->
                            <td>
                                <div class="pg-aksi">
                                    <button type="button" class="pg-btn edit" aria-label="Edit layanan"
                                            data-id="<?php echo (int) $row['id']; ?>"
                                            data-nama="<?php echo e($row['nama']); ?>"
                                            data-deskripsi="<?php echo e($row['deskripsi']); ?>"
                                            data-harga="<?php echo (int) $row['harga']; ?>"
                                            data-aktif="<?php echo $row['aktif'] ? '1' : '0'; ?>"
                                            onclick="bukaModalEdit(this)">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    <button type="button" class="pg-btn hapus" aria-label="Hapus layanan"
                                            data-id="<?php echo (int) $row['id']; ?>"
                                            data-nama="<?php echo e($row['nama']); ?>"
                                            onclick="bukaModalHapus(this)">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Modal: Tambah Layanan -->
<div id="modalTambah" class="pg-modal" onclick="if (event.target === this) tutupModal('modalTambah')">
    <div class="pg-dialog" role="dialog" aria-modal="true" aria-labelledby="judulTambah">
        <div class="pg-dialog-head">
            <h3 class="pg-dialog-title" id="judulTambah">Tambah Layanan</h3>
            <button type="button" class="pg-x" onclick="tutupModal('modalTambah')" aria-label="Tutup">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formTambah" action="<?php echo $base; ?>pengaturan/proses_tambah" method="post">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <div class="pg-field">
                <label class="pg-label" for="tambahNama">Nama Layanan</label>
                <input type="text" id="tambahNama" name="nama" class="pg-input" placeholder="Contoh: Cuci + Setrika" required maxlength="100">
            </div>
            <div class="pg-field">
                <label class="pg-label" for="tambahDeskripsi">Deskripsi</label>
                <textarea id="tambahDeskripsi" name="deskripsi" class="pg-input" placeholder="Contoh: Pakaian dicuci dan disetrika rapi" maxlength="255"></textarea>
            </div>
            <div class="pg-field">
                <label class="pg-label" for="tambahHargaView">Harga</label>
                <div class="pg-rp">
                    <span>Rp</span>
                    <input type="text" id="tambahHargaView" class="pg-input" inputmode="numeric" autocomplete="off" placeholder="0" required>
                </div>
                <input type="hidden" id="tambahHarga" name="harga" value="">
            </div>
            <div class="pg-field">
                <label class="pg-label" for="tambahStatus">Status</label>
                <select id="tambahStatus" name="status" class="pg-input">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
            <div class="pg-foot">
                <button type="button" class="pg-batal" onclick="tutupModal('modalTambah')">Batal</button>
                <button type="submit" class="pg-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Layanan -->
<div id="modalEdit" class="pg-modal" onclick="if (event.target === this) tutupModal('modalEdit')">
    <div class="pg-dialog" role="dialog" aria-modal="true" aria-labelledby="judulEdit">
        <div class="pg-dialog-head">
            <h3 class="pg-dialog-title" id="judulEdit">Edit Layanan</h3>
            <button type="button" class="pg-x" onclick="tutupModal('modalEdit')" aria-label="Tutup">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formEdit" action="<?php echo $base; ?>pengaturan/proses_edit" method="post">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" id="editId" value="">
            <div class="pg-field">
                <label class="pg-label" for="editNama">Nama Layanan</label>
                <input type="text" id="editNama" name="nama" class="pg-input" required maxlength="100">
            </div>
            <div class="pg-field">
                <label class="pg-label" for="editDeskripsi">Deskripsi</label>
                <textarea id="editDeskripsi" name="deskripsi" class="pg-input" maxlength="255"></textarea>
            </div>
            <div class="pg-field">
                <label class="pg-label" for="editHargaView">Harga</label>
                <div class="pg-rp">
                    <span>Rp</span>
                    <input type="text" id="editHargaView" class="pg-input" inputmode="numeric" autocomplete="off" placeholder="0" required>
                </div>
                <input type="hidden" id="editHarga" name="harga" value="">
            </div>
            <div class="pg-field">
                <label class="pg-label" for="editStatus">Status</label>
                <select id="editStatus" name="status" class="pg-input">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
            <div class="pg-foot">
                <button type="button" class="pg-batal" onclick="tutupModal('modalEdit')">Batal</button>
                <button type="submit" class="pg-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Konfirmasi Hapus -->
<div id="modalHapus" class="pg-modal" onclick="if (event.target === this) tutupModal('modalHapus')">
    <div class="pg-dialog" role="dialog" aria-modal="true" aria-labelledby="judulHapus" style="max-width:400px;">
        <form action="<?php echo $base; ?>pengaturan/hapus" method="post" class="pg-confirm">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" id="hapusId" value="">
            <div class="pg-confirm-ico">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
            </div>
            <h3 class="pg-dialog-title" id="judulHapus">Yakin ingin menghapus?</h3>
            <p>Layanan <b id="hapusNama">-</b> akan dihapus. Tindakan ini tidak dapat dibatalkan.</p>
            <div class="pg-foot" style="justify-content:center; margin-top:22px;">
                <button type="button" class="pg-batal" onclick="tutupModal('modalHapus')">Batal</button>
                <button type="submit" class="pg-danger">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<?php if ($flash): ?>
<!-- Modal: Hasil proses (berhasil / gagal) -->
<div id="modalHasil" class="pg-modal open" onclick="if (event.target === this) tutupModal('modalHasil')">
    <div class="pg-dialog" role="alertdialog" aria-modal="true" aria-labelledby="judulHasil" style="max-width:400px;">
        <div class="pg-confirm">
            <?php if ($flash['tipe'] === 'sukses'): ?>
                <div class="pg-confirm-ico ok">
                    <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
            <?php else: ?>
                <div class="pg-confirm-ico">
                    <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
            <?php endif; ?>
            <h3 class="pg-dialog-title" id="judulHasil"><?php echo e($flash['judul']); ?></h3>
            <p><?php echo e($flash['pesan']); ?></p>
            <div class="pg-foot" style="justify-content:center; margin-top:22px;">
                <button type="button" class="pg-primary" style="min-width:120px;" onclick="tutupModal('modalHasil')">Oke</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    function byId(id) { return document.getElementById(id); }
    function ambilAngka(s) { return String(s).replace(/\D/g, ''); }
    function formatAngka(d) { return d === '' ? '' : Number(d).toLocaleString('id-ID'); }

    function bukaModal(id) {
        var m = byId(id);
        m.classList.add('open');
        var f = m.querySelector('input[type="text"]');
        if (f) setTimeout(function () { f.focus(); }, 50);
    }
    function tutupModal(id) { byId(id).classList.remove('open'); }

    // Harga: tampilan berformat (15.000), nilai asli (15000) disimpan di input hidden
    function bindHarga(prefix) {
        var view = byId(prefix + 'HargaView'), hidden = byId(prefix + 'Harga');
        view.addEventListener('input', function () {
            var d = ambilAngka(view.value);
            hidden.value = d;
            view.value = formatAngka(d);
        });
    }
    function setHarga(prefix, angka) {
        var d = ambilAngka(angka);
        byId(prefix + 'Harga').value = d;
        byId(prefix + 'HargaView').value = formatAngka(d);
    }
    bindHarga('tambah');
    bindHarga('edit');

    function bukaModalTambah() {
        byId('formTambah').reset();
        byId('tambahHarga').value = '';
        bukaModal('modalTambah');
    }
    function bukaModalEdit(btn) {
        var d = btn.dataset;
        byId('editId').value = d.id;
        byId('editNama').value = d.nama;
        byId('editDeskripsi').value = d.deskripsi;
        setHarga('edit', d.harga);
        byId('editStatus').value = d.aktif === '1' ? 'aktif' : 'nonaktif';
        bukaModal('modalEdit');
    }
    function bukaModalHapus(btn) {
        byId('hapusId').value = btn.dataset.id;
        byId('hapusNama').textContent = btn.dataset.nama;
        bukaModal('modalHapus');
    }

    // Tekan Esc untuk menutup modal yang terbuka
    document.addEventListener('keydown', function (ev) {
        if (ev.key !== 'Escape') return;
        document.querySelectorAll('.pg-modal.open').forEach(function (m) { m.classList.remove('open'); });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>