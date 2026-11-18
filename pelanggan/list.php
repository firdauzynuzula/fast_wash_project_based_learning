<?php
/**
 * Fast Wash - Daftar Pelanggan
 * Tampilan disesuaikan dengan referensi Figma, memakai data dummy.
 */
$title = 'Data Pelanggan';
$breadcrumb = [
    ['label' => 'Pelanggan', 'url' => 'pelanggan/list'],
    ['label' => 'Data Pelanggan'],
];

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

$pelanggan = [
    ['nama' => 'Alyssa Naura',   'wa' => '+62 821-3456-9846', 'pesanan' => 12, 'pengeluaran' => 540000, 'warna' => 'blue'],
    ['nama' => 'Nadya Alya',     'wa' => '+62 821-3456-9846', 'pesanan' => 9,  'pengeluaran' => 360000, 'warna' => 'slate'],
    ['nama' => 'Indira Sabrina', 'wa' => '+62 821-3456-9846', 'pesanan' => 7,  'pengeluaran' => 280000, 'warna' => 'sky'],
    ['nama' => 'Sarah Amanda',   'wa' => '+62 821-3456-9846', 'pesanan' => 4,  'pengeluaran' => 160000, 'warna' => 'violet'],
    ['nama' => 'Areta Neila',    'wa' => '+62 821-3456-9846', 'pesanan' => 2,  'pengeluaran' => 75000,  'warna' => 'teal'],
    ['nama' => 'Ayusha Marella', 'wa' => '+62 821-3456-9846', 'pesanan' => 1,  'pengeluaran' => 45000,  'warna' => 'indigo'],
];
?>

