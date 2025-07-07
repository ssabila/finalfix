// ================================================================
// FUNGSI GLOBAL untuk Halaman Profil
// Didefinisikan di luar agar bisa diakses oleh atribut onclick di PHP.
// ================================================================

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'flex';
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'none';
  }
}

function openAvatarModal() {
    openModal('avatar-modal');
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

function deleteItem(id, type, title) {
  const modal = document.getElementById('delete-modal');
  if (!modal) return;

  modal.querySelector('#delete-item-title').textContent = title;

  const form = modal.querySelector('#delete-form');
  form.querySelector('#delete-item-id').value = id;
  const actionInput = form.querySelector('#delete-action-type');
  actionInput.name = `delete_${type}`;

  openModal('delete-modal');
}

function confirmDelete() {
    const form = document.getElementById('delete-form');
    if(form) {
        form.submit();
    }
}

function previewImage(input, previewId) {
    const previewContainer = document.getElementById(previewId);
    if (!previewContainer) return;

    const previewImg = previewContainer.querySelector('img');
    const file = input.files[0];

    if (file && previewImg) {
        const reader = new FileReader();
        reader.onload = e => {
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);
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

// FUNGSI EDIT PROFILE - DIPERBAIKI
function openEditProfileModal() {
    console.log('Opening edit profile modal'); // Debug log
    
    const modal = document.getElementById('edit-profile-modal');
    if (!modal) {
        console.error('Modal edit-profile-modal tidak ditemukan!');
        alert('Modal tidak ditemukan. Pastikan modal HTML sudah ditambahkan.');
        return;
    }
    
    // Cek apakah userProfileData tersedia
    if (typeof userProfileData === 'undefined') {
        console.warn('userProfileData tidak tersedia, data akan kosong');
        // Biarkan form kosong jika data tidak ada
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

// Fungsi untuk clear password fields saat modal dibuka
function clearPasswordFields() {
    const passwordFields = ['current_password', 'new_password', 'confirm_password'];
    passwordFields.forEach(fieldId => {
        const element = document.getElementById(fieldId);
        if (element) {
            element.value = '';
        }
    });
}

// ================================================================
// Inisialisasi dan event listener yang tidak dipanggil via onclick
// ================================================================
document.addEventListener("DOMContentLoaded", () => {
    console.log('Profile page DOM loaded');
    
    // Setup untuk tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.dataset.tab;

            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            const activeContent = document.getElementById(`${tabId}-tab`);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });
    
    // Setup edit profile form validation
    const editProfileForm = document.getElementById('edit-profile-form');
    if (editProfileForm) {
        console.log('Setting up edit profile form validation');
        
        editProfileForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password')?.value || '';
            const confirmPassword = document.getElementById('confirm_password')?.value || '';
            const currentPassword = document.getElementById('current_password')?.value || '';
            
            // Validasi password jika ada yang diisi
            if (currentPassword || newPassword || confirmPassword) {
                if (!currentPassword || !newPassword || !confirmPassword) {
                    e.preventDefault();
                    alert('Untuk mengubah password, semua field password harus diisi');
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Konfirmasi password baru tidak cocok');
                    return false;
                }
                
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('Password baru minimal 6 karakter');
                    return false;
                }
            }
            
            // Validasi email domain
            const emailField = document.getElementById('edit_email');
            if (emailField) {
                const email = emailField.value;
                const emailDomain = email.split('@')[1];
                if (emailDomain && emailDomain !== 'stis.ac.id' && emailDomain !== 'bps.go.id') {
                    e.preventDefault();
                    alert('Email harus berakhiran @stis.ac.id atau @bps.go.id');
                    return false;
                }
            }
        });
    } else {
        console.log('Edit profile form tidak ditemukan');
    }
    
    // Setup toggle password untuk semua password fields di modal edit profile
    const editModalToggleButtons = document.querySelectorAll('#edit-profile-modal .toggle-password');
    console.log('Found toggle password buttons:', editModalToggleButtons.length);
    
    editModalToggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const passwordInput = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (passwordInput && icon) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });
    });
    
    console.log('Profile page setup complete');
});