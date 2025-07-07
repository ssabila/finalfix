// ================================================================
// FUNGSI GLOBAL untuk Halaman Profil
// Didefinisikan di luar agar bisa diakses oleh atribut onclick di PHP.
// ================================================================

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Mencegah scroll di background
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto'; // Kembalikan scroll
  }
}

function openAvatarModal() {
    openModal('avatar-modal');
}

// FUNGSI EDIT PROFILE - DIPERBAIKI
function openEditProfileModal() {
    console.log('Opening edit profile modal'); // Debug log
    
    const modal = document.getElementById('edit-profile-modal');
    if (!modal) {
        console.error('Modal edit-profile-modal tidak ditemukan!');
        showCustomNotification('Modal tidak ditemukan!', 'error');
        return;
    }
    
    // Cek apakah userProfileData tersedia
    if (typeof userProfileData === 'undefined') {
        console.warn('userProfileData tidak tersedia, data akan kosong');
    } else {
        // Isi form dengan data yang ada
        try {
            const fields = {
                'edit_first_name': userProfileData.first_name || '',
                'edit_last_name': userProfileData.last_name || '',
                'edit_nim': userProfileData.nim || '',
                'edit_email': userProfileData.email || '',
                'edit_phone': userProfileData.phone || ''
            };
            
            // Set nilai untuk setiap field
            Object.keys(fields).forEach(fieldId => {
                const element = modal.querySelector('#' + fieldId);
                if (element) {
                    element.value = fields[fieldId];
                } else {
                    console.warn('Element tidak ditemukan:', fieldId);
                }
            });
        } catch (error) {
            console.error('Error mengisi form:', error);
        }
    }
    
    // Reset password fields
    const passwordFields = ['current_password', 'new_password', 'confirm_password'];
    passwordFields.forEach(fieldId => {
        const element = modal.querySelector('#' + fieldId);
        if (element) {
            element.value = '';
        }
    });
    
    openModal('edit-profile-modal');
}

function editItem(id, type) {
  const itemElement = document.querySelector(`.profile-item[data-id='${id}'][data-type='${type}']`);
  if (!itemElement) return;

  const dataScript = itemElement.querySelector('.item-edit-data');
  if (!dataScript) {
    console.error('Data untuk edit tidak ditemukan!');
    return;
  }
  const itemData = JSON.parse(dataScript.textContent);
  const modalId = `edit-${type}-modal`;
  const modal = document.getElementById(modalId);

  if (!modal) {
    console.error(`Modal dengan ID ${modalId} tidak ditemukan!`);
    return;
  }

  // Isi form dengan data yang ada
  modal.querySelector('input[name="item_id"]').value = itemData.id;

  if (type === 'lost-found') {
    modal.querySelector('#edit_lf_type').value = itemData.type;
    modal.querySelector('#edit_lf_title').value = itemData.title;
    modal.querySelector('#edit_lf_category_id').value = itemData.category_id;
    modal.querySelector('#edit_lf_description').value = itemData.description;
    modal.querySelector('#edit_lf_location').value = itemData.location;
    modal.querySelector('#edit_lf_date_occurred').value = itemData.date_occurred;
    const currentImageContainer = modal.querySelector('#edit-lf-current-image');
    const currentImage = modal.querySelector('#edit-lf-current-img');
    if(itemData.image && currentImage) {
      currentImage.src = itemData.image;
      currentImageContainer.style.display = 'block';
    } else if (currentImageContainer) {
      currentImageContainer.style.display = 'none';
    }
  } else if (type === 'activity') {
    modal.querySelector('#edit_act_title').value = itemData.title;
    modal.querySelector('#edit_act_category_id').value = itemData.category_id;
    modal.querySelector('#edit_act_description').value = itemData.description;
    modal.querySelector('#edit_act_event_date').value = itemData.event_date;
    modal.querySelector('#edit_act_event_time').value = itemData.event_time;
    modal.querySelector('#edit_act_location').value = itemData.location;
    modal.querySelector('#edit_act_organizer').value = itemData.organizer;
    const currentImageContainer = modal.querySelector('#edit-act-current-image');
    const currentImage = modal.querySelector('#edit-act-current-img');
    if(itemData.image && currentImage) {
      currentImage.src = itemData.image;
      currentImageContainer.style.display = 'block';
    } else if (currentImageContainer) {
      currentImageContainer.style.display = 'none';
    }
  }

  openModal(modalId);
}

