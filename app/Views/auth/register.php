<?php
$title = 'Daftar - Personal Finance SaaS';
$content = '
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-wallet2 display-3 text-primary"></i>
                    <h3 class="fw-bold mt-3">Buat Akun Baru</h3>
                    <p class="text-muted">Mulai kelola keuangan pribadi Anda hari ini</p>
                </div>
                
                <form id="registerForm" novalidate>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label fw-semibold">Nama Depan</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" autocomplete="given-name" placeholder="Budi">
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label fw-semibold">Nama Belakang</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" autocomplete="family-name" placeholder="Santoso">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required autocomplete="email" placeholder="anda@email.com">
                        <div class="invalid-feedback">Masukkan email yang valid</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Kata Sandi</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password" minlength="6" placeholder="Minimal 6 karakter">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimal 6 karakter</div>
                        <div class="invalid-feedback">Kata sandi minimal 6 karakter</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label fw-semibold">Konfirmasi Kata Sandi</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="Ulangi kata sandi">
                        <div class="invalid-feedback">Kata sandi tidak cocok</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="base_currency" class="form-label fw-semibold">Mata Uang Utama</label>
                        <select class="form-select" id="base_currency" name="base_currency">
                            <option value="IDR" selected>IDR - Rupiah Indonesia</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="SGD">SGD - Singapore Dollar</option>
                            <option value="JPY">JPY - Japanese Yen</option>
                        </select>
                    </div>
                    
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">Saya menyetujui <a href="#" class="text-decoration-none">Syarat & Ketentuan</a> dan <a href="#" class="text-decoration-none">Kebijakan Privasi</a></label>
                        <div class="invalid-feedback">Anda harus menyetujui syarat & ketentuan</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3" id="registerBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        Daftar Sekarang
                    </button>
                </form>
                
                <div class="text-center">
                    <p class="text-muted mb-0">Sudah punya akun? <a href="/login" class="fw-semibold">Masuk</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
';
$extraScripts = '
<script>
document.getElementById("registerForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = document.getElementById("registerBtn");
    const spinner = btn.querySelector(".spinner-border");
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirm_password").value;
    
    if (password !== confirmPassword) {
        showToast("Kata sandi tidak cocok", "danger");
        return;
    }
    
    if (password.length < 6) {
        showToast("Kata sandi minimal 6 karakter", "danger");
        return;
    }
    
    const formData = new FormData(e.target);
    formData.delete("confirm_password");
    
    btn.disabled = true;
    spinner.classList.remove("d-none");
    
    try {
        const response = await fetch("/api/auth/register", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(Object.fromEntries(formData))
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast("Pendaftaran berhasil! Mengalihkan...", "success");
            setTimeout(() => window.location.href = "/dashboard", 1500);
        } else {
            showToast(data.error || "Gagal mendaftar", "danger");
        }
    } catch (err) {
        showToast("Terjadi kesalahan", "danger");
    } finally {
        btn.disabled = false;
        spinner.classList.add("d-none");
    }
});

document.getElementById("togglePassword").addEventListener("click", function() {
    const input = document.getElementById("password");
    const icon = this.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("bi-eye", "bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("bi-eye-slash", "bi-eye");
    }
});
</script>
';
require __DIR__ . '/../layouts/main.php';