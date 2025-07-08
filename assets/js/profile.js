document.addEventListener("DOMContentLoaded", () => {
  init();
});

async function init() {
  setupEventListeners();
  setupTabs();
  initAvatarUpload();
  setupDateValidations();
}

function setupEventListeners() {
  // Edit profile button
  const editProfileBtn = document.querySelector(".edit-profile-btn");
  if (editProfileBtn) {
    editProfileBtn.addEventListener("click", () => openModal('edit-profile-modal'));
  }

  // Modal controls
  const closeModalBtns = document.querySelectorAll(".close-modal");
  closeModalBtns.forEach((btn) => {
    btn.addEventListener("click", () => closeModal(btn.closest('.modal').id));
  });

  // Close modal when clicking outside
  window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal")) {
      closeModal(e.target.id);
    }
  });

  // Setup form validation
  const forms = document.querySelectorAll("form");
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!validateForm(this)) {
        e.preventDefault();
        return false;
      }
    });
  });
}

function setupTabs() {
  const tabBtns = document.querySelectorAll(".tab-btn");
  const tabContents = document.querySelectorAll(".tab-content");

  tabBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const targetTab = btn.dataset.tab;

      // Update active tab button
      tabBtns.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      // Update active tab content
      tabContents.forEach((content) => {
        content.classList.remove("active");
        if (content.id === `${targetTab}-tab`) {
          content.classList.add("active");
        }
      });
    });
  });
}

function setupDateValidations() {
  const today = new Date().toISOString().split('T')[0];

  // Set constraint for activity date inputs (min: today)
  const activityDateInputs = document.querySelectorAll('input[name="event_date"], #edit_act_event_date');
  activityDateInputs.forEach(input => {
    input.setAttribute('min', today);
    input.addEventListener('change', validateActivityDate);
  });

  // Set constraint for lost & found date inputs (max: today)
  const lostFoundDateInputs = document.querySelectorAll('input[name="date_occurred"], #edit_lf_date_occurred');
  lostFoundDateInputs.forEach(input => {
    input.setAttribute('max', today);
    input.addEventListener('change', validateLostFoundDate);
  });

  // Validate activity time
  const timeInputs = document.querySelectorAll('input[name="event_time"], #edit_act_event_time');
  timeInputs.forEach(input => {
    input.addEventListener('change', validateActivityTime);
  });
}

function validateActivityDate() {
  const selectedDate = new Date(this.value);
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (selectedDate < today) {
    showCustomNotification("Tanggal kegiatan tidak boleh di masa lalu!", "error");
    this.value = ''; // Clear invalid value
    this.focus();
    return false;
  }
  return true;
}

function validateLostFoundDate() {
  const selectedDate = new Date(this.value);
  const today = new Date();
  today.setHours(23, 59, 59, 999);

  if (selectedDate > today) {
    showCustomNotification("Tanggal kejadian tidak boleh di masa depan!", "error");
    this.value = ''; // Clear invalid value
    this.focus();
    return false;
  }
  return true;
}

function validateActivityTime() {
  const form = this.closest('form');
  const dateInput = form.querySelector('input[name="event_date"], #edit_act_event_date');
  
  if (!dateInput || !dateInput.value || !this.value) {
    return true; // Don't validate if date is not set
  }

  const selectedDateTime = new Date(`${dateInput.value}T${this.value}`);
  const now = new Date();

  if (selectedDateTime < now) {
    showCustomNotification("Waktu kegiatan tidak boleh di masa lalu!", "error");
    this.focus();
    return false;
  }
  return true;
}

function validateForm(form) {
    for (const field of form.querySelectorAll("[required]")) {
        if (!field.value.trim()) {
            showCustomNotification("Semua kolom wajib diisi!", "error");
            field.focus();
            return false;
        }
    }
    // Add any other form-wide validations if needed
    return true;
}


function initAvatarUpload() {
  const changeAvatarBtn = document.querySelector('.change-avatar-btn');
  if (changeAvatarBtn) {
    changeAvatarBtn.addEventListener('click', () => openModal('avatar-modal'));
  }
}

function previewAvatar(input) {
    const newAvatarPreview = document.getElementById('new-avatar-preview');
    const newAvatarImg = document.getElementById('new-avatar-img');
    const noPreviewPlaceholder = document.getElementById('no-preview-placeholder');
    const saveBtn = document.getElementById('save-avatar-btn');

    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Validate file
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (!validTypes.includes(file.type)) {
            showCustomNotification('Format file tidak valid. Gunakan JPG, PNG, atau GIF.', 'error');
            input.value = '';
            return;
        }
        if (file.size > maxSize) {
            showCustomNotification('Ukuran file terlalu besar. Maksimal 5MB.', 'error');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            newAvatarImg.src = e.target.result;
            newAvatarPreview.style.display = 'flex';
            noPreviewPlaceholder.style.display = 'none';
            saveBtn.disabled = false;
        };
        reader.readAsDataURL(file);
    }
}


