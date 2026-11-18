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

// Ambil daftar layanan aktif yang unik
$sqlLayanan = "SELECT * FROM (
    SELECT DISTINCT ON (nama_layanan) id_layanan, nama_layanan, harga, satuan, durasi_jam 
    FROM layanan 
    WHERE is_active = true 
    ORDER BY nama_layanan, id_layanan
) t ORDER BY t.id_layanan";
$layanan = $pdo->query($sqlLayanan)->fetchAll();

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Alert Error Flash jika ada -->
<?php if ($flashError): ?>
    <div class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-2xl flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span><?php echo e($flashError); ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
    </div>
<?php endif; ?>

<!-- Formulir Pesanan Baru Sesuai UI/UX Designer Figma -->
<form id="formPesanan" action="<?php echo $base; ?>pesanan/proses_tambah" method="post" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-12">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">

    <!-- Kolom Kiri: Formulir Data Pelanggan & Layanan (Lebar 2/3) -->
    <div class="lg:col-span-2 space-y-6">

        <!-- 1. Card Pelanggan -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-xs border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-slate-800">Pelanggan</h2>
                <button type="button" onclick="togglePelangganBaru()" id="btnModePelanggan" class="inline-flex items-center gap-1 text-sm font-semibold text-[#0085ff] hover:underline">
                    <svg id="iconTambahPelanggan" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/>
                    </svg>
                    <svg id="iconPilihPelanggan" class="hidden w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/>
                    </svg>
                    <span id="labelModePelanggan">Pelanggan Baru</span>
                </button>
            </div>

            <!-- Mode Pilih Pelanggan Terdaftar -->
            <div id="boxPilihPelanggan" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-normal text-slate-700 mb-2">Nama Lengkap</label>
                    <div class="relative">
                        <select name="id_pelanggan" id="selectPelanggan" onchange="pilihPelangganChange()" class="appearance-none w-full px-4 py-3 text-sm bg-white border border-slate-200 rounded-2xl shadow-xs focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] text-slate-700 cursor-pointer">
                            <option value="">Pilih Nama Pelanggan</option>
                            <?php foreach ($pelanggan as $p): ?>
                                <option value="<?php echo (int) $p['id_pelanggan']; ?>" data-nama="<?php echo e($p['nama']); ?>" data-telepon="<?php echo e($p['no_telepon']); ?>">
                                    <?php echo e($p['nama']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-normal text-slate-700 mb-2">No. WhatsApp</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <input type="text" id="inputWhatsappPreview" readonly placeholder="0812 3456 7890" class="w-full pl-10 pr-4 py-3 text-sm bg-slate-50 border border-slate-200 rounded-2xl text-slate-600 focus:outline-hidden cursor-default">
                    </div>
                </div>
            </div>

            <!-- Mode Daftarkan Pelanggan Baru Langsung (Opsional) -->
            <div id="boxPelangganBaru" class="hidden grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                <div>
                    <label class="block text-xs font-normal text-slate-700 mb-2">Nama Lengkap Baru *</label>
                    <input type="text" name="nama_baru" id="nama_baru" placeholder="Masukkan nama pelanggan baru" class="w-full px-4 py-3 text-sm bg-white border border-slate-200 rounded-2xl focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-normal text-slate-700 mb-2">No. WhatsApp Baru *</label>
                    <input type="text" name="telepon_baru" id="telepon_baru" placeholder="08xxxxxxxxxx" class="w-full px-4 py-3 text-sm bg-white border border-slate-200 rounded-2xl focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] text-slate-800">
                </div>
            </div>
        </div>

        <!-- 2. Card Layanan Reguler -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-xs border border-slate-100">
            <h2 class="text-base font-bold text-slate-800 mb-1">Layanan Reguler</h2>
            <p class="text-xs text-slate-500 mb-4">Pilih Layanan:</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-6">
                <?php foreach ($layanan as $idx => $l): ?>
                    <?php
                    $isFirst = ($idx === 0);
                    $namaLower = strtolower($l['nama_layanan']);

                    if (str_contains($namaLower, 'setrika') && !str_contains($namaLower, 'cuci')) {
                        $iconSvg = '<svg class="w-5 h-5 text-[#0085ff]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>';
                    } elseif (str_contains($namaLower, 'kering')) {
                        $iconSvg = '<svg class="w-5 h-5 text-[#0085ff]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="13" r="4"/><line x1="8" y1="6" x2="8.01" y2="6"/><line x1="12" y1="6" x2="12.01" y2="6"/></svg>';
                    } else {
                        $iconSvg = '<svg class="w-5 h-5 text-[#0085ff]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 3l4 3-2 5-3-1v10H9V10L6 11 4 6l4-3c1 1.5 3 2 4 2s3-.5 4-2z"/></svg>';
                    }

                    $durasiHari = ceil((int) $l['durasi_jam'] / 24);
                    $formatEstimasi = "{$durasiHari} hari";
                    ?>
                    <label class="relative flex flex-col p-4 border rounded-2xl cursor-pointer transition-all hover:border-[#0085ff] has-checked:border-[#0085ff] has-checked:bg-blue-50/30 border-slate-200">
                        <input type="radio" name="id_layanan" value="<?php echo (int) $l['id_layanan']; ?>" data-nama="<?php echo e($l['nama_layanan']); ?>" data-harga="<?php echo (float) $l['harga']; ?>" data-durasi="<?php echo e($formatEstimasi); ?>" <?php echo $isFirst ? 'checked' : ''; ?> onchange="hitungRingkasan()" class="hidden">
                        <div class="mb-3">
                            <?php echo $iconSvg; ?>
                        </div>
                        <span class="font-bold text-sm text-slate-800"><?php echo e($l['nama_layanan']); ?></span>
                        <span class="text-xs text-slate-400 mt-0.5">
                            Rp<?php echo number_format((float) $l['harga'], 0, ',', '.'); ?>/<?php echo e($l['satuan']); ?> · <?php echo e($formatEstimasi); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Baris Berat (kg) & Pembayaran -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-center mb-6 pt-2">
                <div>
                    <label class="block text-xs font-normal text-slate-700 mb-2">Berat (kg)</label>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="ubahBerat(-1)" class="w-10 h-10 flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-base rounded-xl transition-colors">-</button>
                        <div class="relative flex-1 max-w-[140px]">
                            <input type="number" name="qty" id="inputBerat" value="3" step="0.5" min="0.5" oninput="hitungRingkasan()" class="w-full py-2 px-3 text-center font-bold text-sm text-slate-800 bg-white border border-slate-200 rounded-xl focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff]">
                        </div>
                        <button type="button" onclick="ubahBerat(1)" class="w-10 h-10 flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-base rounded-xl transition-colors">+</button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-normal text-slate-700 mb-2">Pembayaran</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center justify-center py-2 px-3 border border-slate-200 rounded-xl cursor-pointer text-xs font-medium text-slate-700 hover:border-[#0085ff] has-checked:border-[#0085ff] has-checked:bg-blue-50 has-checked:text-[#0085ff] transition-all">
                            <input type="radio" name="metode_pembayaran" value="tunai" checked onchange="hitungRingkasan()" class="hidden">
                            <span>Tunai</span>
                        </label>
                        <label class="flex items-center justify-center py-2 px-3 border border-slate-200 rounded-xl cursor-pointer text-xs font-medium text-slate-700 hover:border-[#0085ff] has-checked:border-[#0085ff] has-checked:bg-blue-50 has-checked:text-[#0085ff] transition-all">
                            <input type="radio" name="metode_pembayaran" value="qris" onchange="hitungRingkasan()" class="hidden">
                            <span>QRIS</span>
                        </label>
                        <label class="flex items-center justify-center py-2 px-3 border border-slate-200 rounded-xl cursor-pointer text-xs font-medium text-slate-700 hover:border-[#0085ff] has-checked:border-[#0085ff] has-checked:bg-blue-50 has-checked:text-[#0085ff] transition-all">
                            <input type="radio" name="metode_pembayaran" value="transfer" onchange="hitungRingkasan()" class="hidden">
                            <span>Transfer</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Layanan Cepat -->
            <div class="p-4 border border-slate-200 rounded-2xl bg-white mb-6 flex items-center justify-between">
                <div>
                    <div class="font-bold text-sm text-slate-800">Layanan Cepat</div>
                    <div class="text-xs text-slate-400 mt-0.5">Siap dalam 6 jam | +Rp 10,000</div>
                </div>
                <div>
                    <input type="checkbox" name="layanan_cepat" id="checkLayananCepat" value="1" onchange="hitungRingkasan()" class="w-5 h-5 rounded-lg border-slate-300 text-[#0085ff] focus:ring-[#0085ff] cursor-pointer">
                </div>
            </div>

            <!-- Catatan -->
            <div>
                <label class="block text-xs font-normal text-slate-700 mb-2">Catatan</label>
                <textarea name="catatan" id="inputCatatan" rows="3" placeholder="noda, bahan halus, pewangi" class="w-full px-4 py-3 text-sm bg-white border border-slate-200 rounded-2xl focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] placeholder:text-slate-400 text-slate-700"></textarea>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Ringkasan Pesanan (Lebar 1/3) -->
    <div class="lg:col-span-1 space-y-4">
        <a href="<?php echo $base; ?>pesanan/tambah" class="w-full py-3 px-4 border-2 border-[#0085ff] text-[#0085ff] font-semibold text-sm rounded-2xl bg-white hover:bg-blue-50 transition-colors flex items-center justify-center gap-1.5 shadow-xs">
            <span>+ Tambah Pesanan</span>
        </a>

        <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-100 space-y-4">
            <h2 class="text-base font-bold text-slate-800">Ringkasan Pesanan</h2>
            <div class="space-y-3 text-xs text-slate-600">
                <div class="flex justify-between">
                    <span class="text-slate-500">Pelanggan</span>
                    <span class="font-semibold text-slate-800" id="ringkasanPelanggan">Pilih Pelanggan</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Layanan</span>
                    <span class="font-semibold text-slate-800" id="ringkasanLayananNama">Cuci + Setrika</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Berat</span>
                    <span class="font-semibold text-slate-800" id="ringkasanBeratPerhitungan">3 kg × Rp 15,000</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Layanan Cepat</span>
                    <span class="font-semibold text-slate-800" id="ringkasanLayananCepat">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Pembayaran</span>
                    <span class="font-semibold text-slate-800" id="ringkasanMetodeBayar">Tunai</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Pesanan Siap</span>
                    <span class="font-semibold text-slate-800" id="ringkasanPesananSiap">dalam 2 hari</span>
                </div>
            </div>
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <span class="font-bold text-sm text-slate-800">Total</span>
                <span class="text-xl font-extrabold text-[#0085ff]" id="ringkasanTotalHarga">Rp 45,000</span>
            </div>
            <div class="pt-2">
                <button type="submit" class="w-full py-3.5 px-4 bg-[#0085ff] hover:bg-[#0070d8] text-white font-bold text-sm rounded-2xl shadow-md shadow-[#0085ff]/25 transition-all text-center">
                    Buat Pesanan
                </button>
            </div>
        </div>
    </div>
</form>

<script>
let modeBaru = false;

function togglePelangganBaru() {
    modeBaru = !modeBaru;
    const boxPilih = document.getElementById('boxPilihPelanggan');
    const boxBaru  = document.getElementById('boxPelangganBaru');
    const iconTambah = document.getElementById('iconTambahPelanggan');
    const iconPilih  = document.getElementById('iconPilihPelanggan');
    const label      = document.getElementById('labelModePelanggan');

    if (modeBaru) {
        boxPilih.classList.add('hidden');
        boxBaru.classList.remove('hidden');
        boxBaru.classList.add('grid');
        iconTambah.classList.add('hidden');
        iconPilih.classList.remove('hidden');
        label.textContent = 'Pilih Terdaftar';
        document.getElementById('selectPelanggan').value = '';
        document.getElementById('inputWhatsappPreview').value = '';
    } else {
        boxPilih.classList.remove('hidden');
        boxBaru.classList.add('hidden');
        boxBaru.classList.remove('grid');
        iconTambah.classList.remove('hidden');
        iconPilih.classList.add('hidden');
        label.textContent = 'Pelanggan Baru';
        document.getElementById('nama_baru').value = '';
        document.getElementById('telepon_baru').value = '';
    }
    pilihPelangganChange();
}

function pilihPelangganChange() {
    const sel = document.getElementById('selectPelanggan');
    const ringkasanNama = document.getElementById('ringkasanPelanggan');

    if (modeBaru) {
        const namaBaru = document.getElementById('nama_baru').value.trim() || 'Pelanggan Baru';
        ringkasanNama.textContent = namaBaru;
        return;
    }

    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('inputWhatsappPreview').value = opt.getAttribute('data-telepon') || '';
        ringkasanNama.textContent = opt.getAttribute('data-nama') || 'Pelanggan';
    }
}

