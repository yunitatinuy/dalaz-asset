document.addEventListener('DOMContentLoaded', function() {
    
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const headerNav = document.getElementById('headerNav');
    const userDropdown = document.querySelector('.dropdown-menu'); 
    const userAvatar = document.querySelector('.user-avatar');

    // 1. LOGIKA MOBILE MENU (HAMBURGER)
    if (mobileBtn && headerNav) {
        mobileBtn.addEventListener('click', function(e) {
            e.stopPropagation(); 
            headerNav.classList.toggle('active');
            
            // Tutup Profil jika Menu Hamburger dibuka
            if (userDropdown && userDropdown.classList.contains('show')) {
                userDropdown.classList.remove('show');
            }
            
            // Ubah icon garis tiga jadi X
            const icon = mobileBtn.querySelector('i');
            if (headerNav.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }

    // 2. LOGIKA DROPDOWN ASSET DATA
    const dropdownLi = document.querySelector('.nav-item-dropdown'); 

    if (dropdownLi) {
        const link = dropdownLi.querySelector('a.nav-link');
        const submenu = dropdownLi.querySelector('.nav-submenu');

        if (link && submenu) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Buka/Tutup Submenu secara bergantian saat diklik
                submenu.classList.toggle('show');
            });
        }
    }

    // 3. LOGIKA USER PROFILE (AVATAR)
    if (userAvatar && userDropdown) {
        userAvatar.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Tutup Menu Hamburger utama jika Profil ditekan
            if (headerNav && headerNav.classList.contains('active')) {
                headerNav.classList.remove('active');
                if (mobileBtn) {
                    const icon = mobileBtn.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
            
            // Tutup Submenu Asset (jika terbuka)
            const submenu = document.querySelector('.nav-submenu');
            if (submenu) submenu.classList.remove('show');
            
            // Toggle dropdown user
            userDropdown.classList.toggle('show');
        });
    }

    // 4. LOGIKA KLIK DI LUAR MENU (TUTUP SEMUA)
    document.addEventListener('click', function(e) {
        const header = document.querySelector('.header');
        
        // Tutup menu utama
        if (headerNav && headerNav.classList.contains('active') && !header.contains(e.target)) {
            headerNav.classList.remove('active');
            if(mobileBtn) {
                mobileBtn.querySelector('i').classList.remove('fa-times');
                mobileBtn.querySelector('i').classList.add('fa-bars');
            }
        }

        // Tutup submenu asset data
        if (!e.target.closest('.nav-item-dropdown')) {
            document.querySelectorAll('.nav-submenu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }

        // Tutup profil
        if (!e.target.closest('.user-profile')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
});