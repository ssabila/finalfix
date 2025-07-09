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
    editProfileBtn.addEventListener("click", () => openEditProfileModal());
  }

  // Modal controls
  const closeModalBtns = document.querySelectorAll(".close-modal");
  closeModalBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const modal = btn.closest('.modal');
      if (modal) closeModal(modal.id);
    });
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
  
  // Inisialisasi event listener untuk toggle password di modal edit profil
  document.querySelectorAll('#edit-profile-modal .toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const passwordInput = this.parentElement.querySelector('input');
        const icon = this.querySelector('i');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
  });
}

function setupTabs() {
  const tabBtns = document.querySelectorAll(".tab-btn");
  const tabContents = document.querySelectorAll(".tab-content");

  const urlParams = new URLSearchParams(window.location.search);
  const activeTabFromUrl = urlParams.get('tab');

  if (activeTabFromUrl) {
    tabBtns.forEach((b) => b.classList.remove("active"));
    tabContents.forEach((content) => content.classList.remove("active"));

    const targetBtn = document.querySelector(`[data-tab="${activeTabFromUrl}"]`);
    const targetContent = document.getElementById(`${activeTabFromUrl}-tab`);

    if (targetBtn && targetContent) {
      targetBtn.classList.add("active");
      targetContent.classList.add("active");
    }
  }

  tabBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const targetTab = btn.dataset.tab;
      tabBtns.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      tabContents.forEach((content) => {
        content.classList.remove("active");
        if (content.id === `${targetTab}-tab`) {
          content.classList.add("active");
        }
      });
    });
  });
}

function editItem(id, type) {
  const itemCard = document.querySelector(`[data-id="${id}"][data-type="${type}"]`);
  if (!itemCard) {
    console.error('Item card tidak ditemukan!');
    showCustomNotification('Item tidak ditemukan!', 'error');
    return;
  }
  const dataScript = itemCard.querySelector('.item-edit-data');
  if (!dataScript) {
    console.error('Data script item tidak ditemukan!');
    showCustomNotification('Data item tidak ditemukan!', 'error');
    return;
  }
  try {
    const itemData = JSON.parse(dataScript.textContent);
    const modalId = `edit-${type}-modal`;
    const modal = document.getElementById(modalId);
    if (!modal) {
      console.error(`Modal dengan ID ${modalId} tidak ditemukan!`);
      showCustomNotification('Modal edit tidak ditemukan!', 'error');
      return;
    }
    const form = modal.querySelector('form');
    if (form) {
      form.action = 'profile.php';
      form.method = 'POST';
    }
    const itemIdInput = modal.querySelector('input[name="item_id"]');
    const titleInput = modal.querySelector('input[name="title"]');
    const categorySelect = modal.querySelector('select[name="category_id"]');
    const descriptionTextarea = modal.querySelector('textarea[name="description"]');
    const locationInput = modal.querySelector('input[name="location"]');
    if (itemIdInput) itemIdInput.value = itemData.id;
    if (titleInput) titleInput.value = itemData.title;
    if (categorySelect) categorySelect.value = itemData.category_id;
    if (descriptionTextarea) descriptionTextarea.value = itemData.description;
    if (locationInput) locationInput.value = itemData.location;
    if (type === 'lost-found') {
      const typeSelect = modal.querySelector('select[name="type"]');
      const dateOccurredInput = modal.querySelector('input[name="date_occurred"]');
      if (typeSelect) typeSelect.value = itemData.type;
      if (dateOccurredInput) dateOccurredInput.value = itemData.date_occurred;
      const currentImageContainer = modal.querySelector('#edit-lf-current-image');
      const currentImage = modal.querySelector('#edit-lf-current-img');
      if (itemData.image && currentImage && currentImageContainer) {
        currentImage.src = itemData.image;
        currentImageContainer.style.display = 'block';
      } else if (currentImageContainer) {
        currentImageContainer.style.display = 'none';
      }
    } else if (type === 'activity') {
      const eventDateInput = modal.querySelector('input[name="event_date"]');
      const eventTimeInput = modal.querySelector('input[name="event_time"]');
      const organizerInput = modal.querySelector('input[name="organizer"]');
      if (eventDateInput) eventDateInput.value = itemData.event_date;
      if (eventTimeInput) eventTimeInput.value = itemData.event_time;
      if (organizerInput) organizerInput.value = itemData.organizer;
      const currentImageContainer = modal.querySelector('#edit-act-current-image');
      const currentImage = modal.querySelector('#edit-act-current-img');
      if (itemData.image && currentImage && currentImageContainer) {
        currentImage.src = itemData.image;
        currentImageContainer.style.display = 'block';
      } else if (currentImageContainer) {
        currentImageContainer.style.display = 'none';
      }
    }
    openModal(modalId);
  } catch (error) {
    console.error('Error parsing item data:', error);
    showCustomNotification('Gagal memuat data item!', 'error');
  }
}

