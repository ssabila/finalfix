/**
 * Menampilkan notifikasi custom yang konsisten di seluruh aplikasi
 * @param {string} message - Pesan yang akan ditampilkan
 * @param {string} type - Tipe notifikasi: 'success', 'error', 'info', 'warning'
 * @param {number} duration - Durasi tampil dalam milidetik (default: 4000)
 */
function showCustomNotification(message, type = 'info', duration = 4000) {
    // Hapus notifikasi lama jika ada
    const existingNotification = document.querySelector('.custom-notification');
    if (existingNotification) {
        hideNotification(existingNotification);
    }

    // Buat elemen notifikasi
    const notification = document.createElement('div');
    notification.className = `custom-notification notification-${type}`;
    
    // Tentukan icon berdasarkan tipe
    let icon = '';
    switch (type) {
        case 'success':
            icon = 'fa-check-circle';
            break;
        case 'error':
            icon = 'fa-exclamation-circle';
            break;
        case 'warning':
            icon = 'fa-exclamation-triangle';
            break;
        case 'info':
        default:
            icon = 'fa-info-circle';
            break;
    }

    // Struktur HTML notifikasi
    notification.innerHTML = `
        <div class="notification-content">
            <i class="notification-icon fas ${icon}"></i>
            <div class="notification-text">${message}</div>
            <button class="notification-close" onclick="hideNotification(this.parentElement.parentElement)">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="notification-progress"></div>
    `;

    // Tambahkan ke DOM
    document.body.appendChild(notification);

    // Trigger animasi masuk
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    // Auto hide setelah duration
    setTimeout(() => {
        hideNotification(notification);
    }, duration);

    return notification;
}

/**
 * Menyembunyikan notifikasi dengan animasi
 * @param {HTMLElement} notification - Element notifikasi yang akan disembunyikan
 */
function hideNotification(notification) {
    if (!notification || !notification.parentElement) return;
    
    notification.classList.remove('show');
    notification.classList.add('hide');
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.parentElement.removeChild(notification);
        }
    }, 400);
}

/**
 * Menampilkan notifikasi sukses
 * @param {string} message - Pesan sukses
 */
function showSuccessNotification(message) {
    showCustomNotification(message, 'success');
}

/**
 * Menampilkan notifikasi error
 * @param {string} message - Pesan error
 */
function showErrorNotification(message) {
    showCustomNotification(message, 'error');
}

/**
 * Menampilkan notifikasi info
 * @param {string} message - Pesan info
 */
function showInfoNotification(message) {
    showCustomNotification(message, 'info');
}

/**
 * Menampilkan notifikasi warning
 * @param {string} message - Pesan warning
 */
function showWarningNotification(message) {
    showCustomNotification(message, 'warning');
}

// Buat fungsi tersedia secara global
window.showCustomNotification = showCustomNotification;
window.hideNotification = hideNotification;
window.showSuccessNotification = showSuccessNotification;
window.showErrorNotification = showErrorNotification;
window.showInfoNotification = showInfoNotification;
window.showWarningNotification = showWarningNotification;
// preloader logic
window.addEventListener('load', () => {
    const preloader = document.querySelector('.preloader');

    if (preloader) {
        const minimumDisplayTime = 1000; // 1 detik

        setTimeout(() => {
            // Menambahkan kelas 'hidden' untuk memicu transisi fade-out
            preloader.classList.add('hidden');
        }, minimumDisplayTime);
    }
});

// fungsi global
function showNotification(message, type = "info") {
    const existingNotifications = document.querySelectorAll(".notification");
    existingNotifications.forEach((notification) => notification.remove());

    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        min-width: 300px;
        max-width: 400px;
        animation: slideInRight 0.3s ease;
        border-left: 4px solid ${getNotificationColor(type)};
    `;

    const iconMap = {
        success: "fas fa-check-circle",
        error: "fas fa-exclamation-circle",
        warning: "fas fa-exclamation-triangle",
        info: "fas fa-info-circle",
    };

    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.5rem;">
            <i class="${iconMap[type]}" style="font-size: 1.2rem; color: ${getNotificationColor(type)};"></i>
            <span style="flex: 1; color: #2c3e50; font-weight: 500;">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: #7f8c8d; cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: all 0.3s ease;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

function getNotificationColor(type) {
    const colors = {
        success: "#2ecc71",
        error: "#e74c3c",
        warning: "#f39c12",
        info: "#4bc3ff",
    };
    return colors[type] || colors.info;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
}

function formatTime(timeString) {
    return timeString.substring(0, 5);
}

function validateField(e) {
    const field = e.target;
    const formGroup = field.closest(".form-group");

    if (!formGroup) return;

    formGroup.classList.remove("error", "success");

    if (field.checkValidity()) {
        formGroup.classList.add("success");
    } else {
        formGroup.classList.add("error");
        let errorMessage = formGroup.querySelector(".error-message");
        if (!errorMessage) {
            errorMessage = document.createElement("div");
            errorMessage.className = "error-message";
            formGroup.appendChild(errorMessage);
        }
        if (field.validity.valueMissing) {
            errorMessage.textContent = "Field ini wajib diisi";
        } else if (field.validity.typeMismatch) {
            errorMessage.textContent = "Format tidak valid";
        } else {
            errorMessage.textContent = "Input tidak valid";
        }
    }
}

function clearFieldError(e) {
    const field = e.target;
    const formGroup = field.closest(".form-group");

    if (formGroup && formGroup.classList.contains("error")) {
        formGroup.classList.remove("error");
        const errorMessage = formGroup.querySelector(".error-message");
        if (errorMessage) {
            errorMessage.style.display = "none";
        }
    }
}

// event listener setelah dom siap
document.addEventListener("DOMContentLoaded", () => {
    
    function setupNavigation() {
        const hamburger = document.getElementById("hamburger");
        const navMenu = document.getElementById("nav-menu");

        if (hamburger && navMenu) {
            hamburger.addEventListener("click", () => {
                hamburger.classList.toggle("active");
                navMenu.classList.toggle("active");
            });

            const navLinks = navMenu.querySelectorAll(".nav-link");
            navLinks.forEach((link) => {
                link.addEventListener("click", () => {
                    hamburger.classList.remove("active");
                    navMenu.classList.remove("active");
                });
            });

            document.addEventListener("click", (e) => {
                if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
                    hamburger.classList.remove("active");
                    navMenu.classList.remove("active");
                }
            });
        }
    }

    function setupGlobalEventListeners() {
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                const activeModals = document.querySelectorAll(".modal");
                activeModals.forEach((modal) => {
                    if (typeof closeModal === 'function' && modal.id) {
                        closeModal(modal.id);
                    }
                });
            }
        });

        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("modal")) {
                if (typeof closeModal === 'function' && e.target.id) {
                    closeModal(e.target.id);
                }
            }
        });

        const forms = document.querySelectorAll("form");
        forms.forEach((form) => {
            const inputs = form.querySelectorAll("input[required], select[required], textarea[required]");
            inputs.forEach((input) => {
                input.addEventListener("blur", validateField);
                input.addEventListener("input", clearFieldError);
            });
        });
    }
    
    setupNavigation();
    setupGlobalEventListeners();

    const animatedElements = document.querySelectorAll(".animate-on-scroll");
    if (animatedElements.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target); // Stop observing once visible to prevent re-animation
                }
            });
        }, {
            threshold: 0.1 // Trigger when 10% of the element is visible
        });

        animatedElements.forEach(el => {
            observer.observe(el);
        });
    }
    
    const style = document.createElement("style");
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
});