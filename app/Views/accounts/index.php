<?php
$title = 'Rekening - Personal Finance SaaS';
$content = '
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-1">Kelola Rekening</h2>
                <p class="text-muted mb-0">Kelola kas, bank, e-wallet, dan investasi Anda</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#accountModal">
                <i class="bi bi-plus-circle me-1"></i>Tambah Rekening
            </button>
        </div>
    </div>
</div>

<!-- Accounts Grid -->
<div class="row g-3" id="accountsGrid">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Memuat rekening...</p>
    </div>
</div>

<!-- Account Modal -->
<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalTitle">Tambah Rekening</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="accountForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id" id="accountId">
                    
                    <div class="mb-3">
                        <label for="accountTypeId" class="form-label fw-semibold">Jenis Rekening <span class="text-danger">*</span></label>
                        <select class="form-select" id="accountTypeId" name="account_type_id" required>
                            <option value="">Pilih jenis rekening</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="accountName" class="form-label fw-semibold">Nama Rekening <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="accountName" name="name" required placeholder="Contoh: BCA Utama, GoPay, Kas Dompet">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="initialBalance" class="form-label fw-semibold">Saldo Awal</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="initialBalance" name="initial_balance" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="accountCurrency" class="form-label fw-semibold">Mata Uang</label>
                            <select class="form-select" id="accountCurrency" name="currency">
                                <option value="IDR">IDR</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="SGD">SGD</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="accountSubmitBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        <span id="accountSubmitText">Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Rekening</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus rekening <strong id="deleteAccountName"></strong>?</p>
                <p class="text-muted small">Rekening akan dinonaktifkan (soft delete) dan tidak bisa digunakan untuk transaksi baru.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteAccount">Hapus</button>
            </div>
        </div>
    </div>
</div>
';
$extraScripts = '
<script src="/assets/js/accounts.js"></script>
';
require __DIR__ . '/../layouts/main.php';