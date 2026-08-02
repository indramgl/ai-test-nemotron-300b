<?php
$title = 'Langganan - Personal Finance SaaS';
$content = '
<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-1">Kelola Langganan</h2>
        <p class="text-muted mb-0">Kelola paket langganan dan upgrade ke Pro</p>
    </div>
</div>

<!-- Current Plan -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Paket Saat Ini</h5>
                <span class="badge bg-primary" id="currentPlanBadge">Free</span>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="fw-bold" id="currentPlanName">Free</h5>
                        <p class="text-muted mb-1" id="currentPlanDesc">Maksimal 3 rekening, kategori standar, pencatatan transaksi dasar</p>
                        <small class="text-muted" id="currentPlanPeriod">Mulai: - | Berakhir: -</small>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-secondary fs-6 px-3 py-2" id="currentPlanStatus">Aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Available Plans -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <h5 class="fw-bold mb-3">Paket Tersedia</h5>
    </div>
    <div class="col-12 col-md-6" id="planFree">
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
    <div class="col-12 col-md-6" id="planPro">
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
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" id="upgradeMonthlyBtn" data-cycle="monthly">
                        <i class="bi bi-arrow-repeat me-1"></i>Upgrade Bulanan (Rp 79.000/bln)
                    </button>
                    <button class="btn btn-outline-primary" id="upgradeYearlyBtn" data-cycle="yearly">
                        <i class="bi bi-calendar-check me-1"></i>Upgrade Tahunan (Rp 790.000/thn - Hemat 17%)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Billing History -->
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Riwayat Tagihan</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="billingTable">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Paket</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Metode</th>
                            </tr>
                        </thead>
                        <tbody id="billingBody">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 mb-0 small">Memuat...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
';
$extraScripts = '
<script src="/assets/js/subscription.js"></script>
';
require __DIR__ . '/../layouts/main.php';