// PERBAIKAN FUNGSI DELETE - Ini yang utama diperbaiki
function deleteItem(id, type, title) {
  console.log('Delete function called with:', {id, type, title}); // Debug log
  
  const modal = document.getElementById('delete-modal');
  if (!modal) {
    console.error('Delete modal tidak ditemukan!');
    showCustomNotification('Modal delete tidak ditemukan!', 'error');
    return;
  }

  // Set judul item yang akan dihapus
  const titleElement = modal.querySelector('#delete-item-title');
  if (titleElement) {
    titleElement.textContent = title || 'Item';
  }

  // Set form data
  const form = modal.querySelector('#delete-form');
  if (!form) {
    console.error('Delete form tidak ditemukan!');
    showCustomNotification('Form delete tidak ditemukan!', 'error');
    return;
  }

  // Set ID item
  const itemIdInput = form.querySelector('#delete-item-id');
  if (itemIdInput) {
    itemIdInput.value = id;
  }

  // Set action type dengan benar
  const actionInput = form.querySelector('#delete-action-type');
  if (actionInput) {
    // Hapus name attribute yang lama dan set yang baru
    actionInput.removeAttribute('name');
    
    if (type === 'lost-found') {
      actionInput.setAttribute('name', 'delete_lost_found');
    } else if (type === 'activity') {
      actionInput.setAttribute('name', 'delete_activity');
    }
    
    actionInput.value = '1';
  }

  // Tampilkan modal
  openModal('delete-modal');
}

// PERBAIKAN FUNGSI CONFIRM DELETE
function confirmDelete() {
    const form = document.getElementById('delete-form');
    if (!form) {
        console.error('Form delete tidak ditemukan!');
        showCustomNotification('Form delete tidak ditemukan!', 'error');
        return;
    }
    
    // Tampilkan loading state
    const confirmBtn = document.querySelector('#delete-modal .btn-danger');
    if (confirmBtn) {
        const originalText = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
        confirmBtn.disabled = true;
        
        // Submit form setelah delay singkat untuk UX
        setTimeout(() => {
            form.submit();
        }, 300);
    } else {
        // Fallback jika button tidak ditemukan
        form.submit();
    }
}

function previewImage(input, previewId) {
    const previewContainer = document.getElementById(previewId);
    if (!previewContainer) return;

    const previewImg = previewContainer.querySelector('img');
    const file = input.files[0];

    if (file && previewImg) {
        // Validasi file
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            showCustomNotification('Hanya file gambar (JPG, PNG, GIF) yang diperbolehkan!', 'error');
            input.value = '';
            return;
        }

        // Validasi ukuran (max 5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            showCustomNotification('Ukuran file terlalu besar! Maksimal 5MB.', 'error');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
    }
}

function removeImage(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (input) input.value = '';
    if (preview) preview.style.display = 'none';
}