function deleteItem(id, type, title) {
  const modal = document.getElementById('delete-modal');
  if (!modal) {
    console.error('Delete modal tidak ditemukan!');
    showCustomNotification('Modal konfirmasi tidak ditemukan!', 'error');
    return;
  }
  const titleElement = modal.querySelector('#delete-item-title');
  if (titleElement) {
    titleElement.textContent = title || 'item ini';
  }
  const itemIdInput = modal.querySelector('#delete-item-id');
  if (itemIdInput) {
    itemIdInput.value = id;
  }
  const form = modal.querySelector('#delete-form');
  if (form) {
    const existingActionInputs = form.querySelectorAll('input[name^="delete_"]');
    existingActionInputs.forEach(input => input.remove());
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.value = '1';
    if (type === 'lost-found') {
      actionInput.name = 'delete_lost_found';
    } else if (type === 'activity') {
      actionInput.name = 'delete_activity';
    }
    form.appendChild(actionInput);
  }
  openModal('delete-modal');
}

function confirmDelete() {
  const form = document.getElementById('delete-form');
  if (form) {
    const deleteButton = document.querySelector('#delete-modal .btn-danger');
    if (deleteButton) {
      const originalText = deleteButton.innerHTML;
      deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
      deleteButton.disabled = true;
      setTimeout(() => {
        deleteButton.innerHTML = originalText;
        deleteButton.disabled = false;
      }, 5000);
    }
    form.submit();
  } else {
    console.error('Delete form not found!');
    showCustomNotification('Terjadi kesalahan, form tidak ditemukan.', 'error');
  }
}

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add("active");
    document.body.style.overflow = 'hidden';
    if (modalId === 'avatar-modal') {
      resetAvatarForm();
    }
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove("active");
    const form = modal.querySelector("form");
    if (form) {
      form.reset();
      form.querySelectorAll(".image-preview").forEach(p => p.style.display = "none");
      form.querySelectorAll(".current-image").forEach(c => c.style.display = "none");
    }
  }
  if (document.querySelectorAll(".modal.active").length === 0) {
    document.body.style.overflow = '';
  }
}

function validateForm(form) {
  const requiredFields = form.querySelectorAll("[required]");
  let isValid = true;

  requiredFields.forEach((field) => {
    if (!field.value.trim()) {
      showFieldError(field, "Field ini wajib diisi");
      isValid = false;
    } else {
      clearFieldError(field);
    }
  });

  const eventDate = form.querySelector('input[name="event_date"]');
  if (eventDate && eventDate.value) {
    const selectedDate = new Date(eventDate.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (selectedDate < today) {
      showCustomNotification("Tanggal kegiatan tidak boleh di masa lalu!", "warning");
      eventDate.focus();
      isValid = false;
    }
  }

  const dateOccurred = form.querySelector('input[name="date_occurred"]');
  if (dateOccurred && dateOccurred.value) {
    const selectedDate = new Date(dateOccurred.value);
    const today = new Date();
    today.setHours(23, 59, 59, 999);
    if (selectedDate > today) {
      showCustomNotification("Tanggal kejadian tidak boleh di masa depan!", "warning");
      dateOccurred.focus();
      isValid = false;
    }
  }

  return isValid;
}

function showFieldError(field, message) {
  clearFieldError(field);
  field.classList.add('error');
  const errorDiv = document.createElement('div');
  errorDiv.className = 'field-error';
  errorDiv.textContent = message;
  if (field.parentNode.classList.contains('input-group')) {
    field.parentNode.parentNode.appendChild(errorDiv);
  } else {
    field.parentNode.appendChild(errorDiv);
  }
}

function clearFieldError(field) {
  field.classList.remove('error');
  const existingError = field.parentNode.querySelector('.field-error') ||
    field.parentNode.parentNode.querySelector('.field-error');
  if (existingError) {
    existingError.remove();
  }
}

function setupDateValidations() {
  const today = new Date().toISOString().split('T')[0];
  const activityDateInputs = document.querySelectorAll('input[name="event_date"]');
  activityDateInputs.forEach(input => {
    input.setAttribute('min', today);
    input.addEventListener('change', validateActivityDate);
  });
  const lostFoundDateInputs = document.querySelectorAll('input[name="date_occurred"]');
  lostFoundDateInputs.forEach(input => {
    input.setAttribute('max', today);
    input.addEventListener('change', validateLostFoundDate);
  });
}

function validateActivityDate() {
  const selectedDate = new Date(this.value);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  if (selectedDate < today) {
    showCustomNotification("Tanggal kegiatan tidak boleh di masa lalu!", "warning");
    this.value = '';
    this.focus();
  }
}

function validateLostFoundDate() {
  const selectedDate = new Date(this.value);
  const today = new Date();
  today.setHours(23, 59, 59, 999);
  if (selectedDate > today) {
    showCustomNotification("Tanggal kejadian tidak boleh di masa depan!", "warning");
    this.value = '';
    this.focus();
  }
}

function previewImage(input, previewContainerId) {
  const preview = document.getElementById(previewContainerId);
  if (!preview) return;
  const previewImg = preview.querySelector("img");
  if (!previewImg) return;
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const validTypes = ["image/jpeg", "image/png", "image/gif"];
    const maxSize = 5 * 1024 * 1024;
    if (!validTypes.includes(file.type)) {
      showCustomNotification("Hanya file gambar (JPG, PNG, GIF) yang diperbolehkan!", "error");
      input.value = "";
      return;
    }
    if (file.size > maxSize) {
      showCustomNotification("Ukuran file terlalu besar! Maksimal 5MB.", "error");
      input.value = "";
      return;
    }
    const reader = new FileReader();
    reader.onload = function (e) {
      previewImg.src = e.target.result;
      preview.style.display = "block";
    };
    reader.readAsDataURL(file);
  } else {
    preview.style.display = "none";
  }
}

