<?php
$title = 'Personal Finance SaaS - Kelola Keuangan Anda dengan Mudah';
$content = '
<!-- Hero Section -->
<div class="row min-vh-75 align-items-center">
    <div class="col-lg-6 mx-auto text-center">
        <div class="mb-4">
            <i class="bi bi-wallet2 display-1 text-primary"></i>
        </div>
        <h1 class="display-3 fw-bold mb-4">Kelola Keuangan Pribadi Anda</h1>
        <p class="lead text-muted mb-5">Aplikasi pencatatan keuangan modern dengan fitur lengkap: multi-rekening, anggaran, target tabungan, dan laporan analitik.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="/register" class="btn btn-primary btn-lg px-4">
                <i class="bi bi-person-plus me-2"></i>Mulai Gratis
            </a>
            <a href="/login" class="btn btn-outline-primary btn-lg px-4">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
            </a>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="row py-5 mt-5 bg-light" id="features">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold">Fitur Unggulan</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-credit-card-2-fill fs-3"></i>
                        </div>
                        <h5 class="card-title fw-bold">Multi Rekening</h5>
                        <p class="text-muted">Kelola Kas, Bank, E-Wallet, dan Investasi dalam satu tempat. Saldo terupdate otomatis.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-graph-up-arrow fs-3"></i>
                        </div>
                        <h5 class="card-title fw-bold">Anggaran & Budgeting</h5>
                        <p class="text-muted">Atur batas pengeluaran per kategori dengan notifikasi saat mendekati batas (80% & 100%).</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-bullseye fs-3"></i>
                        </div>
                        <h5 class="card-title fw-bold">Target Keuangan</h5>
                        <p class="text-muted">Buat target tabungan (Dana Darurat, DP Rumah, Liburan) dengan tracking progress real-time.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-bar-chart-fill fs-3"></i>
                        </div>
                        <h5 class="card-title fw-bold">Laporan Analitik</h5>
                        <p class="text-muted">Visualisasi Cash Flow, Net Worth, dan ringkasan bulanan dengan grafik interaktif.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-purple bg-opacity-10 text-purple rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-arrow-left-right fs-3"></i>
                        </div>
                        <h5 class="card-title fw-bold">Transfer Antar Rekening</h5>
                        <p class="text-muted">Pindahkan dana antar rekening dengan mudah tanpa dicatat sebagai pengeluaran/pemasukan.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-download fs-3"></i>
                        </div>
                        <h5 class="card-title fw-bold">Ekspor Data</h5>
                        <p class="text-muted">Unduh laporan keuangan dalam format CSV/Excel untuk analisis lebih lanjut (Pro).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pricing Section -->
<div class="row py-5" id="pricing">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold">Pilih Paket yang Tepat</h2>
        <div class="row g-4 justify-content-center">
            <div class="col-md-5">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 text-center py-3">
                        <h4 class="fw-bold">Gratis</h4>
                        <div class="display-4 fw-bold text-primary mt-2">Rp 0<span class="text-muted fw-normal fs-5">/bulan</span></div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Maksimal 3 rekening</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Kategori standar</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Pencatatan transaksi dasar</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Dashboard ringkasan</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle-fill me-2"></i>Transaksi berulang</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle-fill me-2"></i>Ekspor CSV/Excel</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle-fill me-2"></i>Analitik lanjutan</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <button class="btn btn-outline-primary w-100" disabled>Paket Saat Ini</button>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card h-100 border-primary shadow-lg position-relative">
                    <div class="position-absolute top-0 start-50 translate-middle badge bg-primary px-3 py-1">Populer</div>
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="fw-bold mb-0">Pro</h4>
                        <div class="display-4 fw-bold mt-2">Rp 79.000<span class="fw-normal fs-5 opacity-75">/bulan</span></div>
                        <small class="opacity-75">Atau Rp 790.000/tahun (hemat 17%)</small>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Rekening tak terbatas</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Kategori kustom tak terbatas</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Transaksi berulang (Recurring)</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Ekspor CSV/Excel/PDF</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Analitik lanjutan & Net Worth</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Prioritas support</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <a href="/register" class="btn btn-primary w-100">Upgrade ke Pro</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- About Section -->
<div class="row py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">Tentang FinanceApp</h2>
                <p class="lead text-muted mb-4">FinanceApp adalah aplikasi keuangan pribadi modern yang dirancang untuk membantu Anda mengelola keuangan dengan mudah dan efisien.</p>
                <p class="text-muted mb-4">Dibangun dengan teknologi modern dan antarmuka yang intuitif, FinanceApp memungkinkan Anda mencatat transaksi, mengelola anggaran, mengejar target tabungan, dan melihat laporan keuangan lengkap dalam satu aplikasi.</p>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="d-flex align-items-center p-3 bg-white rounded shadow-sm">
                            <i class="bi bi-shield-check text-success fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Aman & Privat</h6>
                                <small class="text-muted">Data Anda dienkripsi dan tidak dibagikan</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center p-3 bg-white rounded shadow-sm">
                            <i class="bi bi-phone text-primary fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Mobile Friendly</h6>
                                <small class="text-muted">Akses kapan saja dari perangkat apa saja</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center p-3 bg-white rounded shadow-sm">
                            <i class="bi bi-graph-up text-success fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Laporan Lengkap</h6>
                                <small class="text-muted">Visualisasi keuangan yang mudah dipahami</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center p-3 bg-white rounded shadow-sm">
                            <i class="bi bi-lightning text-warning fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Cepat & Ringan</h6>
                                <small class="text-muted">Performa optimal di semua perangkat</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-5 text-center">
                        <i class="bi bi-phone-landscape display-1 text-primary"></i>
                        <h5 class="fw-bold mt-3">Siap Mulai?</h5>
                        <p class="text-muted">Bergabung dengan ribuan pengguna yang sudah mengelola keuangan dengan lebih baik.</p>
                        <a href="/register" class="btn btn-primary btn-lg px-5 mt-3">Mulai Sekarang Gratis</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA -->
<div class="row py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Siap Mengendalikan Keuangan Anda?</h2>
        <p class="lead mb-4 opacity-75">Bergabung dengan ribuan pengguna yang sudah mengelola keuangan dengan lebih baik.</        <a href="/register" class="btn btn-light btn-lg px-5 fw-bold">Mulai Sekarang Gratis</a>
    </div>
</div>
';
$extraScripts = '';
require __DIR__ . '/../layouts/main.php';