<style>
/* Seluruh aturan dibatasi ke halaman pelanggan agar tidak mengubah halaman lain. */
.customer-figma { width:100%; max-width:1120px; margin:0 auto; color:#17243b; font-family:inherit; }
.customer-figma * { box-sizing:border-box; }
.customer-figma .cf-intro { display:flex; align-items:center; justify-content:space-between; gap:16px; margin:0 0 18px; }
.customer-figma .cf-eyebrow { margin:0 0 4px; color:#647a9a; font-size:12px; }
.customer-figma .cf-heading { margin:0; color:#17243b; font-size:23px; line-height:1.25; font-weight:750; }
.customer-figma .cf-subtitle { margin:5px 0 0; color:#7184a1; font-size:13px; }
.customer-figma .cf-add { display:inline-flex; align-items:center; justify-content:center; gap:7px; padding:10px 15px; border:0; border-radius:10px; background:#0785f9; color:white; font-size:12px; font-weight:650; cursor:pointer; box-shadow:0 2px 5px #087df022; white-space:nowrap; }
.customer-figma .cf-add:hover { background:#0674dc; }
.customer-figma .cf-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
.customer-figma .cf-stat { min-width:0; min-height:98px; padding:14px 15px; border:1px solid #e8eef7; border-radius:13px; background:#fff; box-shadow:0 2px 5px #1d35570a; }
.customer-figma .cf-stat-top { display:flex; align-items:center; justify-content:space-between; gap:8px; color:#617493; font-size:11px; }
.customer-figma .cf-stat-icon { display:grid; place-items:center; width:29px; height:29px; flex:none; border-radius:50%; background:#edf6ff; color:#0785f9; font-size:15px; }
.customer-figma .cf-stat-value { margin-top:2px; font-size:23px; line-height:1.2; font-weight:750; color:#17243b; }
.customer-figma .cf-stat-note { margin-top:5px; color:#91a2bc; font-size:10px; }
.customer-figma .cf-panel { overflow:hidden; border:1px solid #e8eef7; border-radius:14px; background:#fff; box-shadow:0 2px 7px #1d35570a; }
.customer-figma .cf-panel-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:15px 16px 12px; }
.customer-figma .cf-panel-title { display:flex; align-items:center; gap:9px; min-width:0; font-size:13px; font-weight:650; color:#25344c; }
.customer-figma .cf-panel-symbol { display:grid; place-items:center; width:26px; height:26px; border-radius:8px; background:#edf6ff; color:#0785f9; }
.customer-figma .cf-count { flex:none; padding:4px 8px; border-radius:20px; background:#edf6ff; color:#0877e5; font-size:10px; font-weight:650; }
.customer-figma .cf-tools { display:grid; grid-template-columns:minmax(180px,1fr) 140px 150px auto; gap:8px; padding:0 16px 14px; }
.customer-figma .cf-control { display:flex; align-items:center; gap:7px; min-width:0; height:34px; padding:0 10px; border:1px solid #dfe7f1; border-radius:9px; background:#fff; color:#40516c; font-size:11px; }
.customer-figma .cf-control input,.customer-figma .cf-control select { width:100%; min-width:0; border:0; outline:0; background:transparent; color:#40516c; font:inherit; }
.customer-figma .cf-control input::placeholder { color:#91a2bc; }
.customer-figma .cf-export { justify-content:center; cursor:pointer; background:#f5f9ff; white-space:nowrap; }
.customer-figma .cf-table-wrap { overflow-x:auto; }
.customer-figma table { width:100%; min-width:570px; border-collapse:collapse; text-align:left; }
.customer-figma thead { background:#0785f9; color:#fff; }
.customer-figma th { padding:11px 13px; font-size:10px; font-weight:650; white-space:nowrap; }
.customer-figma td { padding:10px 13px; border-bottom:1px solid #edf1f6; color:#29364d; font-size:10px; white-space:nowrap; }
.customer-figma tbody tr:last-child td { border-bottom:0; }
.customer-figma tbody tr:hover { background:#f8fbff; }
.customer-figma .cf-person { display:flex; align-items:center; gap:9px; }
.customer-figma .cf-avatar { display:grid; place-items:center; width:26px; height:26px; flex:none; border-radius:50%; font-size:9px; font-weight:700; }
.customer-figma .cf-blue { background:#e4f2ff; color:#0877d8; }.customer-figma .cf-slate { background:#e8edf4; color:#52627a; }.customer-figma .cf-sky { background:#e0f3ff; color:#1683bd; }.customer-figma .cf-violet { background:#f1e5ff; color:#9254d8; }.customer-figma .cf-teal { background:#d8fbf2; color:#0d967d; }.customer-figma .cf-indigo { background:#e5eaff; color:#5867d9; }
.customer-figma .cf-number,.customer-figma .cf-money { text-align:center; }
.customer-figma .cf-foot { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 15px; border-top:1px solid #edf1f6; background:#fbfcfe; color:#8293ac; font-size:10px; }
.customer-figma .cf-pages { display:flex; align-items:center; gap:5px; }
.customer-figma .cf-page { min-width:24px; height:24px; padding:0 7px; border:1px solid #dfe7f1; border-radius:6px; background:#fff; color:#52627a; font-size:10px; cursor:pointer; }
.customer-figma .cf-page.active { border-color:#0785f9; background:#0785f9; color:#fff; }
.customer-figma .cf-page:disabled { opacity:.45; cursor:not-allowed; }
.cf-modal { position:fixed; inset:0; z-index:1000; display:none; align-items:center; justify-content:center; padding:18px; background:#10243d80; }
.cf-modal.open { display:flex; }
.cf-modal-card { width:100%; max-width:420px; padding:22px; border-radius:15px; background:#fff; box-shadow:0 18px 55px #10243d35; }
.cf-modal-card h3 { margin:0 0 14px; font-size:18px; font-weight:700; color:#17243b; }
.cf-modal-card label { display:block; margin:10px 0; color:#52627a; font-size:12px; }
.cf-modal-card input { display:block; width:100%; margin-top:5px; padding:9px 10px; border:1px solid #dfe7f1; border-radius:8px; font-size:13px; }
.cf-modal-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:16px; }
.cf-modal-actions button { padding:9px 13px; border:1px solid #dfe7f1; border-radius:8px; background:white; font-size:12px; cursor:pointer; }
.cf-modal-actions button:last-child { border-color:#0785f9; background:#0785f9; color:white; }
.cf-success-overlay { position:fixed; inset:0; z-index:1100; display:none; align-items:center; justify-content:center; padding:20px; background:rgba(9,31,59,.38); backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px); }
.cf-success-overlay.show { display:flex; animation:cfFadeIn .18s ease-out; }
.cf-success-card { width:100%; max-width:324px; min-height:183px; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:28px 22px; border-radius:11px; background:#fff; text-align:center; box-shadow:0 14px 38px rgba(15,35,60,.16); animation:cfPopIn .2s ease-out; }
.cf-success-check { display:grid; place-items:center; width:39px; height:39px; margin-bottom:25px; border-radius:50%; background:#0785f9; color:#fff; }
.cf-success-check svg { width:23px; height:23px; }
.cf-success-title { margin:0; color:#17243b; font-size:13px; line-height:1.35; font-weight:750; }
.cf-success-subtitle { margin:2px 0 0; color:#8796ad; font-size:9px; line-height:1.4; }
@keyframes cfFadeIn { from {opacity:0} to {opacity:1} }
@keyframes cfPopIn { from {opacity:0;transform:translateY(5px) scale(.97)} to {opacity:1;transform:translateY(0) scale(1)} }
@media(max-width:900px) { .customer-figma .cf-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } .customer-figma .cf-tools { grid-template-columns:minmax(160px,1fr) 1fr; } }
@media(max-width:560px) { .customer-figma .cf-intro { align-items:flex-start; flex-direction:column; } .customer-figma .cf-heading { font-size:20px; } .customer-figma .cf-stats { gap:9px; } .customer-figma .cf-stat { padding:11px; min-height:90px; } .customer-figma .cf-tools { grid-template-columns:1fr; } .customer-figma .cf-panel-head { align-items:flex-start; } .customer-figma .cf-foot { align-items:flex-start; flex-direction:column; } }

.cf-control select {
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    background-image: none !important;
}

/* Penyesuaian ukuran tampilan halaman pelanggan */
.customer-figma {
    max-width: 100%;
}

.customer-figma .cf-eyebrow {
    font-size: 13px;
}

.customer-figma .cf-heading {
    font-size: 26px;
}

.customer-figma .cf-subtitle {
    font-size: 15px;
}

.customer-figma .cf-add {
    font-size: 14px;
    padding: 12px 18px;
}

.customer-figma .cf-stats {
    gap: 16px;
}

.customer-figma .cf-stat {
    min-height: 115px;
    padding: 17px;
}

.customer-figma .cf-stat-top {
    font-size: 13px;
}

.customer-figma .cf-stat-icon {
    width: 34px;
    height: 34px;
}

.customer-figma .cf-stat-value {
    font-size: 26px;
}

.customer-figma .cf-stat-note {
    font-size: 12px;
}

.customer-figma .cf-panel-title {
    font-size: 15px;
}

.customer-figma .cf-count {
    font-size: 11px;
}

.customer-figma .cf-tools {
    grid-template-columns: minmax(180px, 1fr) 160px 170px auto;
    gap: 10px;
}

.customer-figma .cf-control {
    height: 40px;
    font-size: 13px;
}

.customer-figma th {
    padding: 13px 15px;
    font-size: 12px;
}

.customer-figma td {
    padding: 12px 15px;
    font-size: 12px;
}

.customer-figma .cf-foot {
    font-size: 12px;
}

.customer-figma .cf-page {
    min-width: 28px;
    height: 28px;
    font-size: 12px;
}

</style>

<div class="customer-figma">
    <section class="cf-intro">
        <div>
            <p class="cf-eyebrow">Manajemen Data</p>
            <h1 class="cf-heading">Pelanggan</h1>
            <p class="cf-subtitle">Kelola data pelanggan dan riwayat pesanan pelanggan Fast Wash</p>
        </div>
        <button class="cf-add" type="button" onclick="cfOpenModal()">＋ Tambah Pelanggan</button>
    </section>

    <section class="cf-stats" aria-label="Ringkasan pelanggan">
        <article class="cf-stat"><div class="cf-stat-top"><span>Total Pelanggan</span><span class="cf-stat-icon">♧</span></div><div class="cf-stat-value">142</div><div class="cf-stat-note">Terdaftar di sistem</div></article>
        <article class="cf-stat"><div class="cf-stat-top"><span>Pelanggan Aktif</span><span class="cf-stat-icon">◉</span></div><div class="cf-stat-value">98</div><div class="cf-stat-note">Pesanan dalam 30 hari</div></article>
        <article class="cf-stat"><div class="cf-stat-top"><span>Pelanggan Baru</span><span class="cf-stat-icon">＋</span></div><div class="cf-stat-value">14</div><div class="cf-stat-note">Pada Bulan September</div></article>
        <article class="cf-stat"><div class="cf-stat-top"><span>Rata-Rata Order</span><span class="cf-stat-icon">♧</span></div><div class="cf-stat-value">3.8x</div><div class="cf-stat-note">Setiap Akun</div></article>
    </section>

    <section class="cf-panel">
        <div class="cf-panel-head">
            <div class="cf-panel-title"><span class="cf-panel-symbol">▣</span><span>Daftar jumlah pesanan dan rekap transaksi pelanggan</span></div>
            <span class="cf-count">142 profil aktif</span>
        </div>
        <div class="cf-tools">
            <label class="cf-control"><span>⌕</span><input id="cfSearch" type="search" placeholder="Cari Nama Pelanggan / Kode Pesanan"><span></span></label>
            

<label class="cf-control">
    <select id="cfOrder" style="appearance:none; -webkit-appearance:none; -moz-appearance:none; background-image:none;">
        <option value="">Total Pesanan</option>
        <option value="desc">Terbanyak</option>
        <option value="asc">Paling sedikit</option>
    </select>
    <span></span>
</label>

<label class="cf-control">
    <select id="cfSpend" style="appearance:none; -webkit-appearance:none; -moz-appearance:none; background-image:none;">
        <option value="">Total Pengeluaran</option>
        <option value="desc">Terbesar</option>
        <option value="asc">Terkecil</option>
    </select>
    <span></span>
</label>
            <button class="cf-control cf-export" type="button" onclick="cfExport()">⇩ Export</button>
        </div>
        <div class="cf-table-wrap">
            <table>
                <thead><tr><th>Nama Pelanggan</th><th>Nomor WhatsApp</th><th class="cf-number">Total Pesanan</th><th class="cf-money">Total Pengeluaran</th></tr></thead>
                <tbody id="cfRows"></tbody>
            </table>
        </div>
        <div class="cf-foot">
            <span id="cfInfo">Menampilkan 6 dari 142 pelanggan</span>
            <div class="cf-pages">
                <button class="cf-page" id="cfPrev" type="button" onclick="cfChangePage(-1)">Sebelumnya</button>
                <button class="cf-page active" id="cfPage1" type="button">1</button>
                <button class="cf-page" type="button" disabled>2</button>
                <button class="cf-page" type="button" disabled>3</button>
                <button class="cf-page" id="cfNext" type="button" onclick="cfChangePage(1)">Berikutnya</button>
            </div>
        </div>
    </section>
</div>

<div class="cf-modal" id="cfModal" role="dialog" aria-modal="true" aria-labelledby="cfModalTitle">
    <div class="cf-modal-card">
        <h3 id="cfModalTitle">Tambah Pelanggan</h3>
        <form id="cfForm">
            <label>Nama Pelanggan<input id="cfName" required placeholder="Masukkan nama pelanggan"></label>
            <label>Nomor WhatsApp<input id="cfPhone" required placeholder="+62 ..."></label>
            <div class="cf-modal-actions"><button type="button" onclick="cfCloseModal()">Batal</button><button type="submit">Simpan</button></div>
        </form>
    </div>
</div>

<div class="cf-success-overlay" id="cfSuccess" role="status" aria-live="polite">
    <div class="cf-success-card">
        <span class="cf-success-check" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.2 4.2L19 6.8"/></svg>
        </span>
        <p class="cf-success-title">Pelanggan berhasil ditambahkan</p>
        <p class="cf-success-subtitle">data pelanggan tersimpan pada daftar pelanggan</p>
    </div>
</div>

<script>
const cfCustomers = <?= json_encode($pelanggan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let cfPage = 1;
const cfPerPage = 6;
const cfMoney = n => 'Rp ' + Number(n).toLocaleString('id-ID');
const cfEsc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function cfFiltered() {
    const q = document.getElementById('cfSearch').value.toLowerCase().trim();
    let rows = cfCustomers.filter(c => (c.nama + ' ' + c.wa).toLowerCase().includes(q));
    const order = document.getElementById('cfOrder').value;
    const spend = document.getElementById('cfSpend').value;
    if (order) rows.sort((a,b) => order === 'desc' ? b.pesanan-a.pesanan : a.pesanan-b.pesanan);
    if (spend) rows.sort((a,b) => spend === 'desc' ? b.pengeluaran-a.pengeluaran : a.pengeluaran-b.pengeluaran);
    return rows;
}
function cfRender() {
    const rows = cfFiltered();
    const pages = Math.max(1, Math.ceil(rows.length / cfPerPage));
    cfPage = Math.min(cfPage, pages);
    const visible = rows.slice((cfPage-1)*cfPerPage, cfPage*cfPerPage);
    document.getElementById('cfRows').innerHTML = visible.length ? visible.map(c => {
        const initials = c.nama.split(/\s+/).slice(0,2).map(x=>x[0]).join('').toUpperCase();
        return `<tr><td><div class="cf-person"><span class="cf-avatar cf-${c.warna}">${cfEsc(initials)}</span><span>${cfEsc(c.nama)}</span></div></td><td>${cfEsc(c.wa)}</td><td class="cf-number">${c.pesanan} Pesanan</td><td class="cf-money">${cfMoney(c.pengeluaran)}</td></tr>`;
    }).join('') : '<tr><td colspan="4" style="text-align:center;padding:24px;color:#91a2bc">Data pelanggan tidak ditemukan</td></tr>';
    document.getElementById('cfInfo').textContent = `Menampilkan ${visible.length} dari ${rows.length === 6 ? 142 : rows.length} pelanggan`;
    document.getElementById('cfPage1').textContent = cfPage;
    document.getElementById('cfPrev').disabled = cfPage === 1;
    document.getElementById('cfNext').disabled = cfPage >= pages;
}
function cfChangePage(n) { cfPage += n; cfRender(); }
function cfOpenModal() { document.getElementById('cfModal').classList.add('open'); document.getElementById('cfName').focus(); }
function cfCloseModal() { document.getElementById('cfModal').classList.remove('open'); }
document.getElementById('cfSearch').addEventListener('input', () => { cfPage=1; cfRender(); });
document.getElementById('cfOrder').addEventListener('change', () => { cfPage=1; cfRender(); });
document.getElementById('cfSpend').addEventListener('change', () => { cfPage=1; cfRender(); });
let cfSuccessTimer;
function cfShowSuccess() {
    const success = document.getElementById('cfSuccess');
    success.classList.add('show');
    clearTimeout(cfSuccessTimer);
    cfSuccessTimer = setTimeout(() => success.classList.remove('show'), 2300);
}
document.getElementById('cfForm').addEventListener('submit', e => {
    e.preventDefault();
    const nama = document.getElementById('cfName').value.trim();
    const wa = document.getElementById('cfPhone').value.trim();
    if (!nama || !wa) return;
    cfCustomers.unshift({nama:nama,wa:wa,pesanan:0,pengeluaran:0,warna:'blue'});
    document.getElementById('cfForm').reset();
    cfCloseModal();
    cfPage=1;
    cfRender();
    cfShowSuccess();
});
document.getElementById('cfModal').addEventListener('click', e => { if(e.target.id==='cfModal') cfCloseModal(); });
function cfExport() {
    const lines = [['Nama Pelanggan','Nomor WhatsApp','Total Pesanan','Total Pengeluaran'], ...cfFiltered().map(c=>[c.nama,c.wa,c.pesanan,c.pengeluaran])];
    const csv = lines.map(row=>row.map(v=>'"'+String(v).replace(/"/g,'""')+'"').join(',')).join('\r\n');
    const blob = new Blob(['\ufeff'+csv], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='data-pelanggan.csv'; a.click(); URL.revokeObjectURL(a.href);
}
cfRender();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
