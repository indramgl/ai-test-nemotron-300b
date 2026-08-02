<?php
$title = 'Profil - Personal Finance SaaS';
$content = '
<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-1">Profil Saya</h2>
        <p class="text-muted mb-0">Kelola informasi profil dan preferensi Anda</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                    <i class="bi bi-person-circle fs-1"></i>
                </div>
                <h4 class="fw-bold" id="profileName">User</h4>
                <p class="text-muted" id="profileEmail">email@example.com</p>
                <div class="mt-3">
                    <span class="badge bg-primary" id="profilePlan">Free</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="fw-bold mb-0">Informasi Profil</h5>
            </div>
            <div class="card-body">
                <form id="profileForm" novalidate>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="profileFirstName" class="form-label fw-semibold">Nama Depan</label>
                            <input type="text" class="form-control" id="profileFirstName" name="first_name">
                        </div>
                        <div class="col-md-6">
                            <label for="profileLastName" class="form-label fw-semibold">Nama Belakang</label>
                            <input type="text" class="form-control" id="profileLastName" name="last_name">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profileEmail" class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" id="profileEmail" name="email" readonly>
                        <div class="form-text">Email tidak dapat diubah. Hubungi support untuk mengubah email.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profilePhone" class="form-label fw-semibold">Nomor Telepon</label>
                        <input type="tel" class="form-control" id="profilePhone" name="phone" placeholder="08xxxxxxxxxx">
                    </div>
                    
                    <div class="mb-3">
                        <label for="profileCurrency" class="form-label fw-semibold">Mata Uang Utama</label>
                        <select class="form-select" id="profileCurrency" name="base_currency">
                            <option value="IDR">IDR - Rupiah Indonesia</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="SGD">SGD - Singapore Dollar</option>
                            <option value="JPY">JPY - Japanese Yen</option>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" id="profileSubmitBtn">
                            <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                            <span id="profileSubmitText">Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Section -->
<div class="row g-4 mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="fw-bold mb-0">Ubah Kata Sandi</h5>
            </div>
            <div class="card-body">
                <form id="passwordForm" novalidate>
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label fw-semibold">Kata Sandi Saat Ini</label>
                        <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label fw-semibold">Kata Sandi Baru</label>
                        <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6">
                        <div class="form-text">Minimal 6 karakter</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirmNewPassword" class="form-label fw-semibold">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" class="form-control" id="confirmNewPassword" name="confirm_new_password" required>
                    </div>
                    <button type="submit" class="btn btn-outline-primary" id="passwordSubmitBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        <span id="passwordSubmitText">Ubah Kata Sandi</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
';
$extraScripts = '
<script src="/assets/js/profile.js"></script>
';
require __DIR__ . '/../layouts/main.php';