function previewAvatar(input) {
    const saveBtn = document.getElementById('save-avatar-btn');
    const newPreviewImg = document.getElementById('new-avatar-img');
    const newPreviewContainer = document.getElementById('new-avatar-preview');
    const noPreviewPlaceholder = document.getElementById('no-preview-placeholder');

    const file = input.files[0];
    if (file) {
        // Validasi file avatar
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!validTypes.includes(file.type)) {
            showCustomNotification('Format avatar harus JPG atau PNG!', 'error');
            input.value = '';
            return;
        }

        // Validasi ukuran (max 2MB untuk avatar)
        const maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            showCustomNotification('Ukuran avatar terlalu besar! Maksimal 2MB.', 'error');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            if (newPreviewImg) newPreviewImg.src = e.target.result;
            if (newPreviewContainer) newPreviewContainer.style.display = 'block';
            if (noPreviewPlaceholder) noPreviewPlaceholder.style.display = 'none';
            if(saveBtn) saveBtn.disabled = false;
        };
        reader.readAsDataURL(file);
    } else {
        if(saveBtn) saveBtn.disabled = true;
    }
}

// Fungsi untuk menampilkan notifikasi custom (TANPA CSS INLINE)
function showCustomNotification(message, type = 'info') {
    // Hapus notifikasi lama jika ada
    const existingNotification = document.querySelector('.custom-notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Buat notifikasi baru
    const notification = document.createElement('div');
    notification.className = `custom-notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="notification-icon fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Tambahkan ke body
    document.body.appendChild(notification);

    // Auto remove setelah 5 detik
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.add('notification-exit');
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// ================================================================
// EVENT LISTENERS DAN INISIALISASI
// ================================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile.js loaded successfully'); // Debug log
    
    // Tab switching functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.dataset.tab;
            
            // Remove active class dari semua tab
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class ke tab yang diklik
            button.classList.add('active');
            const targetContent = document.getElementById(`${tabName}-tab`);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });

    // Close modal dengan ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            // Tutup semua modal yang terbuka
            const openModals = document.querySelectorAll('.modal[style*="flex"]');
            openModals.forEach(modal => {
                modal.style.display = 'none';
            });
            document.body.style.overflow = 'auto';
        }
    });

    // Close modal ketika klik di luar modal content
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });

    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Form validasi untuk edit profile
    const editProfileForm = document.getElementById('edit-profile-form');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const currentPassword = document.getElementById('current_password');

            if (newPassword && confirmPassword && currentPassword) {
                // Jika user ingin mengubah password
                if (newPassword.value || confirmPassword.value || currentPassword.value) {
                    if (!currentPassword.value) {
                        e.preventDefault();
                        showCustomNotification('Masukkan password saat ini untuk mengubah password', 'error');
                        return;
                    }
                    if (newPassword.value !== confirmPassword.value) {
                        e.preventDefault();
                        showCustomNotification('Password baru dan konfirmasi password tidak sama', 'error');
                        return;
                    }
                    if (newPassword.value.length < 8) {
                        e.preventDefault();
                        showCustomNotification('Password baru minimal 8 karakter', 'error');
                        return;
                    }
                }
            }
        });
    }

    // File upload validations
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Check file size based on input type
                let maxSize = 5 * 1024 * 1024; // 5MB default
                if (this.name === 'avatar') {
                    maxSize = 2 * 1024 * 1024; // 2MB for avatar
                }
                
                if (file.size > maxSize) {
                    const maxSizeMB = maxSize / (1024 * 1024);
                    showCustomNotification(`Ukuran file terlalu besar. Maksimal ${maxSizeMB}MB.`, 'error');
                    this.value = '';
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showCustomNotification('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau GIF.', 'error');
                    this.value = '';
                    return;
                }
            }
        });
    });

    // Debug: Log semua tombol delete yang ada
    const deleteButtons = document.querySelectorAll('.btn-delete, .delete-btn');
    console.log('Tombol delete ditemukan:', deleteButtons.length);
    
    // Pastikan event onclick terpasang dengan benar
    deleteButtons.forEach((btn, index) => {
        console.log(`Button ${index}:`, btn.getAttribute('onclick'));
    });

    // Confirm before leaving page if form has changes
    let formChanged = false;
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });
    });

    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Reset formChanged flag when form is submitted
    forms.forEach(form => {
        form.addEventListener('submit', () => {
            formChanged = false;
        });
    });
});