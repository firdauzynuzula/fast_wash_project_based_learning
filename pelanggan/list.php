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
    border-radius: 50%;
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

.customer-figma table thead tr th:first-child {
    border-top-left-radius:15px;
}

.customer-figma table thead tr th:last-child {
    border-top-right-radius: 15px;
}

/* Icon panah dropdown ke bawah */
.customer-figma .cf-control:has(select) {
    position: relative;
}

.customer-figma .cf-control:has(select)::after {
    content: "";
    position: absolute;
    right: 16px;
    top: 50%;
    width: 7px;
    height: 7px;    
    border-right: 1.5px solid #64748b;
    border-bottom: 1.5px solid #64748b;
    transform: translateY(-70%) rotate(45deg);
    pointer-events: none;
}

.customer-figma .cf-control select {
    padding-right: 35px !important;
    appearance: none !important;
    -webkit-appearance: none !important;
}

.customer-figma table tbody td {
    padding-top: 18px !important;
    padding-bottom: 18px !important;
}

.customer-figma table tbody td {
    font-size: 13px !important;
}

/* Wrapper input dan ikon */
.cf-input-icon {
    position: relative !important;
    display: block !important;
    width: 100% !important;
}

/* Ukuran ikon jangan sampai membesar */
.cf-input-icon svg {
    position: absolute !important;
    left: 12px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;

    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    max-width: 18px !important;

    display: block !important;
    color: #64748b !important;
    pointer-events: none !important;
}

