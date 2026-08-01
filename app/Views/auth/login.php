<?php
$title = 'Masuk - Personal Finance SaaS';
$content = '
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-wallet2 display-3 text-primary"></i>
                    <h3 class="fw-bold mt-3">Masuk ke Akun Anda</h3>
                    <p class="text-muted">Masuk untuk mengelola keuangan pribadi Anda</p>
                </div>
                
                <form id="loginForm" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required autocomplete="email" placeholder="anda@email.com">
                        <div class="invalid-feedback">Masukkan email yang valid</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Kata Sandi</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Masukkan kata sandi</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Ingat saya</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3" id="loginBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        Masuk
                    </button>
                </form>
                
                <div class="text-center">
                    <p class="text-muted mb-0">Belum punya akun? <a href="/register" class="fw-semibold">Daftar gratis</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
';
$extraScripts = '
<script>
document.getElementById("loginForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = document.getElementById("loginBtn");
    const spinner = btn.querySelector(".spinner-border");
    const formData = new FormData(e.target);
    
    btn.disabled = true;
    spinner.classList.remove("d-none");
    
    try {
        const response = await fetch("/api/auth/login", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(Object.fromEntries(formData))
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast("Berhasil masuk!", "success");
            setTimeout(() => window.location.href = "/dashboard", 1000);
        } else {
            showToast(data.error || "Gagal masuk", "danger");
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