function removeImage(inputId, previewId) {
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewId);
  if (input) input.value = "";
  if (preview) preview.style.display = "none";
}

function showCustomNotification(message, type = 'info') {
  const existingNotifications = document.querySelectorAll('.custom-notification');
  existingNotifications.forEach(notification => notification.remove());
  const notification = document.createElement('div');
  notification.className = `custom-notification notification-${type}`;
  notification.innerHTML = `
    <div class="notification-content">
      <span class="notification-message">${message}</span>
      <button class="notification-close">&times;</button>
    </div>
  `;
  document.body.appendChild(notification);
  notification.querySelector('.notification-close').addEventListener('click', () => {
    notification.remove();
  });
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, 5000);
  setTimeout(() => {
    notification.classList.add('show');
  }, 100);
}

function initAvatarUpload() {
  const changeAvatarBtn = document.querySelector('.change-avatar-btn');
  if (changeAvatarBtn) {
    changeAvatarBtn.addEventListener('click', () => openAvatarModal());
  }
}

function resetAvatarForm() {
  const form = document.querySelector('#avatar-modal form');
  if (form) {
    form.reset();
    const newAvatarPreview = document.getElementById('new-avatar-preview');
    const noPreviewPlaceholder = document.getElementById('no-preview-placeholder');
    const saveBtn = document.getElementById('save-avatar-btn');

    if (newAvatarPreview) newAvatarPreview.style.display = 'none';
    if (noPreviewPlaceholder) noPreviewPlaceholder.style.display = 'block';
    if (saveBtn) saveBtn.disabled = true;
  }
}

/**
 * Menangani pratinjau gambar untuk avatar di modal.
 * @param {HTMLInputElement} input - Elemen input file.
 */
function previewAvatar(input) {
    const newAvatarPreview = document.getElementById('new-avatar-preview');
    const noPreviewPlaceholder = document.getElementById('no-preview-placeholder');
    const newAvatarImg = document.getElementById('new-avatar-img');
    const saveBtn = document.getElementById('save-avatar-btn');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();

        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            showCustomNotification('Format file tidak valid. Gunakan JPG, PNG, atau GIF.', 'error');
            input.value = '';
            return;
        }

        if (file.size > 5 * 1024 * 1024) { // 5MB
            showCustomNotification('Ukuran file terlalu besar! Maksimal 5MB.', 'error');
            input.value = '';
            return;
        }

        reader.onload = function(e) {
            if (newAvatarImg) newAvatarImg.src = e.target.result;
            if (newAvatarPreview) newAvatarPreview.style.display = 'block';
            if (noPreviewPlaceholder) noPreviewPlaceholder.style.display = 'none';
            if (saveBtn) saveBtn.disabled = false;
        };

        reader.readAsDataURL(file);
    } else {
        if (newAvatarPreview) newAvatarPreview.style.display = 'none';
        if (noPreviewPlaceholder) noPreviewPlaceholder.style.display = 'block';
        if (saveBtn) saveBtn.disabled = true;
    }
}

/**
 * Membuka modal untuk mengganti avatar.
 */
function openAvatarModal() {
    openModal('avatar-modal');
}

/**
 * Membuka modal untuk mengedit profil dan mengisi data pengguna.
 */
function openEditProfileModal() {
    if (typeof userProfileData !== 'undefined') {
        document.getElementById('edit_first_name').value = userProfileData.first_name;
        document.getElementById('edit_last_name').value = userProfileData.last_name;
        document.getElementById('edit_nim').value = userProfileData.nim;
        document.getElementById('edit_email').value = userProfileData.email;
        document.getElementById('edit_phone').value = userProfileData.phone;
    }
    openModal('edit-profile-modal');
}