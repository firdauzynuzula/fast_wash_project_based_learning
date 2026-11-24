<?php
$title = 'Pesanan Baru';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'pesanan'],
    ['label' => 'Pesanan Baru', 'url' => ''],
];

// Ambil daftar pelanggan terdaftar
$stmtPelanggan = $pdo->query("SELECT id_pelanggan, nama, no_telepon FROM pelanggan ORDER BY nama ASC");
$pelanggan = $stmtPelanggan->fetchAll();
$pelangganJs = array_map(fn($p) => [
    'id'   => (int) $p['id_pelanggan'],
    'nama' => $p['nama'],
    'telp' => $p['no_telepon'],
], $pelanggan);

// Layanan aktif yang unik
$sqlLayanan = "SELECT * FROM (
    SELECT DISTINCT ON (nama_layanan) id_layanan, nama_layanan, harga, satuan, durasi_jam
    FROM layanan
    WHERE is_active = true
    ORDER BY nama_layanan, id_layanan
) t ORDER BY t.id_layanan";
$layanan = $pdo->query($sqlLayanan)->fetchAll();

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

/**
 * Render satu kartu "Layanan Reguler".
 * $i = index pesanan (angka, atau '__I__' untuk template JS).
 */
function renderKartuPesanan($i, array $layanan): string
{
    ob_start();
    ?>
    <div data-order class="tb-card">
        <div class="tb-card-head"><h2 class="tb-h">Layanan Reguler</h2></div>
        <p class="tb-sub">Pilih Layanan:</p>

        <div class="tb-svc-grid">
            <?php foreach ($layanan as $l): ?>
                <?php
                $namaLower = strtolower($l['nama_layanan']);
                if (str_contains($namaLower, 'setrika') && !str_contains($namaLower, 'cuci')) {
                    $iconSvg = '<svg width="20" height="20" fill="none" stroke="#0085ff" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17h17a1 1 0 001-1 7 7 0 00-7-7h-4a4 4 0 00-4 4v4zM3 20h18"/></svg>';
                } elseif (str_contains($namaLower, 'kering')) {
                    $iconSvg = '<svg width="20" height="20" fill="none" stroke="#0085ff" stroke-width="1.8" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="13" r="4"/><path stroke-linecap="round" d="M8 6h.01M12 6h.01"/></svg>';
                } else {
                    $iconSvg = '<svg width="20" height="20" fill="none" stroke="#0085ff" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 3l4 3-2 5-3-1v10H9V10L6 11 4 6l4-3c1 1.5 3 2 4 2s3-.5 4-2z"/></svg>';
                }
                $durasiHari = max(1, (int) ceil((int) $l['durasi_jam'] / 24));
                ?>
                <label class="tb-svc">
                    <input type="radio"
                           name="orders[<?php echo $i; ?>][id_layanan]"
                           value="<?php echo (int) $l['id_layanan']; ?>"
                           data-nama="<?php echo e($l['nama_layanan']); ?>"
                           data-harga="<?php echo (float) $l['harga']; ?>"
                           data-jam="<?php echo (int) ($l['durasi_jam'] ?: 24); ?>">
                    <span class="tb-svc-body">
                        <span><?php echo $iconSvg; ?></span>
                        <span>
                            <span class="tb-svc-nama"><?php echo e($l['nama_layanan']); ?></span>
                            <span class="tb-svc-ket">Rp<?php echo number_format((float) $l['harga'], 0, '.', ','); ?>/<?php echo e($l['satuan']); ?> · <?php echo $durasiHari; ?> hari</span>
                        </span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="tb-inner">
            <div class="tb-inner-top">
                <div>
                    <label class="tb-lbl">Berat (kg)</label>
                    <div class="tb-step">
                        <button type="button" onclick="ubahBerat(this, -1)">&minus;</button>
                        <span class="berat-text">0 kg</span>
                        <input type="hidden" class="berat-input" name="orders[<?php echo $i; ?>][qty]" value="0">
                        <button type="button" onclick="ubahBerat(this, 1)">+</button>
                    </div>
                </div>

                <label class="tb-cepat">
                    <span>
                        <span class="tb-cepat-t">Layanan Cepat</span>
                        <span class="tb-cepat-s">Siap dalam 6 jam | +Rp 10,000</span>
                    </span>
                    <input type="checkbox" class="cek-cepat tb-check" name="orders[<?php echo $i; ?>][layanan_cepat]" value="1">
                </label>
            </div>

            <label class="tb-lbl" style="margin-top: 22px;">Catatan</label>
            <textarea class="tb-ta" name="orders[<?php echo $i; ?>][catatan]" placeholder="noda, bahan halus, pewangi"></textarea>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .tb-grid { display: grid; grid-template-columns: 2.07fr 1fr; gap: 24px; align-items: start; margin-bottom: 48px; }
    .tb-left { display: flex; flex-direction: column; gap: 24px; min-width: 0; }
    .tb-list { display: flex; flex-direction: column; gap: 24px; }
    .tb-card { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 1px 3px rgba(15,23,42,.04); box-sizing: border-box; }
    .tb-h { font-size: 14px; font-weight: 700; color: #111827; margin: 0; }
    .tb-card-head { padding-bottom: 16px; border-bottom: 1px solid #eef2f7; margin-bottom: 18px; }
    .tb-sub { font-size: 12px; color: #374151; margin: 0 0 14px; }
    .tb-lbl { display: block; font-size: 12px; color: #374151; margin-bottom: 8px; }

    /* Pelanggan */
    .tb-pel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .tb-link { display: inline-flex; align-items: center; gap: 6px; background: none; border: 0; padding: 0; color: #0085ff; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .tb-link:hover { text-decoration: underline; }
    .tb-two { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 14px; }
    .tb-rel { position: relative; }
    .tb-inp { width: 100%; height: 42px; box-sizing: border-box; border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; padding: 0 16px; font-size: 13px; color: #374151; outline: none; font-family: inherit; }
    .tb-inp.sel { appearance: none; -webkit-appearance: none; -moz-appearance: none; padding-right: 44px; cursor: pointer; }
    .tb-inp.sel.kosong { color: #9ca3af; }
    .tb-inp.tel { padding-left: 42px; }
    .tb-inp::placeholder { color: #9ca3af; }
    .tb-inp:focus, .tb-ta:focus { border-color: #0085ff; box-shadow: 0 0 0 3px rgba(0,133,255,.12); }
    .tb-inp[readonly] { background: #fff; cursor: default; }
    .tb-ico-l { position: absolute; top: 0; bottom: 0; left: 14px; display: flex; align-items: center; pointer-events: none; }
    .tb-ico-r { position: absolute; top: 0; bottom: 0; right: 16px; display: flex; align-items: center; pointer-events: none; }

    /* Kartu layanan */
    .tb-svc-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .tb-svc { position: relative; display: block; cursor: pointer; }
    .tb-svc input { position: absolute; opacity: 0; pointer-events: none; }
    .tb-svc-body { display: flex; flex-direction: column; justify-content: space-between; min-height: 116px; box-sizing: border-box; padding: 14px 14px 12px; border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; transition: border-color .15s, box-shadow .15s; }
    .tb-svc:hover .tb-svc-body { border-color: #93c5fd; }
    .tb-svc input:checked + .tb-svc-body { border-color: #0085ff; box-shadow: 0 0 0 1px #0085ff; }
    .tb-svc-nama { display: block; font-size: 12px; font-weight: 700; color: #111827; }
    .tb-svc-ket { display: block; font-size: 11px; color: #9ca3af; margin-top: 2px; }

    /* Kotak dalam: Berat + Layanan Cepat + Catatan */
    .tb-inner { margin-top: 22px; border: 1px solid #eef2f7; border-radius: 16px; padding: 22px 24px 24px; box-sizing: border-box; }
    .tb-inner-top { display: grid; grid-template-columns: 228px 1fr; gap: 22px; align-items: end; }
    .tb-step { display: flex; align-items: center; gap: 8px; box-sizing: border-box; border: 1px solid #eef2f7; border-radius: 12px; padding: 5px; }
    .tb-step button { width: 34px; height: 34px; border: 0; border-radius: 10px; background: #e8eff8; color: #374151; font-size: 16px; line-height: 1; cursor: pointer; flex-shrink: 0; }
    .tb-step button:hover { background: #d9e6f5; }
    .tb-step .berat-text { flex: 1; text-align: center; font-size: 13px; font-weight: 700; color: #111827; }

    .tb-cepat { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: #f6f9fd; border-radius: 12px; padding: 18px 22px; cursor: pointer; box-sizing: border-box; }
    .tb-cepat-t { display: block; font-size: 12px; font-weight: 700; color: #111827; }
    .tb-cepat-s { display: block; font-size: 11px; color: #6b7280; margin-top: 3px; }
    .tb-check { appearance: none; -webkit-appearance: none; width: 20px; height: 20px; border: 1.5px solid #cbd5e1; border-radius: 6px; background: #fff; cursor: pointer; flex-shrink: 0; margin: 0; }
    .tb-check:checked { background: #0085ff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 13l4 4L19 7'/%3E%3C/svg%3E") center/70% no-repeat; border-color: #0085ff; }
    .tb-ta { width: 100%; height: 92px; box-sizing: border-box; border: 1px solid #e5e7eb; border-radius: 14px; padding: 14px 16px; font-size: 13px; color: #374151; outline: none; resize: vertical; font-family: inherit; background: #fff; }
    .tb-ta::placeholder { color: #9ca3af; }

    /* Kolom kanan */
    .tb-right { position: sticky; top: 0; align-self: start; display: flex; flex-direction: column; gap: 16px; min-width: 0; max-height: calc(100vh - 104px); }
    .tb-btn-add { flex-shrink: 0; display: block; width: 100%; height: 46px; box-sizing: border-box; border: 1.5px solid #0085ff; border-radius: 14px; background: #fff; color: #0085ff; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .tb-btn-add:hover { background: #f0f7ff; }
    .tb-sum { padding: 24px; display: flex; flex-direction: column; min-height: 0; flex: 1; overflow: hidden; }
    .tb-sum-title { flex-shrink: 0; padding-bottom: 16px; border-bottom: 1px solid #eef2f7; margin-bottom: 18px; }
    #ringkasanItems { flex: 1; min-height: 0; max-height: calc(100vh - 460px); overflow-y: auto; padding-right: 4px; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
    #ringkasanItems::-webkit-scrollbar { display: block; width: 4px; }
    #ringkasanItems::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
    .tb-row { display: flex; justify-content: space-between; gap: 12px; font-size: 12px; margin-bottom: 14px; line-height: 1.3; min-height: 16px; }
    .tb-row .k { color: #374151; }
    .tb-row .v { color: #111827; font-weight: 500; text-align: right; max-width: 65%; word-break: break-word; overflow-wrap: anywhere; }
    .tb-sep { border: 0; border-top: 1px solid #eef2f7; margin: 4px 0 14px; }
    .tb-total { flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding-top: 20px; border-top: 1px solid #eef2f7; min-height: 20px; }
    .tb-total .k { font-size: 13px; font-weight: 700; color: #111827; }
    .tb-total .v { font-size: 15px; font-weight: 700; color: #0085ff; }

    .tb-pay-lbl { flex-shrink: 0; font-size: 12px; color: #374151; margin: 18px 0 10px; }
    .tb-pills { flex-shrink: 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .tb-pill { cursor: pointer; display: block; }
    .tb-pill input { position: absolute; opacity: 0; pointer-events: none; }
    .tb-pill span { display: block; box-sizing: border-box; text-align: center; padding: 10px 0; border-radius: 12px; background: #e8eff8; border: 1.5px solid transparent; font-size: 12px; font-weight: 500; color: #374151; }
    .tb-pill input:checked + span { background: #fff; border-color: #0085ff; color: #0085ff; }

    .tb-btn-lunas { flex-shrink: 0; display: block; width: 100%; height: 44px; box-sizing: border-box; margin-top: 22px; border: 1.5px solid #0085ff; border-radius: 24px; background: #fff; color: #0085ff; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .tb-btn-lunas.aktif { background: #e6f2ff; }
    .tb-btn-lunas.belum { border-color: #f59e0b; color: #d97706; background: #fffbeb; }
    .tb-btn-buat { flex-shrink: 0; display: block; width: 100%; height: 46px; box-sizing: border-box; margin-top: 12px; border: 0; border-radius: 24px; background: #0085ff; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 10px rgba(0,133,255,.28); font-family: inherit; }
    .tb-btn-buat:hover { background: #0070d8; }

    .tb-alert { margin-bottom: 20px; padding: 16px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 14px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; }
    .tb-alert button { background: none; border: 0; color: #ef4444; font-weight: 700; font-size: 18px; cursor: pointer; }


    /* Modal Tambah Pelanggan */
    .pm-overlay { display: none; position: fixed; inset: 0; z-index: 60; background: rgba(15,23,42,.38); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); align-items: center; justify-content: center; padding: 16px; }
    .pm-overlay.show { display: flex; }
    .pm-box { background: #fff; border-radius: 24px; width: 100%; max-width: 560px; padding: 32px; box-sizing: border-box; box-shadow: 0 24px 60px rgba(15,23,42,.25); position: relative; }
    .pm-title { font-size: 24px; font-weight: 700; color: #111827; margin: 0; }
    .pm-desc { font-size: 14px; color: #6b7280; margin: 6px 0 26px; }
    .pm-close { position: absolute; top: 28px; right: 28px; background: none; border: 0; cursor: pointer; padding: 4px; color: #6b7280; display: flex; }
    .pm-close:hover { color: #111827; }
    .pm-lbl { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 10px; }
    .pm-lbl em { color: #ef4444; font-style: normal; margin-left: 3px; }
    .pm-field { position: relative; margin-bottom: 22px; }
    .pm-field svg { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); pointer-events: none; }
    .pm-inp { width: 100%; height: 48px; box-sizing: border-box; border: 1px solid #e5e7eb; border-radius: 14px; padding: 0 16px 0 46px; font-size: 14px; color: #111827; outline: none; font-family: inherit; background: #fff; }
    .pm-inp::placeholder { color: #9ca3af; }
    .pm-inp:focus { border-color: #0085ff; box-shadow: 0 0 0 3px rgba(0,133,255,.12); }
    .pm-inp.err { border-color: #ef4444; }
    .pm-error { display: none; font-size: 13px; color: #dc2626; margin: -8px 0 16px; }
    .pm-error.show { display: block; }
    .pm-actions { display: flex; justify-content: flex-end; gap: 14px; margin-top: 30px; }
    .pm-btn { height: 46px; border-radius: 14px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; padding: 0 30px; }
    .pm-btn-batal { background: #fff; border: 1px solid #d1d5db; color: #374151; }
    .pm-btn-batal:hover { background: #f9fafb; }
    .pm-btn-simpan { background: #0085ff; border: 1px solid #0085ff; color: #fff; box-shadow: 0 4px 10px rgba(0,133,255,.25); }
    .pm-btn-simpan:hover { background: #0070d8; }
    .pm-btn-simpan:disabled { opacity: .7; cursor: wait; }

    .pm-ok { max-width: 600px; text-align: center; padding: 60px 32px 56px; }
    .pm-ok-ico { width: 74px; height: 74px; border-radius: 50%; background: #0085ff; margin: 0 auto 44px; display: flex; align-items: center; justify-content: center; }
    .pm-ok-title { font-size: 24px; font-weight: 700; color: #0f172a; margin: 0; }
    .pm-ok-sub { font-size: 14px; color: #6b7280; margin: 6px 0 0; }

    @media (max-width: 1000px) {
        .tb-grid { grid-template-columns: 1fr; }
        .tb-right { position: static; max-height: none; }
        #ringkasanItems { max-height: none; }
    }
    @media (max-width: 640px) {
        .tb-two, .tb-svc-grid, .tb-inner-top { grid-template-columns: 1fr; }
    }
</style>

<?php if ($flashError): ?>
    <div class="tb-alert">
        <span><?php echo e($flashError); ?></span>
        <button type="button" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<form id="formPesanan" action="<?php echo $base; ?>pesanan/proses_tambah" method="post" class="tb-grid">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="status_bayar" id="inputStatusBayar" value="">

    <!-- Kolom kiri -->
    <div class="tb-left">

        <!-- Pelanggan -->
        <div class="tb-card">
            <div class="tb-pel-head">
                <h2 class="tb-h">Pelanggan</h2>
                <button type="button" id="btnModePelanggan" class="tb-link" onclick="bukaModalPelanggan()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                    <span>Tambah Pelanggan</span>
                </button>
            </div>

            <div class="tb-two">
                <div>
                    <label class="tb-lbl">Nama Lengkap</label>
                    <div class="tb-rel">
                        <!-- Mode pelanggan terdaftar -->
                        <select name="id_pelanggan" id="selectPelanggan" class="tb-inp sel kosong" onchange="pilihPelangganChange()">
                            <option value="">Pilih Nama Pelanggan</option>
                            <?php foreach ($pelanggan as $p): ?>
                                <option value="<?php echo (int) $p['id_pelanggan']; ?>"
                                        data-nama="<?php echo e($p['nama']); ?>"
                                        data-telepon="<?php echo e($p['no_telepon']); ?>"><?php echo e($p['nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="tb-ico-r" id="chevPelanggan">
                            <svg width="16" height="16" fill="none" stroke="#9ca3af" stroke-width="2" viewBox="0 0 24 24" style="display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="tb-lbl">No. WhatsApp</label>
                    <div class="tb-rel">
                        <div class="tb-ico-l">
                            <svg width="16" height="16" fill="none" stroke="#9ca3af" stroke-width="2" viewBox="0 0 24 24" style="display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <input type="text" name="telepon_baru" id="inputTelepon" class="tb-inp tel" placeholder="0812 3456 7890" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu Layanan Reguler (bertambah ke bawah) -->
        <div id="daftarPesanan" class="tb-list">
            <?php echo renderKartuPesanan(0, $layanan); ?>
        </div>
    </div>

    <!-- Kolom kanan -->
    <div class="tb-right">
        <button type="button" class="tb-btn-add" onclick="tambahPesanan()">+ Tambah Pesanan</button>

        <div class="tb-card tb-sum">
            <div class="tb-sum-title"><h2 class="tb-h">Ringkasan Pesanan</h2></div>

            <div id="ringkasanItems"></div>

            <div class="tb-total">
                <span class="k">Total</span>
                <span class="v" id="ringkasanTotalHarga"></span>
            </div>

            <div class="tb-pay-lbl">Pembayaran</div>
            <div class="tb-pills">
                <?php foreach (['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'] as $val => $lbl): ?>
                    <label class="tb-pill">
                        <input type="radio" name="metode_pembayaran" value="<?php echo $val; ?>" data-metode="<?php echo $lbl; ?>">
                        <span><?php echo $lbl; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="button" id="btnStatusBayar" class="tb-btn-lunas" onclick="toggleStatusBayar()">Lunas</button>
            <button type="submit" class="tb-btn-buat">Buat Pesanan</button>
        </div>
    </div>
</form>

<!-- Modal Tambah Pelanggan -->
<div id="modalPelanggan" class="pm-overlay" onclick="if (event.target === this) tutupModalPelanggan()">
    <div class="pm-box">
        <button type="button" class="pm-close" onclick="tutupModalPelanggan()" aria-label="Tutup">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
        <h3 class="pm-title">Tambah Pelanggan</h3>
        <p class="pm-desc">Masukkan data pelanggan baru untuk mulai mencatat pesanan</p>

        <label class="pm-lbl" for="pmNama">Nama Lengkap<em>*</em></label>
        <div class="pm-field">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 016-6h4a6 6 0 016 6v1"/></svg>
            <input type="text" id="pmNama" class="pm-inp" placeholder="Contoh: Alyssa Naura" autocomplete="off">
        </div>

        <label class="pm-lbl" for="pmTelepon">Nomor WhatsApp<em>*</em></label>
        <div class="pm-field" style="margin-bottom: 0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            <input type="text" id="pmTelepon" class="pm-inp" placeholder="Contoh: 0812-3456-7890" autocomplete="off" inputmode="tel">
        </div>
        <div id="pmError" class="pm-error" style="margin: 12px 0 0;"></div>

        <div class="pm-actions">
            <button type="button" class="pm-btn pm-btn-batal" onclick="tutupModalPelanggan()">Batal</button>
            <button type="button" id="pmSimpan" class="pm-btn pm-btn-simpan" onclick="simpanPelanggan()">Simpan Pelanggan</button>
        </div>
    </div>
</div>

<!-- Modal sukses -->
<div id="modalPelangganOk" class="pm-overlay">
    <div class="pm-box pm-ok">
        <div class="pm-ok-ico">
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
        </div>
        <h3 class="pm-ok-title">Pelanggan berhasil ditambahkan</h3>
        <p class="pm-ok-sub">data pelanggan tersimpan pada daftar pelanggan</p>
    </div>
</div>

<!-- Template kartu pesanan tambahan -->
<template id="tplOrder"><?php echo renderKartuPesanan('__I__', $layanan); ?></template>

<script>
let jumlahPesanan = 1;

const fmt = n => Number(n).toLocaleString('en-US');
const fmtQty = n => (Number.isInteger(n) ? String(n) : n.toFixed(1));
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const baris = (k, v) => `<div class="tb-row"><span class="k">${k}</span><span class="v">${v}</span></div>`;

/* ---------- Pelanggan ---------- */
function pilihPelangganChange() {
    const sel = document.getElementById('selectPelanggan');
    const opt = sel.options[sel.selectedIndex];
    sel.classList.toggle('kosong', !sel.value);
    document.getElementById('inputTelepon').value = (opt && opt.value) ? (opt.getAttribute('data-telepon') || '') : '';
    hitungRingkasan();
}

function namaPelanggan() {
    const sel = document.getElementById('selectPelanggan');
    const opt = sel.options[sel.selectedIndex];
    return (opt && opt.value) ? (opt.getAttribute('data-nama') || '') : '';
}

/* ---------- Tambah kartu pesanan (ke bawah) ---------- */
function tambahPesanan() {
    const html = document.getElementById('tplOrder').innerHTML.replaceAll('__I__', jumlahPesanan);
    document.getElementById('daftarPesanan').insertAdjacentHTML('beforeend', html);
    jumlahPesanan++;
    hitungRingkasan();
    const cards = document.querySelectorAll('[data-order]');
    const lastCard = cards[cards.length - 1];
    const mainEl = document.querySelector('main');
    if (mainEl && lastCard) {
        const mainRect = mainEl.getBoundingClientRect();
        const cardRect = lastCard.getBoundingClientRect();
        mainEl.scrollTo({
            top: mainEl.scrollTop + (cardRect.top - mainRect.top) - 20,
            behavior: 'smooth'
        });
    }
    const itemsEl = document.getElementById('ringkasanItems');
    if (itemsEl) {
        itemsEl.scrollTo({ top: itemsEl.scrollHeight, behavior: 'smooth' });
    }
}

/* ---------- Berat ---------- */
function ubahBerat(btn, delta) {
    const card  = btn.closest('[data-order]');
    const input = card.querySelector('.berat-input');
    let val = parseFloat(input.value) || 0;
    val = Math.max(0, val + delta);
    input.value = val;
    card.querySelector('.berat-text').textContent = fmtQty(val) + ' kg';
    hitungRingkasan();
}

/* ---------- Status pembayaran: kosong -> Lunas <-> Belum Lunas ---------- */
function toggleStatusBayar() {
    const el = document.getElementById('inputStatusBayar');
    el.value = el.value === 'lunas' ? 'belum_lunas' : 'lunas';
    hitungRingkasan();
}

/* ---------- Ringkasan: kosong sampai form diisi ---------- */
function hitungRingkasan() {
    const nama = namaPelanggan();
    const cards = document.querySelectorAll('[data-order]');
    let html = '', total = 0, maxJam = 0, adaLayanan = false;

    cards.forEach((card, n) => {
        const lay   = card.querySelector('input[data-harga]:checked');
        const qty   = parseFloat(card.querySelector('.berat-input').value) || 0;
        const cepat = card.querySelector('.cek-cepat').checked;
        const catatan = card.querySelector('textarea').value.trim();

        if (n > 0) html += '<hr class="tb-sep">';

        if (lay) {
            const harga = parseFloat(lay.dataset.harga);
            const jam   = cepat ? 6 : (parseInt(lay.dataset.jam) || 24);
            adaLayanan = true;
            total += qty * harga + (cepat ? 10000 : 0);
            maxJam = Math.max(maxJam, jam);

            html += baris('Pelanggan', esc(nama))
                  + baris('Layanan', esc(lay.dataset.nama))
                  + baris('Berat', qty > 0 ? `${fmtQty(qty)} kg × Rp ${fmt(harga)}` : '')
                  + baris('Layanan Cepat', cepat ? '10,000' : '-')
                  + baris('Catatan', esc(catatan));
        } else {
            html += baris('Pelanggan', esc(nama))
                  + baris('Layanan', '')
                  + baris('Berat', '')
                  + baris('Layanan Cepat', '')
                  + baris('Catatan', esc(catatan));
        }
    });

    const metode = document.querySelector('input[name="metode_pembayaran"]:checked');
    const status = document.getElementById('inputStatusBayar').value;
    const siap   = adaLayanan ? (maxJam < 24 ? `dalam ${maxJam} jam` : `dalam ${Math.ceil(maxJam / 24)} hari`) : '';

    if (cards.length > 1) html += '<hr class="tb-sep">';
    html += baris('Pembayaran', metode ? esc(metode.dataset.metode) : '')
          + baris('Status Pembayaran', status === 'lunas' ? 'Lunas' : (status === 'belum_lunas' ? 'Belum Lunas' : ''))
          + baris('Pesanan Siap', siap);

    const btn = document.getElementById('btnStatusBayar');
    btn.className = 'tb-btn-lunas' + (status === 'lunas' ? ' aktif' : (status === 'belum_lunas' ? ' belum' : ''));
    btn.textContent = status === 'belum_lunas' ? 'Belum Lunas' : 'Lunas';

    document.getElementById('ringkasanItems').innerHTML = html;
    document.getElementById('ringkasanTotalHarga').textContent = adaLayanan ? 'Rp ' + fmt(total) : '';
}


/* ---------- Modal Tambah Pelanggan ---------- */
const BASE_URL = <?php echo json_encode($base); ?>;
const CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;

function bukaModalPelanggan() {
    document.getElementById('pmNama').value = '';
    document.getElementById('pmTelepon').value = '';
    pmTampilError('');
    document.getElementById('modalPelanggan').classList.add('show');
    setTimeout(() => document.getElementById('pmNama').focus(), 50);
}
function tutupModalPelanggan() {
    document.getElementById('modalPelanggan').classList.remove('show');
}
function pmTampilError(pesan, fieldIds) {
    const el = document.getElementById('pmError');
    el.textContent = pesan;
    el.classList.toggle('show', !!pesan);
    ['pmNama', 'pmTelepon'].forEach(id => document.getElementById(id).classList.remove('err'));
    (fieldIds || []).forEach(id => document.getElementById(id).classList.add('err'));
}

async function simpanPelanggan() {
    const nama = document.getElementById('pmNama').value.trim();
    const telp = document.getElementById('pmTelepon').value.trim();

    if (!nama) { pmTampilError('Nama lengkap wajib diisi.', ['pmNama']); return; }
    if (!telp) { pmTampilError('Nomor WhatsApp wajib diisi.', ['pmTelepon']); return; }
    pmTampilError('');

    const btn = document.getElementById('pmSimpan');
    btn.disabled = true;

    try {
        const fd = new FormData();
        fd.append('csrf', CSRF_TOKEN);
        fd.append('nama', nama);
        fd.append('telepon', telp);

        const res = await fetch(BASE_URL + 'pesanan/proses_tambah_pelanggan', { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.ok) {
            pmTampilError(data.message || 'Gagal menyimpan pelanggan.');
            return;
        }

        // Tambahkan ke dropdown & langsung pilih
        const sel = document.getElementById('selectPelanggan');
        const opt = document.createElement('option');
        opt.value = data.id;
        opt.textContent = data.nama;
        opt.setAttribute('data-nama', data.nama);
        opt.setAttribute('data-telepon', data.telp);
        sel.appendChild(opt);
        sel.value = String(data.id);
        pilihPelangganChange();

        tutupModalPelanggan();
        const ok = document.getElementById('modalPelangganOk');
        ok.classList.add('show');
        setTimeout(() => ok.classList.remove('show'), 1800);
    } catch (err) {
        pmTampilError('Terjadi kesalahan. Silakan coba lagi.');
    } finally {
        btn.disabled = false;
    }
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') tutupModalPelanggan();
    if (e.key === 'Enter' && document.getElementById('modalPelanggan').classList.contains('show')) {
        e.preventDefault();
        simpanPelanggan();
    }
});
document.getElementById('modalPelangganOk').addEventListener('click', function () { this.classList.remove('show'); });

document.getElementById('formPesanan').addEventListener('change', hitungRingkasan);

document.getElementById('formPesanan').addEventListener('submit', function (e) {
    if (!namaPelanggan()) {
        e.preventDefault();
        alert('Silakan pilih pelanggan atau tambah pelanggan baru.');
        return;
    }
    const kosong = [...document.querySelectorAll('[data-order]')].some(c => !c.querySelector('input[data-harga]:checked'));
    if (kosong) {
        e.preventDefault();
        alert('Silakan pilih layanan pada setiap pesanan.');
        return;
    }
    const beratKosong = [...document.querySelectorAll('.berat-input')].some(i => !(parseFloat(i.value) > 0));
    if (beratKosong) {
        e.preventDefault();
        alert('Berat cucian harus lebih dari 0 kg.');
    }
});

hitungRingkasan();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>