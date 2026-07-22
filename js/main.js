document.addEventListener('DOMContentLoaded', () => {
    // 1. Image Swap Logic (Max 5 images as requested)
    const sliderImage = document.getElementById('slider-image');
    if (sliderImage) {
        const images = [
            'images/slide1.png',
            'images/slide2.png',
            'images/slide3.png',
            'images/slide4.png'
        ];
        let currentImageIndex = 0;
        setInterval(() => {
            currentImageIndex = (currentImageIndex + 1) % images.length;
            sliderImage.style.opacity = 0; // Fade out effect
            setTimeout(() => {
                sliderImage.src = images[currentImageIndex];
                sliderImage.style.opacity = 1; // Fade in
            }, 400); // 400ms CSS transition matching
        }, 5000);
    }

    // 2. Demonstration of three pop-ups
    const btnAlert = document.getElementById('btn-alert');
    if (btnAlert) {
        btnAlert.addEventListener('click', () => {
            alert('Check-in time is from 3:00 PM and check-out is at 11:00 AM. Early check-in is subject to room availability.');
        });
    }

    const btnConfirm = document.getElementById('btn-confirm');
    if (btnConfirm) {
        btnConfirm.addEventListener('click', () => {
            const result = confirm('Would you like to add a 60-minute relaxing massage to your reservation for $120?');
            if (result) {
                alert('Excellent! Your spa session has been successfully added to your itinerary.');
            } else {
                alert('No problem! You can always book a session later at the concierge desk.');
            }
        });
    }

    const btnCustomModal = document.getElementById('btn-custom-modal');
    const customModal = document.getElementById('custom-modal');
    const closeModal = document.getElementById('close-modal');
    
    if (btnCustomModal && customModal && closeModal) {
        btnCustomModal.addEventListener('click', () => {
            customModal.classList.add('active');
        });

        closeModal.addEventListener('click', () => {
            customModal.classList.remove('active');
        });
    }
    
    // Navbar Glassmorphism Scroll Effect
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // Theme Toggle Logic
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    
    // Check local storage for theme
    const currentTheme = localStorage.getItem('hotel_theme');
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-mode');
        if (themeIcon) {
            themeIcon.classList.remove('ph-moon');
            themeIcon.classList.add('ph-sun');
        }
    }

    if (themeToggleBtn && themeIcon) {
        themeToggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            let theme = 'light';
            
            if (document.body.classList.contains('dark-mode')) {
                theme = 'dark';
                themeIcon.classList.remove('ph-moon');
                themeIcon.classList.add('ph-sun');
            } else {
                themeIcon.classList.remove('ph-sun');
                themeIcon.classList.add('ph-moon');
            }
            
            localStorage.setItem('hotel_theme', theme);
        });
    }
});
