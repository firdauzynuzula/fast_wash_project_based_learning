<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>Dashboard Pelanggan | Fast Wash</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="font-jakarta bg-slate-100 text-slate-900 antialiased">
    <div class="mx-auto min-h-screen max-w-md bg-[#f4f9fe]">

        <!-- Header -->
        <header class="sticky top-0 z-20 bg-white/95 px-6 py-5 shadow-[0_4px_16px_rgba(0,133,255,0.06)] backdrop-blur">
            <a href="index.html" class="flex w-fit items-center gap-3 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0085ff]/40">
                <span class="flex size-10 items-center justify-center rounded-full bg-[#118dff] text-white shadow-md shadow-[#118dff]/30">
                    <!-- Ikon mesin cuci -->
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="4" y="2.5" width="16" height="19" rx="3"/>
                        <circle cx="12" cy="13.5" r="4.5"/>
                        <path d="M10.5 14.5a2 2 0 0 0 3-1.5"/>
                        <path d="M8 6h.01M11 6h.01"/>
                    </svg>
                </span>
                <span class="text-xl font-extrabold tracking-tight text-[#0085ff]">Fast Wash</span>
            </a>
        </header>

        <main class="px-5 pb-10 pt-6">
            <!-- Sapaan -->
            <section class="px-1">
                <p class="text-sm font-medium text-[#0085ff]">Selamat datang</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">
                    Nadya Alya <span aria-hidden="true">👋</span>
                </h1>
            </section>

            <!-- Ringkasan cucian aktif -->
            <section class="mt-6 rounded-3xl bg-[#0085ff] px-6 py-7 text-white shadow-xl shadow-[#0085ff]/25" aria-labelledby="cucian-aktif">
                <p id="cucian-aktif" class="text-sm font-medium text-white/85">Cucian aktif</p>
                <p class="mt-1 text-4xl font-extrabold tracking-tight">2 pesanan</p>
                <p class="mt-4 max-w-68 text-xs leading-relaxed text-white/85">
                    Kami kirim notifikasi WhatsApp saat cucian siap diambil.
                </p>
            </section>

            <!-- Progres pesanan -->
            <section class="mt-8" aria-labelledby="judul-progres">
                <h2 id="judul-progres" class="px-1 text-base font-bold text-slate-900">Progres Pesanan</h2>

                <ul class="mt-4 space-y-4">
                    <li class="rounded-2xl bg-white p-5 shadow-[0_6px_20px_rgba(15,23,42,0.05)]">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-bold tracking-tight">#FW24042</h3>
                            <span class="rounded-full bg-[#e8f3ff] px-3 py-1 text-xs font-semibold text-[#0085ff]">Dicuci</span>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Cuci + Setrika · 3 kg · Rp 45.000</p>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-xs font-semibold text-[#de8825]">Belum dibayar</span>
                            <a href="#" class="rounded text-xs font-semibold text-[#0085ff] hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0085ff]/40" aria-label="Detail pesanan #FW24042">Detail</a>
                        </div>
                    </li>

                    <li class="rounded-2xl bg-white p-5 shadow-[0_6px_20px_rgba(15,23,42,0.05)]">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-bold tracking-tight">#FW24041</h3>
                            <span class="rounded-full bg-[#e8faf0] px-3 py-1 text-xs font-semibold text-[#2ec291]">Siap Diambil</span>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Cuci Kering · 2 kg · Rp 24.000</p>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-xs font-semibold text-[#2ec291]">Lunas</span>
                            <a href="#" class="rounded text-xs font-semibold text-[#0085ff] hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0085ff]/40" aria-label="Detail pesanan #FW24041">Detail</a>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Daftar harga -->
            <section class="mt-8" aria-labelledby="judul-harga">
                <h2 id="judul-harga" class="px-1 text-base font-bold text-slate-900">Daftar Harga</h2>

                <ul class="mt-4 space-y-3.5">
                    <li class="flex items-center gap-4 rounded-2xl bg-white px-4 py-4 shadow-[0_6px_20px_rgba(15,23,42,0.05)]">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#e8f3ff] text-[#0085ff]">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8.5 6H19a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H8.5L3 12l5.5-6Z"/>
                                <circle cx="9" cy="12" r="1"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">Cuci + Setrika</p>
                            <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-400">
                                <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                2 hari
                            </p>
                        </div>
                        <p class="ml-auto text-sm font-bold whitespace-nowrap text-[#0085ff]">Rp 15.000/kg</p>
                    </li>

                    <li class="flex items-center gap-4 rounded-2xl bg-white px-4 py-4 shadow-[0_6px_20px_rgba(15,23,42,0.05)]">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#e8f3ff] text-[#0085ff]">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8.5 6H19a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H8.5L3 12l5.5-6Z"/>
                                <circle cx="9" cy="12" r="1"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">Cuci Kering</p>
                            <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-400">
                                <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                1 hari
                            </p>
                        </div>
                        <p class="ml-auto text-sm font-bold whitespace-nowrap text-[#0085ff]">Rp 12.000/kg</p>
                    </li>

                    <li class="flex items-center gap-4 rounded-2xl bg-white px-4 py-4 shadow-[0_6px_20px_rgba(15,23,42,0.05)]">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#e8f3ff] text-[#0085ff]">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8.5 6H19a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H8.5L3 12l5.5-6Z"/>
                                <circle cx="9" cy="12" r="1"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">Setrika</p>
                            <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-400">
                                <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                1 hari
                            </p>
                        </div>
                        <p class="ml-auto text-sm font-bold whitespace-nowrap text-[#0085ff]">Rp 8.000/kg</p>
                    </li>
                </ul>
            </section>
        </main>
    </div>
</body>
</html>
