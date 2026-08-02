// Subscription JavaScript

document.addEventListener('DOMContentLoaded', async () => {
    await loadSubscription();
    setupUpgradeButtons();
});

async function loadSubscription() {
    try {
        const data = await Api.get('/api/subscription');
        
        // Update current plan display
        if (data.current_subscription) {
            document.getElementById('currentPlanBadge').className = 'badge bg-success';
            document.getElementById('currentPlanBadge').textContent = data.current_subscription.name || 'Pro';
            document.getElementById('currentPlanName').textContent = data.current_subscription.name || 'Pro';
            document.getElementById('currentPlanDesc').textContent = getPlanDescription(data.current_subscription.name);
            document.getElementById('currentPlanPeriod').textContent = `Mulai: ${formatDate(data.current_subscription.start_date)} | Berakhir: ${formatDate(data.current_subscription.end_date)}`;
            document.getElementById('currentPlanStatus').className = 'badge bg-success fs-6 px-3 py-2';
            document.getElementById('currentPlanStatus').textContent = 'Aktif';
        } else {
            // Free tier
            document.getElementById('currentPlanBadge').className = 'badge bg-primary';
            document.getElementById('currentPlanBadge').textContent = 'Free';
            document.getElementById('currentPlanName').textContent = 'Free';
            document.getElementById('currentPlanDesc').textContent = 'Maksimal 3 rekening, kategori standar, pencatatan transaksi dasar';
            document.getElementById('currentPlanPeriod').textContent = 'Belum berlangganan';
            document.getElementById('currentPlanStatus').className = 'badge bg-secondary fs-6 px-3 py-2';
            document.getElementById('currentPlanStatus').textContent = 'Gratis';
        }
        
        // Highlight current plan
        if (data.current_subscription && data.current_subscription.name === 'Pro') {
            document.getElementById('planPro').classList.add('border-primary', 'shadow-lg');
            document.getElementById('planPro').querySelector('.card-header').classList.add('bg-primary', 'text-white');
            document.getElementById('planFree').classList.add('opacity-50');
        } else {
            document.getElementById('planFree').classList.add('border-primary', 'shadow-lg');
            document.getElementById('planFree').querySelector('.card-header').classList.add('bg-primary', 'text-white');
            document.getElementById('planPro').classList.add('opacity-50');
        }
        
        // Load billing history (placeholder for now)
        loadBillingHistory();
        
    } catch (err) {
        console.error('Failed to load subscription:', err);
        showToast('Gagal memuat langganan', 'danger');
    }
}

function getPlanDescription(planName) {
    const descriptions = {
        'Free': 'Maksimal 3 rekening, kategori standar, pencatatan transaksi dasar',
        'Pro': 'Rekening tak terbatas, kategori kustom tak terbatas, transaksi berulang, ekspor CSV/Excel/PDF, analitik lanjutan & Net Worth'
    };
    return descriptions[planName] || '';
}

function loadBillingHistory() {
    // Placeholder for billing history
    // In a real implementation, this would fetch from an API endpoint
    const tbody = document.getElementById('billingBody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    <i class="bi bi-info-circle me-2"></i>
                    Riwayat tagihan akan muncul setelah upgrade ke Pro
                </td>
            </tr>
        `;
    }
}

function setupUpgradeButtons() {
    document.getElementById('upgradeMonthlyBtn')?.addEventListener('click', () => upgradePlan('monthly'));
    document.getElementById('upgradeYearlyBtn')?.addEventListener('click', () => upgradePlan('yearly'));
}

async function upgradePlan(cycle) {
    try {
        // Show loading
        const btn = document.getElementById(cycle === 'monthly' ? 'upgradeMonthlyBtn' : 'upgradeYearlyBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
        btn.disabled = true;
        
        // First, let's show a confirmation modal
        const confirmed = confirm(`Yakin ingin upgrade ke Pro ${cycle === 'monthly' ? 'Bulanan' : 'Tahunan'}?`);
        if (!confirmed) {
            btn.innerHTML = originalText;
            btn.disabled = false;
            return;
        }
        
        // Find Pro plan ID
        const subscriptionData = await Api.get('/api/subscription');
        const proPlan = subscriptionData.plans?.find(p => p.name === 'Pro');
        
        if (!proPlan) {
            showToast('Paket Pro tidak ditemukan', 'danger');
            return;
        }
        
        const response = await Api.post('/api/subscription/upgrade', {
            plan_id: proPlan.id,
            billing_cycle: cycle
        });
        
        showToast('Berhasil upgrade ke Pro!', 'success');
        await loadSubscription(); // Reload subscription data
        
    } catch (err) {
        console.error('Upgrade failed:', err);
        showToast('Gagal upgrade: ' + err.message, 'danger');
    }
}