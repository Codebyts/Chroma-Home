// nav-side.js — simple toggle for all screen sizes

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.querySelector('nav .toggle-sidebar');

    // Set initial sidebar state based on screen size
    if (window.innerWidth <= 768) {
        sidebar.classList.add('hidden');
    } else {
        sidebar.classList.remove('hidden');
    }

    // Toggle sidebar visibility when clicking hamburger
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('hidden');
            console.log('Sidebar toggled. Hidden:', sidebar.classList.contains('hidden'));
        });
    }

    // Close sidebar if clicking outside
    document.addEventListener('click', (e) => {
        if (!sidebar.classList.contains('hidden')) {
            if (!sidebar.contains(e.target) && !toggleSidebar.contains(e.target)) {
                sidebar.classList.add('hidden');
            }
        }
    });

    // Close sidebar with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !sidebar.classList.contains('hidden')) {
            sidebar.classList.add('hidden');
        }
    });

    // Hide sidebar on resize if screen becomes small
    window.addEventListener('resize', () => {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('hidden');
        } else {
            sidebar.classList.remove('hidden');
        }
    });

    // Profile dropdown
    const profile = document.querySelector('nav .profile');
    const imgProfile = profile?.querySelector('img');
    const dropdownProfile = profile?.querySelector('.profile-link');

    if (imgProfile && dropdownProfile) {
        imgProfile.addEventListener('click', (ev) => {
            ev.stopPropagation();
            dropdownProfile.classList.toggle('show');
        });

        document.addEventListener('click', (ev) => {
            if (!dropdownProfile.contains(ev.target) && !imgProfile.contains(ev.target)) {
                dropdownProfile.classList.remove('show');
            }
        });
    }
});