/* Input tetap memenuhi kotak */
.cf-input-icon input {
    display: block !important;
    width: 100% !important;
    box-sizing: border-box !important;
    padding-left: 40px !important;
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
        <article class="cf-stat"><div class="cf-stat-top"><span>Total Pelanggan</span><svg xmlns="http://www.w3.org/2000/svg" width="1.25em" height="1em" viewBox="0 0 640 512">
	<path d="M0 0h640v512H0z" fill="none" />
	<path fill="currentColor" d="M96 128a128 128 0 1 1 256 0a128 128 0 1 1-256 0M0 482.3C0 383.8 79.8 304 178.3 304h91.4c98.5 0 178.3 79.8 178.3 178.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3M609.3 512H471.5c5.4-9.4 8.6-20.3 8.6-32v-8c0-60.7-27.1-115.2-69.8-151.8c2.4-.1 4.7-.2 7.1-.2h61.4c89 0 161.2 72.2 161.2 161.3c0 17-13.8 30.7-30.7 30.7M432 256c-31 0-59-12.6-79.3-32.9c19.7-26.6 31.3-59.5 31.3-95.1c0-26.8-6.6-52.1-18.3-74.3C384.3 40.1 407.2 32 432 32c61.9 0 112 50.1 112 112s-50.1 112-112 112" />
</svg>
</div><div class="cf-stat-value">142</div><div class="cf-stat-note">Terdaftar di sistem</div></article>
        <article class="cf-stat"><div class="cf-stat-top"><span>Pelanggan Aktif</span><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
	<path d="M0 0h24v24H0z" fill="none" />
	<path fill="currentColor" fill-rule="evenodd" d="M7.625 19.577q-3.746-2.163-4.357-5.624l-.707.795q-.1.112-.234.177t-.283.074t-.29-.04t-.252-.148q-.055-.05-.1-.108q-.045-.059-.077-.126q-.032-.066-.051-.138q-.018-.071-.023-.145q-.004-.074.006-.147t.034-.143t.062-.133q.037-.064.086-.119l1.741-1.959q.081-.986.369-2.058Q5.286 3.25 12 3.25q.074 0 .146.014q.073.015.141.043t.13.069t.113.094q.053.052.094.113t.069.13t.043.141t.014.146q0 .149-.057.287t-.163.243t-.243.163q-.138.057-.287.057q-5.563 0-7.003 5.374q-.214.8-.29 1.543l1.854 2.085q.049.055.086.119q.037.063.062.133q.024.07.034.143t.006.147t-.023.145t-.051.138t-.077.126t-.1.108q-.111.099-.252.148t-.29.04t-.283-.074t-.234-.177l-.566-.637q.669 2.532 3.502 4.168q.129.074.22.192t.129.263q.039.144.02.292q-.02.148-.095.277q-.036.064-.085.12t-.107.1t-.125.078q-.067.032-.138.051q-.071.02-.145.024q-.074.005-.147-.004t-.143-.034t-.134-.061m13.195-7.37q-.081.986-.369 2.058Q18.714 20.75 12 20.75q-.074 0-.146-.014q-.073-.015-.141-.043t-.13-.069t-.113-.094q-.053-.052-.094-.113t-.069-.13t-.043-.141T11.25 20q0-.149.057-.287t.163-.243t.243-.163q.138-.057.287-.057q5.563 0 7.003-5.374q.214-.8.29-1.543l-1.854-2.085q-.049-.055-.086-.119q-.037-.063-.062-.133q-.024-.07-.034-.143t-.006-.147q.005-.074.023-.145q.019-.072.051-.138t.077-.126q.045-.058.1-.108q.111-.099.252-.148t.29-.04t.283.074t.234.177l.566.637q-.669-2.532-3.502-4.168q-.129-.074-.22-.192t-.129-.263q-.039-.144-.02-.292q.02-.148.095-.277q.074-.129.192-.22t.263-.129t.292-.02q.148.02.277.095q3.746 2.163 4.357 5.624l.707-.795q.1-.112.234-.177t.283-.074t.29.04t.252.148q.055.05.1.108q.045.059.077.126q.032.066.051.138q.018.071.023.145q.004.074-.006.147t-.034.143q-.025.07-.062.133q-.037.064-.086.119zm-9.604 1.198L9.53 11.72q-.052-.053-.113-.094t-.13-.069t-.141-.043T9 11.5t-.146.014q-.073.015-.141.043t-.13.069t-.113.094q-.106.105-.163.243t-.057.287t.057.287t.163.243l2.25 2.25q.105.106.243.163t.287.057t.287-.057t.243-.163q.017-.016.032-.034l3.75-4.25q.049-.055.086-.119q.038-.064.061-.134q.024-.07.034-.143t.006-.147t-.024-.145t-.051-.138q-.033-.066-.078-.125q-.044-.059-.1-.107q-.055-.049-.119-.086q-.064-.038-.134-.061q-.07-.024-.143-.034t-.147-.006t-.145.024t-.138.051q-.066.033-.125.078q-.059.044-.107.1z" />
</svg>
</div><div class="cf-stat-value">98</div><div class="cf-stat-note">Pesanan dalam 30 hari</div></article>
        <article class="cf-stat"><div class="cf-stat-top"><span>Pelanggan Baru</span><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
	<path d="M0 0h24v24H0z" fill="none" />
	<path fill="currentColor" d="M13 8c0-2.21-1.79-4-4-4S5 5.79 5 8s1.79 4 4 4s4-1.79 4-4m-2 0c0 1.1-.9 2-2 2s-2-.9-2-2s.9-2 2-2s2 .9 2 2M1 18v2h16v-2c0-2.66-5.33-4-8-4s-8 1.34-8 4m2 0c.2-.71 3.3-2 6-2c2.69 0 5.78 1.28 6 2zm17-3v-3h3v-2h-3V7h-2v3h-3v2h3v3z" />
</svg>
</div><div class="cf-stat-value">14</div><div class="cf-stat-note">Pada Bulan September</div></article>
        <article class="cf-stat"><div class="cf-stat-top"><span>Rata-Rata Order</span><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
	<path d="M0 0h24v24H0z" fill="none" />
	<g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
		<path d="M3.977 9.84A2 2 0 0 1 5.971 8h12.058a2 2 0 0 1 1.994 1.84l.803 10A2 2 0 0 1 18.833 22H5.167a2 2 0 0 1-1.993-2.16z" />
		<path d="M16 11V6a4 4 0 0 0-4-4v0a4 4 0 0 0-4 4v5" />
	</g>
</svg>
</div><div class="cf-stat-value">3.8x</div><div class="cf-stat-note">Setiap Akun</div></article>
    </section>

    <section class="cf-panel">
        <div class="cf-panel-head">
            <div class="cf-panel-title"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" id="Contacts--Streamline-Outlined-Material" height="24" width="24">
  <desc>
    Contacts Streamline Icon: https://streamlinehq.com
  </desc>
  <path fill="#000000" d="M3.59999 23v-1.5H20.4v1.5H3.59999Zm0 -20.5V1H20.4v1.5H3.59999ZM12 12.9c0.8333 0 1.5333 -0.28335 2.1 -0.85 0.56665 -0.56665 0.85 -1.26665 0.85 -2.1s-0.28335 -1.53335 -0.85 -2.1C13.5333 7.28335 12.8333 7 12 7c-0.83335 0 -1.53335 0.28335 -2.1 0.85 -0.5667 0.56665 -0.85 1.26665 -0.85 2.1s0.2833 1.53335 0.85 2.1c0.56665 0.56665 1.26665 0.85 2.1 0.85ZM3.29999 20c-0.4 0 -0.75 -0.15 -1.05 -0.45 -0.3 -0.3 -0.45 -0.65 -0.45 -1.05V5.5c0 -0.43335 0.15 -0.791665 0.45 -1.075 0.3 -0.283335 0.65 -0.425 1.05 -0.425H20.7c0.4 0 0.75 0.15 1.05 0.45 0.3 0.3 0.45 0.65 0.45 1.05v13c0 0.4 -0.15 0.75 -0.45 1.05 -0.3 0.3 -0.65 0.45 -1.05 0.45H3.29999ZM5.5 18.5c0.85 -1.05 1.8583 -1.8375 3.025 -2.3625 1.16665 -0.525 2.3208 -0.7875 3.4625 -0.7875 1.14165 0 2.3083 0.2625 3.5 0.7875C16.67915 16.6625 17.6833 17.45 18.5 18.5h2.2V5.5H3.29999v13H5.5Zm2.35 0h8.35c-0.5167 -0.5 -1.12085 -0.9 -1.8125 -1.2 -0.6917 -0.3 -1.4875 -0.45 -2.3875 -0.45 -0.9 0 -1.6875 0.15 -2.3625 0.45 -0.675 0.3 -1.27085 0.7 -1.7875 1.2Zm4.154 -7.1c-0.4027 0 -0.7415 -0.14165 -1.0165 -0.425 -0.275 -0.28335 -0.4125 -0.625 -0.4125 -1.025 0 -0.4 0.13615 -0.74165 0.4085 -1.025 0.2723 -0.28335 0.6098 -0.425 1.0125 -0.425 0.40265 0 0.7415 0.14165 1.0165 0.425 0.275 0.28335 0.4125 0.625 0.4125 1.025 0 0.4 -0.1362 0.74165 -0.4085 1.025 -0.27235 0.28335 -0.60985 0.425 -1.0125 0.425Z" stroke-width="0.5"></path>
</svg><span>Daftar jumlah pesanan dan rekap transaksi pelanggan</span></div>
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
            <button class="cf-control cf-export" type="button" onclick="cfExport()"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
	<path d="M0 0h24v24H0z" fill="none" />
	<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.877a2 2 0 0 0 1.94-1.515L22 17" />
</svg>
 Export</button>
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
          <!-- Nama Pelanggan -->
<label>
    <span class="cf-label-text">Nama Pelanggan</span>

    <div class="cf-input-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M0 0h24v24H0z" fill="none" />
            <g fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="7" r="5" />
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 14h.352a3 3 0 0 1 2.976 2.628l.391 3.124A2 2 0 0 1 18.734 22H5.266a2 2 0 0 1-1.985-2.248l.39-3.124A3 3 0 0 1 6.649 14H7" />
            </g>
        </svg>

        <input id="cfName" required placeholder="Masukkan nama pelanggan">
    </div>
</label>


<!-- Nomor WhatsApp -->
<label>
    <span class="cf-label-text">Nomor WhatsApp</span>

    <div class="cf-input-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M0 0h24v24H0z" fill="none" />
            <path fill="currentColor"
                d="M6.62 10.79c1.44 2.83 3.76 5.15 6.59 6.59l2.2-2.2c.28-.28.67-.36 1.02-.25c1.12.37 2.32.57 3.57.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57c.11.35.03.74-.25 1.02z" />
        </svg>

        <input id="cfPhone" required placeholder="+62 ...">
    </div>
</label>
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
