// Main App JavaScript - Handles navbar rendering based on auth status

document.addEventListener('DOMContentLoaded', async () => {
    await updateNavbar();
});

// Update navbar based on authentication status
async function updateNavbar() {
    const mainNavLinks = document.getElementById('mainNavLinks');
    const userNavLinks = document.getElementById('userNavLinks');
    const userName = document.getElementById('userName');
    const userEmail = document.getElementById('userEmail');

    try {
        const response = await fetch('/api/dashboard', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include'
        });
        
        if (response.ok) {
            const data = await response.json();
            
            // User is logged in
            if (userName && data.user) {
                const name = data.user.first_name ? `${data.user.first_name} ${data.user.last_name || ''}`.trim() : data.user.email;
                userName.textContent = name;
            }
            if (userEmail && data.user) {
                userEmail.textContent = data.user.email;
            }
            
            // Render logged-in navigation
            renderLoggedInNav();
        } else {
            // User not logged in
            renderLoggedOutNav();
        }
    } catch (err) {
        console.error('Error checking auth status:', err);
        renderLoggedOutNav();
    }
}

function renderLoggedInNav() {
    const mainNavLinks = document.getElementById('mainNavLinks');
    const userNavLinks = document.getElementById('userNavLinks');
    
    // Main navigation links for logged-in users
    mainNavLinks.innerHTML = `
        <li class="nav-item">
            <a class="nav-link" href="/dashboard"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/accounts"><i class="bi bi-credit-card me-1"></i>Rekening</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/transactions"><i class="bi bi-cash-stack me-1"></i>Transaksi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/budgets"><i class="bi bi-graph-up me-1"></i>Anggaran</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/goals"><i class="bi bi-bullseye me-1"></i>Target</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/reports"><i class="bi bi-bar-chart me-1"></i>Laporan</a>
        </li>
    `;
    
    // User dropdown menu
    userNavLinks.innerHTML = `
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle me-1"></i><span id="userName">User</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header" id="userEmail"></h6></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/profile"><i class="bi bi-person me-2"></i>Profil</a></li>
                <li><a class="dropdown-item" href="/subscription"><i class="bi bi-credit-card me-2"></i>Langganan</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" id="logoutBtn"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </li>
    `;
    
    // Re-attach logout handler
    document.getElementById('logoutBtn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            await fetch('/api/auth/logout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include'
            });
            window.location.href = '/login';
        } catch (err) {
            showToast('Gagal logout', 'danger');
        }
    });
}

function renderLoggedOutNav() {
    const mainNavLinks = document.getElementById('mainNavLinks');
    const userNavLinks = document.getElementById('userNavLinks');
    
    // Main navigation for non-logged-in users (public pages)
    mainNavLinks.innerHTML = `
        <li class="nav-item">
            <a class="nav-link" href="/#features"><i class="bi bi-star me-1"></i>Fitur</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/#pricing"><i class="bi bi-tag me-1"></i>Harga</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/login"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/register"><i class="bi bi-person-plus me-1"></i>Daftar</a>
        </li>
    `;
    
    // User dropdown for non-logged-in (show login/register)
    userNavLinks.innerHTML = `
        <li class="nav-item">
            <a class="nav-link" href="/login"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/register"><i class="bi bi-person-plus me-1"></i>Daftar</a>
        </li>
    `;
}