function editItem(id, type) {
  const itemCard = document.querySelector(`.profile-item[data-id="${id}"][data-type="${type}"]`);
  if (!itemCard) return console.error('Item card tidak ditemukan!');

  const dataScript = itemCard.querySelector('.item-edit-data'); 
  if (!dataScript) return console.error('Data script item tidak ditemukan!');

  const itemData = JSON.parse(dataScript.textContent);
  const modalId = `edit-${type}-modal`;
  const modal = document.getElementById(modalId);
  if (!modal) return console.error(`Modal dengan ID ${modalId} tidak ditemukan!`);

  // Populate common fields
  modal.querySelector('input[name="item_id"]').value = itemData.id;
  modal.querySelector('input[name="title"]').value = itemData.title;
  modal.querySelector('select[name="category_id"]').value = itemData.category_id;
  modal.querySelector('textarea[name="description"]').value = itemData.description;
  modal.querySelector('input[name="location"]').value = itemData.location;

  // Populate specific fields
  if (type === 'lost-found') {
    modal.querySelector('select[name="type"]').value = itemData.type;
    modal.querySelector('input[name="date_occurred"]').value = itemData.date_occurred;
    
    const currentImageContainer = modal.querySelector('#edit-lf-current-image');
    const currentImage = modal.querySelector('#edit-lf-current-img');
    if(itemData.image && currentImage) {
      currentImage.src = itemData.image;
      currentImageContainer.style.display = 'block';
    } else if (currentImageContainer) {
      currentImageContainer.style.display = 'none';
    }
  } else if (type === 'activity') {
    modal.querySelector('input[name="event_date"]').value = itemData.event_date;
    modal.querySelector('input[name="event_time"]').value = itemData.event_time;
    modal.querySelector('input[name="organizer"]').value = itemData.organizer;
    
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
  if (!modal) return console.error('Delete modal tidak ditemukan!');

  modal.querySelector('#delete-item-title').textContent = title || 'item ini';
  modal.querySelector('#delete-item-id').value = id;
  
  // Set the action type in the form
  const form = modal.querySelector('#delete-form');
  let actionInput = form.querySelector('input[name="action_type"]');
  if (!actionInput) {
      actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action_type';
      form.appendChild(actionInput);
  }
  
  if (type === 'lost-found') {
      actionInput.value = 'delete_lost_found';
  } else if (type === 'activity') {
      actionInput.value = 'delete_activity';
  }

  openModal('delete-modal');
}

function confirmDelete() {
    const form = document.getElementById('delete-form');
    if (form) {
        form.submit();
    } else {
        console.error('Delete form not found!');
        // Assumes showCustomNotification is available globally
        showCustomNotification('Terjadi kesalahan, form tidak ditemukan.', 'error');
    }
}

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add("active");
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove("active");
        
        const form = modal.querySelector("form");
        if (form) {
            form.reset();
            // Reset any previews
            form.querySelectorAll(".image-preview").forEach(p => p.style.display = "none");
            form.querySelectorAll(".current-image").forEach(c => c.style.display = "none");
            if (form.querySelector('#save-avatar-btn')) {
                form.querySelector('#save-avatar-btn').disabled = true;
            }
        }
    }
    
    // Check if any other modal is active before re-enabling scroll
    if (document.querySelectorAll(".modal.active").length === 0) {
        document.body.style.overflow = '';
    }
}

function closeModals() {
    document.querySelectorAll(".modal.active").forEach(modal => closeModal(modal.id));
}

function previewImage(input, previewContainerId) {
  const preview = document.getElementById(previewContainerId);
  if (!preview) return;
  const previewImg = preview.querySelector("img");

  if (input.files && input.files[0]) {
    const file = input.files[0];
    const validTypes = ["image/jpeg", "image/png", "image/gif"];
    const maxSize = 5 * 1024 * 1024; // 5MB

    if (!validTypes.includes(file.type) || file.size > maxSize) {
      showCustomNotification("File tidak valid (hanya JPG/PNG/GIF, maks 5MB).", "error");
      input.value = "";
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
      if (previewImg) previewImg.src = e.target.result;
      preview.style.display = "block";
    };
    reader.readAsDataURL(file);
  }
}

function removeImage(inputId, previewContainerId) {
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewContainerId);
  if (input) input.value = "";
  if (preview) preview.style.display = "none";
}

window.editItem = editItem;
window.deleteItem = deleteItem;
window.confirmDelete = confirmDelete;
window.openModal = openModal;
window.closeModal = closeModal;
window.previewImage = previewImage;
window.removeImage = removeImage;
window.previewAvatar = previewAvatar;