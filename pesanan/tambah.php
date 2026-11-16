<?php
$title = 'Pesanan Baru';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'pesanan'],
    ['label' => 'Pesanan Baru', 'url' => ''],
];

// Pelanggan terdaftar
$pelanggan = $pdo->query("SELECT id_pelanggan, nama, no_telepon FROM pelanggan ORDER BY nama ASC")->fetchAll();
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
                <div>
                    <label class="tb-lbl">Pembayaran</label>
                    <div class="tb-pills">
                        <?php foreach (['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'] as $val => $lbl): ?>
                            <label class="tb-pill">
                                <input type="radio" name="orders[<?php echo $i; ?>][metode_pembayaran]" value="<?php echo $val; ?>" data-metode="<?php echo $lbl; ?>">
                                <span><?php echo $lbl; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <label class="tb-cepat">
                <span>
                    <span class="tb-cepat-t">Layanan Cepat</span>
                    <span class="tb-cepat-s">Siap dalam 6 jam | +Rp 10,000</span>
                </span>
                <input type="checkbox" class="cek-cepat tb-check" name="orders[<?php echo $i; ?>][layanan_cepat]" value="1">
            </label>

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
    .tb-grid { display: grid; grid-template-columns: 2.07fr 1fr; gap: 24px; align-items: start; margin-bottom: 24px; }
    .tb-left { display: flex; flex-direction: column; gap: 24px; min-width: 0; }
    .tb-list { display: flex; flex-direction: column; gap: 24px; }
    .tb-card { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 1px 3px rgba(15,23,42,.04); box-sizing: border-box; }
    .tb-h { font-size: 14px; font-weight: 700; color: #111827; margin: 0; }
    .tb-card-head { padding-bottom: 16px; border-bottom: 1px solid #eef2f7; margin-bottom: 18px; }
    .tb-sub { font-size: 12px; color: #374151; margin: 0 0 14px; }
    .tb-lbl { display: block; font-size: 12px; color: #374151; margin-bottom: 8px; }

    /* Pelanggan */
    .tb-two { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 16px; }
    .tb-rel { position: relative; }
    .tb-inp { width: 100%; height: 40px; box-sizing: border-box; border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; padding: 0 44px 0 16px; font-size: 13px; color: #374151; outline: none; font-family: inherit; }
    .tb-inp.tel { padding: 0 16px 0 42px; }
    .tb-inp::placeholder { color: #9ca3af; }
    .tb-inp:focus, .tb-ta:focus { border-color: #0085ff; box-shadow: 0 0 0 3px rgba(0,133,255,.12); }
    .tb-ico-l { position: absolute; top: 0; bottom: 0; left: 14px; display: flex; align-items: center; pointer-events: none; }
    .tb-ico-r { position: absolute; top: 0; bottom: 0; right: 16px; display: flex; align-items: center; pointer-events: none; }
    input[list]::-webkit-calendar-picker-indicator { display: none !important; }

    /* Kartu layanan */
    .tb-svc-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .tb-svc { position: relative; display: block; cursor: pointer; }
    .tb-svc input { position: absolute; opacity: 0; pointer-events: none; }
    .tb-svc-body { display: flex; flex-direction: column; justify-content: space-between; min-height: 106px; box-sizing: border-box; padding: 14px 14px 12px; border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; transition: border-color .15s, box-shadow .15s; }
    .tb-svc:hover .tb-svc-body { border-color: #93c5fd; }
    .tb-svc input:checked + .tb-svc-body { border-color: #0085ff; box-shadow: 0 0 0 1px #0085ff; }
    .tb-svc-nama { display: block; font-size: 12px; font-weight: 700; color: #111827; }
    .tb-svc-ket { display: block; font-size: 11px; color: #9ca3af; margin-top: 2px; }

    /* Kotak dalam */
    .tb-inner { margin-top: 22px; border: 1px solid #eef2f7; border-radius: 16px; padding: 22px; box-sizing: border-box; }
    .tb-inner-top { display: grid; grid-template-columns: auto 1fr; gap: 36px; align-items: start; }
    .tb-step { display: flex; align-items: center; gap: 8px; width: 210px; max-width: 100%; box-sizing: border-box; border: 1px solid #eef2f7; border-radius: 12px; padding: 5px; }
    .tb-step button { width: 34px; height: 34px; border: 0; border-radius: 10px; background: #e8eff8; color: #374151; font-size: 16px; line-height: 1; cursor: pointer; flex-shrink: 0; }
    .tb-step button:hover { background: #d9e6f5; }
    .tb-step .berat-text { flex: 1; text-align: center; font-size: 13px; font-weight: 700; color: #111827; }
    .tb-pills { display: flex; flex-wrap: wrap; gap: 10px; }
    .tb-pill { cursor: pointer; }
    .tb-pill input { position: absolute; opacity: 0; pointer-events: none; }
    .tb-pill span { display: block; min-width: 83px; box-sizing: border-box; text-align: center; padding: 9px 18px; border-radius: 10px; background: #e8eff8; border: 1.5px solid transparent; font-size: 12px; font-weight: 500; color: #374151; }
    .tb-pill input:checked + span { background: #fff; border-color: #0085ff; color: #0085ff; }

    .tb-cepat { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: #f6f9fd; border-radius: 12px; padding: 16px 18px; margin-top: 20px; cursor: pointer; }
    .tb-cepat-t { display: block; font-size: 12px; font-weight: 700; color: #111827; }
    .tb-cepat-s { display: block; font-size: 11px; color: #6b7280; margin-top: 3px; }
    .tb-check { appearance: none; -webkit-appearance: none; width: 20px; height: 20px; border: 1.5px solid #cbd5e1; border-radius: 6px; background: #fff; cursor: pointer; flex-shrink: 0; margin: 0; }
    .tb-check:checked { background: #0085ff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 13l4 4L19 7'/%3E%3C/svg%3E") center/70% no-repeat; border-color: #0085ff; }
    .tb-ta { width: 100%; height: 84px; box-sizing: border-box; border: 1px solid #e5e7eb; border-radius: 14px; padding: 14px 16px; font-size: 13px; color: #374151; outline: none; resize: vertical; font-family: inherit; background: #fff; }
    .tb-ta::placeholder { color: #9ca3af; }

    /* Kolom kanan */
    .tb-right { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    .tb-btn-add { display: block; width: 100%; height: 42px; box-sizing: border-box; border: 1.5px solid #0085ff; border-radius: 14px; background: #fff; color: #0085ff; font-size: 13px; font-weight: 500; cursor: pointer; font-family: inherit; }
    .tb-btn-add:hover { background: #f0f7ff; }
    .tb-sum { padding: 22px; }
    .tb-sum-title { padding-bottom: 14px; border-bottom: 1px solid #eef2f7; margin-bottom: 16px; }
    .tb-row { display: flex; justify-content: space-between; gap: 12px; font-size: 12px; margin-bottom: 14px; line-height: 1.3; min-height: 16px; }
    .tb-row .k { color: #374151; flex-shrink: 0; }
    .tb-row .v { color: #111827; font-weight: 500; text-align: right; min-width: 0; overflow-wrap: anywhere; }
    .tb-sep { border: 0; border-top: 1px solid #eef2f7; margin: 4px 0 14px; }
    .tb-total { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; padding-top: 18px; border-top: 1px solid #eef2f7; min-height: 20px; }
    .tb-total .k { font-size: 13px; font-weight: 700; color: #111827; }
    .tb-total .v { font-size: 15px; font-weight: 700; color: #0085ff; }
    .tb-btn-lunas { display: block; width: 100%; height: 40px; box-sizing: border-box; margin-top: 20px; border: 1.5px solid #0085ff; border-radius: 12px; background: #fff; color: #0085ff; font-size: 13px; font-weight: 500; cursor: pointer; font-family: inherit; }
    .tb-btn-lunas.aktif { background: #e6f2ff; }
    .tb-btn-lunas.belum { border-color: #f59e0b; color: #d97706; background: #fffbeb; }
    .tb-btn-buat { display: block; width: 100%; height: 44px; box-sizing: border-box; margin-top: 12px; border: 0; border-radius: 12px; background: #0085ff; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 10px rgba(0,133,255,.28); font-family: inherit; }
    .tb-btn-buat:hover { background: #0070d8; }

    .tb-alert { margin-bottom: 20px; padding: 16px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 14px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; }
    .tb-alert button { background: none; border: 0; color: #ef4444; font-weight: 700; font-size: 18px; cursor: pointer; }

    @media (max-width: 1000px) {
        .tb-grid { grid-template-columns: 1fr; }
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
            <h2 class="tb-h">Pelanggan</h2>
            <div class="tb-two">
                <div>
                    <label class="tb-lbl">Nama Lengkap</label>
                    <div class="tb-rel">
                        <input type="hidden" name="id_pelanggan" id="inputIdPelanggan" value="">
                        <input type="text" name="nama_baru" id="inputNama" list="listPelanggan" autocomplete="off"
                               placeholder="Pilih Nama Pelanggan" oninput="pelangganInput()" class="tb-inp">
                        <datalist id="listPelanggan">
                            <?php foreach ($pelanggan as $p): ?>
                                <option value="<?php echo e($p['nama']); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <div class="tb-ico-r">
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
                        <input type="text" name="telepon_baru" id="inputTelepon" placeholder="0812 3456 7890" class="tb-inp tel">
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

            <hr class="tb-sep">
            <div class="tb-row"><span class="k">Pembayaran</span><span class="v" id="ringkasanMetodeBayar"></span></div>
            <div class="tb-row"><span class="k">Status Pembayaran</span><span class="v" id="ringkasanStatusBayar"></span></div>
            <div class="tb-row"><span class="k">Pesanan Siap</span><span class="v" id="ringkasanPesananSiap"></span></div>

            <div class="tb-total">
                <span class="k">Total</span>
                <span class="v" id="ringkasanTotalHarga"></span>
            </div>

            <button type="button" id="btnStatusBayar" class="tb-btn-lunas" onclick="toggleStatusBayar()">Lunas</button>
            <button type="submit" class="tb-btn-buat">Buat Pesanan</button>
        </div>
    </div>
</form>

<!-- Template kartu pesanan tambahan -->
<template id="tplOrder"><?php echo renderKartuPesanan('__I__', $layanan); ?></template>

<script>
const pelangganData = <?php echo json_encode($pelangganJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
let jumlahPesanan = 1;

const fmt = n => Number(n).toLocaleString('en-US');
const fmtQty = n => (Number.isInteger(n) ? String(n) : n.toFixed(1));
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const baris = (k, v) => `<div class="tb-row"><span class="k">${k}</span><span class="v">${v}</span></div>`;

/* ---------- Pelanggan ---------- */
function pelangganInput() {
    const nama = document.getElementById('inputNama').value.trim();
    const tel  = document.getElementById('inputTelepon');
    const idEl = document.getElementById('inputIdPelanggan');
    const m = pelangganData.find(p => p.nama.toLowerCase() === nama.toLowerCase());

    if (m) {
        idEl.value = m.id;
        tel.value = m.telp || '';
        tel.readOnly = true;
    } else {
        if (tel.readOnly) tel.value = '';
        idEl.value = '';
        tel.readOnly = false;
    }
    hitungRingkasan();
}

/* ---------- Scroll ke kartu baru ----------
   Tidak memakai scrollIntoView(): fungsi itu ikut menggeser elemen induk yang
   overflow-nya hidden (mis. wrapper layout), sehingga seluruh layout naik dan
   muncul ruang kosong di bawah. Di sini hanya container yang memang bisa di-scroll
   yang digeser. */
function scrollKeKartu(el) {
    let p = el.parentElement;
    while (p && p !== document.body && p !== document.documentElement) {
        const oy = getComputedStyle(p).overflowY;
        if ((oy === 'auto' || oy === 'scroll') && p.scrollHeight > p.clientHeight) {
            const top = el.getBoundingClientRect().top - p.getBoundingClientRect().top + p.scrollTop - 24;
            p.scrollTo({ top: top, behavior: 'smooth' });
            return;
        }
        p = p.parentElement;
    }
    const bodyOy = getComputedStyle(document.body).overflowY;
    const htmlOy = getComputedStyle(document.documentElement).overflowY;
    if (bodyOy !== 'hidden' && htmlOy !== 'hidden') {
        window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
    }
}

/* ---------- Tambah kartu pesanan (ke bawah) ---------- */
function tambahPesanan() {
    const html = document.getElementById('tplOrder').innerHTML.replaceAll('__I__', jumlahPesanan);
    document.getElementById('daftarPesanan').insertAdjacentHTML('beforeend', html);
    jumlahPesanan++;
    hitungRingkasan();
    const cards = document.querySelectorAll('[data-order]');
    scrollKeKartu(cards[cards.length - 1]);
}

/* ---------- Berat (mulai dari 0) ---------- */
function ubahBerat(btn, delta) {
    const card  = btn.closest('[data-order]');
    const input = card.querySelector('.berat-input');
    let val = parseFloat(input.value);
    if (isNaN(val)) val = 0;
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
    const nama = document.getElementById('inputNama').value.trim();
    let html = '', total = 0, maxJam = 0, adaLayanan = false;
    const metodeSet = [];

    document.querySelectorAll('[data-order]').forEach((card, n) => {
        const lay     = card.querySelector('input[data-harga]:checked');
        const qty     = parseFloat(card.querySelector('.berat-input').value) || 0;
        const cepat   = card.querySelector('.cek-cepat').checked;
        const metode  = card.querySelector('input[data-metode]:checked');
        const catatan = card.querySelector('.tb-ta').value.trim();

        if (metode && !metodeSet.includes(metode.dataset.metode)) metodeSet.push(metode.dataset.metode);

        if (n > 0) html += '<hr class="tb-sep">';

        if (lay) {
            const harga = parseFloat(lay.dataset.harga);
            const jam   = cepat ? 6 : (parseInt(lay.dataset.jam) || 24);
            adaLayanan = true;
            total += qty * harga + (cepat ? 10000 : 0);
            maxJam = Math.max(maxJam, jam);

            html += baris('Pelanggan', esc(nama))
                  + baris('Layanan', esc(lay.dataset.nama))
                  + baris('Berat', `${fmtQty(qty)} kg × Rp ${fmt(harga)}`)
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

    const status = document.getElementById('inputStatusBayar').value;
    const btn = document.getElementById('btnStatusBayar');
    btn.className = 'tb-btn-lunas' + (status === 'lunas' ? ' aktif' : (status === 'belum_lunas' ? ' belum' : ''));
    btn.textContent = status === 'belum_lunas' ? 'Belum Lunas' : 'Lunas';

    document.getElementById('ringkasanItems').innerHTML = html;
    document.getElementById('ringkasanMetodeBayar').textContent = metodeSet.join(', ');
    document.getElementById('ringkasanStatusBayar').textContent = status === 'lunas' ? 'Lunas' : (status === 'belum_lunas' ? 'Belum Lunas' : '');
    document.getElementById('ringkasanPesananSiap').textContent = adaLayanan
        ? (maxJam < 24 ? `dalam ${maxJam} jam` : `dalam ${Math.ceil(maxJam / 24)} hari`) : '';
    document.getElementById('ringkasanTotalHarga').textContent = adaLayanan ? 'Rp ' + fmt(total) : '';
}

const formPesanan = document.getElementById('formPesanan');
formPesanan.addEventListener('change', hitungRingkasan);
formPesanan.addEventListener('input', hitungRingkasan); // catatan ikut update saat mengetik

formPesanan.addEventListener('submit', function (e) {
    if (!document.getElementById('inputNama').value.trim()) {
        e.preventDefault();
        alert('Silakan pilih atau isi nama pelanggan.');
        return;
    }
    const kosong = [...document.querySelectorAll('[data-order]')].some(c => !c.querySelector('input[data-harga]:checked'));
    if (kosong) {
        e.preventDefault();
        alert('Silakan pilih layanan pada setiap pesanan.');
        return;
    }
    const beratKosong = [...document.querySelectorAll('.berat-input')].some(i => (parseFloat(i.value) || 0) < 0.5);
    if (beratKosong) {
        e.preventDefault();
        alert('Berat cucian harus lebih dari 0 kg pada setiap pesanan.');
    }
});

hitungRingkasan();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
