// Profile JavaScript

document.addEventListener('DOMContentLoaded', async () => {
    await loadProfile();
    setupProfileForm();
    setupPasswordForm();
});

async function loadProfile() {
    try {
        const data = await Api.get('/api/profile');
        
        // Update profile info
        document.getElementById('profileName').textContent = 
            `${data.user.first_name || ''} ${data.user.last_name || ''}`.trim() || 'User';
        document.getElementById('profileEmail').textContent = data.user.email;
        document.getElementById('profilePlan').textContent = data.subscription?.plan_name || 'Free';
        
        // Fill form fields
        document.getElementById('profileFirstName').value = data.user.first_name || '';
        document.getElementById('profileLastName').value = data.user.last_name || '';
        document.getElementById('profileEmail').value = data.user.email || '';
        document.getElementById('profilePhone').value = data.user.phone || '';
        document.getElementById('profileCurrency').value = data.user.base_currency || 'IDR';
        
        // Update subscription badge
        const planBadge = document.getElementById('profilePlan');
        if (data.subscription?.plan_name === 'Pro') {
            planBadge.className = 'badge bg-success';
            planBadge.textContent = 'Pro';
        } else {
            planBadge.className = 'badge bg-primary';
            planBadge.textContent = 'Free';
        }
    } catch (err) {
        console.error('Failed to load profile:', err);
        showToast('Gagal memuat profil', 'danger');
    }
}

function setupProfileForm() {
    const form = document.getElementById('profileForm');
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = document.getElementById('profileSubmitBtn');
        const spinner = btn.querySelector('.spinner-border');
        const submitText = document.getElementById('profileSubmitText');
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        btn.disabled = true;
        spinner.classList.remove('d-none');
        
        try {
            await Api.put('/api/profile', data);
            showToast('Profil diperbarui', 'success');
        } catch (err) {
            showToast(err.message, 'danger');
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    });
}

function setupPasswordForm() {
    const form = document.getElementById('passwordForm');
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmNewPassword').value;
        
        if (newPassword !== confirmPassword) {
            showToast('Kata sandi baru tidak cocok', 'danger');
            return;
        }
        
        if (newPassword.length < 6) {
            showToast('Kata sandi minimal 6 karakter', 'danger');
            return;
        }
        
        const btn = document.getElementById('passwordSubmitBtn');
        const spinner = btn.querySelector('.spinner-border');
        const submitText = document.getElementById('passwordSubmitText');
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        btn.disabled = true;
        spinner.classList.remove('d-none');
        
        try {
            await Api.post('/api/profile/password', data);
            showToast('Kata sandi berhasil diubah', 'success');
            form.reset();
        } catch (err) {
            showToast(err.message, 'danger');
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    });
}