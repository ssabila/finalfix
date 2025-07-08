// assets/js/profile.js - SIMPLE FIX VERSION
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
}

function setupTabs() {
  const tabBtns = document.querySelectorAll(".tab-btn");
  const tabContents = document.querySelectorAll(".tab-content");

  // Check URL parameter untuk set active tab setelah redirect
  const urlParams = new URLSearchParams(window.location.search);
  const activeTabFromUrl = urlParams.get('tab');
  
  // Set initial active tab
  if (activeTabFromUrl) {
    // Set tab berdasarkan URL parameter
    tabBtns.forEach((b) => b.classList.remove("active"));
    tabContents.forEach((content) => content.classList.remove("active"));
    
    const targetBtn = document.querySelector(`[data-tab="${activeTabFromUrl}"]`);
    const targetContent = document.getElementById(`${activeTabFromUrl}-tab`);
    
    if (targetBtn && targetContent) {
      targetBtn.classList.add("active");
      targetContent.classList.add("active");
    }
  }

  // Setup click handlers untuk tab buttons
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

// ===========================================
// FIXED EDIT ACTIVITY FUNCTION
// ===========================================
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

    // Populate common fields
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

    // Populate specific fields based on type
    if (type === 'lost-found') {
      const typeSelect = modal.querySelector('select[name="type"]');
      const dateOccurredInput = modal.querySelector('input[name="date_occurred"]');
      
      if (typeSelect) typeSelect.value = itemData.type;
      if (dateOccurredInput) dateOccurredInput.value = itemData.date_occurred;
      
      // Show current image if exists
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
      
      // Show current image if exists
      const currentImageContainer = modal.querySelector('#edit-act-current-image');
      const currentImage = modal.querySelector('#edit-act-current-img');
      if (itemData.image && currentImage && currentImageContainer) {
        currentImage.src = itemData.image;
        currentImageContainer.style.display = 'block';
      } else if (currentImageContainer) {
        currentImageContainer.style.display = 'none';
      }
    }

    // Open the modal
    openModal(modalId);
    
  } catch (error) {
    console.error('Error parsing item data:', error);
    showCustomNotification('Gagal memuat data item!', 'error');
  }
}

// ===========================================
// FIXED DELETE ACTIVITY FUNCTION
// ===========================================
function deleteItem(id, type, title) {
  const modal = document.getElementById('delete-modal');
  if (!modal) {
    console.error('Delete modal tidak ditemukan!');
    showCustomNotification('Modal konfirmasi tidak ditemukan!', 'error');
    return;
  }

  // Set the title in the confirmation dialog
  const titleElement = modal.querySelector('#delete-item-title');
  if (titleElement) {
    titleElement.textContent = title || 'item ini';
  }

  // Set the item ID
  const itemIdInput = modal.querySelector('#delete-item-id');
  if (itemIdInput) {
    itemIdInput.value = id;
  }

  // Set the correct hidden input based on type
  const form = modal.querySelector('#delete-form');
  if (form) {
    // Remove any existing action inputs
    const existingActionInputs = form.querySelectorAll('input[name^="delete_"]');
    existingActionInputs.forEach(input => input.remove());

    // Add the correct action input based on type
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

  // Open the delete confirmation modal
  openModal('delete-modal');
}

// ===========================================
// DELETE CONFIRMATION FUNCTION
// ===========================================
function confirmDelete() {
  const form = document.getElementById('delete-form');
  if (form) {
    // Show loading state on button
    const deleteButton = document.querySelector('.btn-danger');
    if (deleteButton) {
      const originalText = deleteButton.innerHTML;
      deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
      deleteButton.disabled = true;
      
      // Restore button after a delay (in case of error)
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

// ===========================================
// MODAL FUNCTIONS
// ===========================================
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add("active");
    document.body.style.overflow = 'hidden';
    
    // Reset avatar form when opening avatar modal
    if (modalId === 'avatar-modal') {
      resetAvatarForm();
    }
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove("active");
    
    // Reset form when closing
    const form = modal.querySelector("form");
    if (form) {
      form.reset();
      // Reset any previews
      form.querySelectorAll(".image-preview").forEach(p => p.style.display = "none");
      form.querySelectorAll(".current-image").forEach(c => c.style.display = "none");
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

// ===========================================
// VALIDATION FUNCTIONS
// ===========================================
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

  // Validate activity date (must be in future)
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

  // Validate lost & found date (must be in past or today)
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

// ===========================================
// DATE VALIDATION SETUP
// ===========================================
function setupDateValidations() {
  const today = new Date().toISOString().split('T')[0];

  // Set constraint for activity date inputs (min: today)
  const activityDateInputs = document.querySelectorAll('input[name="event_date"]');
  activityDateInputs.forEach(input => {
    input.setAttribute('min', today);
    input.addEventListener('change', validateActivityDate);
  });

  // Set constraint for lost & found date inputs (max: today)
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

// ===========================================
// IMAGE PREVIEW FUNCTIONS
// ===========================================
function previewImage(input, previewContainerId) {
  const preview = document.getElementById(previewContainerId);
  if (!preview) return;
  
  const previewImg = preview.querySelector("img");
  if (!previewImg) return;

  if (input.files && input.files[0]) {
    const file = input.files[0];
    const validTypes = ["image/jpeg", "image/png", "image/gif"];
    const maxSize = 5 * 1024 * 1024; // 5MB

    // Validate file type and size
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

    // Create preview
    const reader = new FileReader();
    reader.onload = function(e) {
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

// ===========================================
// NOTIFICATION FUNCTION
// ===========================================
function showCustomNotification(message, type = 'info') {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll('.custom-notification');
  existingNotifications.forEach(notification => notification.remove());

  // Create notification element
  const notification = document.createElement('div');
  notification.className = `custom-notification notification-${type}`;
  notification.innerHTML = `
    <div class="notification-content">
      <span class="notification-message">${message}</span>
      <button class="notification-close">&times;</button>
    </div>
  `;

  // Add to DOM
  document.body.appendChild(notification);

  // Add event listener for close button
  notification.querySelector('.notification-close').addEventListener('click', () => {
    notification.remove();
  });

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, 5000);

  // Show animation
  setTimeout(() => {
    notification.classList.add('show');
  }, 100);
}

// ===========================================
// AVATAR FUNCTIONALITY (SIMPLIFIED)
// ===========================================
function initAvatarUpload() {
  const changeAvatarBtn = document.querySelector('.change-avatar-btn');
  if (changeAvatarBtn) {
    changeAvatarBtn.addEventListener('click', () => openModal('avatar-modal'));
  }
}

function resetAvatarForm() {
  const form = document.querySelector('#avatar-modal form');
  if (form) {
    form.reset();
    const preview = form.querySelector('.image-preview');
    if (preview) preview.style.display = 'none';
  }
}