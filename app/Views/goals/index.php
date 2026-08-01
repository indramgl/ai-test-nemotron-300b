<?php
$title = 'Target Keuangan - Personal Finance SaaS';
$content = '
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-1">Target Keuangan</h2>
                <p class="text-muted mb-0">Buat dan pantau target tabungan Anda</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#goalModal">
                <i class="bi bi-plus-circle me-1"></i>Tambah Target
            </button>
        </div>
    </div>
</div>

<!-- Goals Grid -->
<div class="row g-3" id="goalsGrid">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Memuat target...</p>
    </div>
</div>

<!-- Goal Modal -->
<div class="modal fade" id="goalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="goalModalTitle">Tambah Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="goalForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id" id="goalId">
                    
                    <div class="mb-3">
                        <label for="goalName" class="form-label fw-semibold">Nama Target <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="goalName" name="name" required placeholder="Contoh: Dana Darurat, DP Rumah, Liburan Bali">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="goalTargetAmount" class="form-label fw-semibold">Nominal Target <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="goalTargetAmount" name="target_amount" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="goalTargetDate" class="form-label fw-semibold">Target Tanggal</label>
                            <input type="date" class="form-control" id="goalTargetDate" name="target_date">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="goalIcon" class="form-label fw-semibold">Ikon</label>
                            <select class="form-select" id="goalIcon" name="icon">
                                <option value="bi-bullseye">🎯 Target</option>
                                <option value="bi-house">🏠 Rumah</option>
                                <option value="bi-airplane">✈️ Liburan</option>
                                <option value="bi-car-front">🚗 Kendaraan</option>
                                <option value="bi-shield-check">🛡️ Dana Darurat</option>
                                <option value="bi-mortarboard">🎓 Pendidikan</option>
                                <option value="bi-gift">🎁 Hadiah</option>
                                <option value="bi-heart">❤️ Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="goalColor" class="form-label fw-semibold">Warna</label>
                            <input type="color" class="form-control form-control-color" id="goalColor" name="color" value="#0d6efd" title="Pilih warna">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="goalDescription" class="form-label fw-semibold">Deskripsi</label>
                        <textarea class="form-control" id="goalDescription" name="description" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="goalSubmitBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        <span id="goalSubmitText">Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Goal Detail Modal -->
<div class="modal fade" id="goalDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="goalDetailTitle">Detail Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="goalDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Deposit/Withdraw Modal -->
<div class="modal fade" id="goalTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="goalTransactionModalTitle">Setor ke Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="goalTransactionForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="goal_id" id="goalTxnGoalId">
                    <input type="hidden" name="type" id="goalTxnType" value="DEPOSIT">
                    
                    <div class="mb-3">
                        <label for="goalTxnAmount" class="form-label fw-semibold">Jumlah <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="goalTxnAmount" name="amount" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-text" id="goalTxnMaxText"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="goalTxnDescription" class="form-label fw-semibold">Deskripsi</label>
                        <input type="text" class="form-control" id="goalTxnDescription" name="description" placeholder="Opsional">
                    </div>
                    
                    <div class="mb-3">
                        <label for="goalTxnDate" class="form-label fw-semibold">Tanggal</label>
                        <input type="date" class="form-control" id="goalTxnDate" name="transaction_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="goalTransactionSubmitBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        <span id="goalTransactionSubmitText">Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
';
$extraScripts = '
<script src="/assets/js/goals.js"></script>
';
require __DIR__ . '/../layouts/main.php';