function ubahBerat(delta) {
    const input = document.getElementById('inputBerat');
    let val = parseFloat(input.value) || 0.5;
    val = Math.max(0.5, val + delta);
    input.value = val.toFixed(2);
    hitungRingkasan();
}

function hitungRingkasan() {
    const berat = parseFloat(document.getElementById('inputBerat').value) || 0.5;
    const layananRadio = document.querySelector('input[name="id_layanan"]:checked');
    const metodeRadio = document.querySelector('input[name="metode_pembayaran"]:checked');
    const cepat = document.getElementById('checkLayananCepat').checked;

    let namaLayanan = 'Cuci + Setrika';
    let harga = 15000;
    let durasi = 2;

    if (layananRadio) {
        namaLayanan = layananRadio.getAttribute('data-nama') || 'Cuci + Setrika';
        harga = parseFloat(layananRadio.getAttribute('data-harga')) || 15000;
        durasi = parseInt(layananRadio.getAttribute('data-durasi')) || 2;
    }

    const metodeLabel = metodeRadio ? metodeRadio.value : 'tunai';
    const metodeText = metodeLabel === 'tunai' ? 'Tunai' : (metodeLabel === 'qris' ? 'QRIS' : 'Transfer');

    const biayaTambahan = cepat ? 10000 : 0;
    const subtotal = (berat * harga).toFixed(0);
    const total = (parseFloat(subtotal) + biayaTambahan).toFixed(0);

    document.getElementById('ringkasanLayananNama').textContent = namaLayanan;
    document.getElementById('ringkasanBeratPerhitungan').textContent = `${berat} kg × Rp ${harga.toLocaleString('id-ID')}`;
    document.getElementById('ringkasanLayananCepat').textContent = cepat ? '+Rp 10,000' : '-';
    document.getElementById('ringkasanMetodeBayar').textContent = metodeText;
    document.getElementById('ringkasanPesananSiap').textContent = `dalam ${durasi} hari`;
    document.getElementById('ringkasanTotalHarga').textContent = `Rp ${parseInt(total).toLocaleString('id-ID')}`;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
