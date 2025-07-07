// Profile page functionality dengan validasi tanggal sederhana
document.addEventListener("DOMContentLoaded", () => {
  init()
})

async function init() {
  setupEventListeners()
  setupTabs()
  initAvatarUpload()
  setupDateValidations() // Tambah setup validasi tanggal
}

function setupEventListeners() {
  // Edit profile button
  const editProfileBtn = document.querySelector(".edit-profile-btn")
  if (editProfileBtn) {
    editProfileBtn.addEventListener("click", () => openModal('edit-profile-modal'))
  }

  // Modal controls
  const closeModalBtns = document.querySelectorAll(".close-modal")
  closeModalBtns.forEach((btn) => {
    btn.addEventListener("click", closeModals)
  })

  // Close modal when clicking outside
  window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal")) {
      closeModals()
    }
  })

  // Setup form validation
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!validateForm(this)) {
        e.preventDefault()
        return false
      }
    })
  })
}

function setupTabs() {
  const tabBtns = document.querySelectorAll(".tab-btn")
  const tabContents = document.querySelectorAll(".tab-content")

  tabBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const targetTab = btn.dataset.tab

      // Update active tab button
      tabBtns.forEach((b) => b.classList.remove("active"))
      btn.classList.add("active")

      // Update active tab content
      tabContents.forEach((content) => {
        content.classList.remove("active")
        if (content.id === `${targetTab}-tab`) {
          content.classList.add("active")
        }
      })
    })
  })
}

// ==========================================
// VALIDASI TANGGAL SEDERHANA
// ==========================================

function setupDateValidations() {
  // Set constraint untuk input tanggal kegiatan (min: hari ini)
  const activityDateInputs = document.querySelectorAll('input[name="event_date"], #edit_act_event_date')
  const today = new Date().toISOString().split('T')[0]
  
  activityDateInputs.forEach(input => {
    input.setAttribute('min', today)
    input.addEventListener('change', validateActivityDate)
  })

  // Set constraint untuk input tanggal lost & found (max: hari ini)
  const lostFoundDateInputs = document.querySelectorAll('input[name="date_occurred"], #edit_lf_date_occurred')
  
  lostFoundDateInputs.forEach(input => {
    input.setAttribute('max', today)
    input.addEventListener('change', validateLostFoundDate)
  })

  // Validasi waktu kegiatan
  const timeInputs = document.querySelectorAll('input[name="event_time"], #edit_act_event_time')
  timeInputs.forEach(input => {
    input.addEventListener('change', validateActivityTime)
  })
}

function validateActivityDate() {
  const selectedDate = new Date(this.value)
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  if (selectedDate < today) {
    showCustomNotification("Tanggal kegiatan tidak boleh di masa lalu!", "error")
    this.focus()
    return false
  }
  return true
}

function validateLostFoundDate() {
  const selectedDate = new Date(this.value)
  const today = new Date()
  today.setHours(23, 59, 59, 999)

  if (selectedDate > today) {
    showCustomNotification("Tanggal kejadian tidak boleh di masa depan!", "error")
    this.focus()
    return false
  }
  return true
}

function validateActivityTime() {
  const form = this.closest('form')
  const dateInput = form.querySelector('input[name="event_date"], #edit_act_event_date')
  
  if (!dateInput || !dateInput.value || !this.value) {
    return true
  }

  const selectedDateTime = new Date(dateInput.value + "T" + this.value)
  const now = new Date()

  if (selectedDateTime < now) {
    showCustomNotification("Waktu kegiatan tidak boleh di masa lalu!", "error")
    this.focus()
    return false
  }
  return true
}

function validateForm(form) {
  let isValid = true

  // Validasi field required
  const requiredFields = form.querySelectorAll("[required]")
  requiredFields.forEach((field) => {
    if (!field.value.trim()) {
      showCustomNotification("Semua field wajib harus diisi!", "error")
      field.focus()
      isValid = false
      return false
    }
  })

  if (!isValid) return false

  // Validasi tanggal kegiatan
  const eventDate = form.querySelector('input[name="event_date"], #edit_act_event_date')
  if (eventDate && eventDate.value) {
    const selectedDate = new Date(eventDate.value)
    const today = new Date()
    today.setHours(0, 0, 0, 0)

    if (selectedDate < today) {
      showCustomNotification("Tanggal kegiatan tidak boleh di masa lalu!", "error")
      eventDate.focus()
      return false
    }
  }

  // Validasi tanggal lost & found
  const lostFoundDate = form.querySelector('input[name="date_occurred"], #edit_lf_date_occurred')
  if (lostFoundDate && lostFoundDate.value) {
    const selectedDate = new Date(lostFoundDate.value)
    const today = new Date()
    today.setHours(23, 59, 59, 999)

    if (selectedDate > today) {
      showCustomNotification("Tanggal kejadian tidak boleh di masa depan!", "error")
      lostFoundDate.focus()
      return false
    }
  }

  // Validasi waktu kegiatan
  const eventTime = form.querySelector('input[name="event_time"], #edit_act_event_time')
  if (eventTime && eventTime.value && eventDate && eventDate.value) {
    const selectedDateTime = new Date(eventDate.value + "T" + eventTime.value)
    const now = new Date()

    if (selectedDateTime < now) {
      showCustomNotification("Waktu kegiatan tidak boleh di masa lalu!", "error")
      eventTime.focus()
      return false
    }
  }

  return true
}

// ==========================================
// AVATAR UPLOAD FUNCTIONALITY
// ==========================================

function initAvatarUpload() {
  const changeAvatarBtn = document.querySelector('.change-avatar-btn')
  if (changeAvatarBtn) {
    changeAvatarBtn.addEventListener('click', () => openModal('avatar-modal'))
  }
  
  initAvatarDragDrop()
}

function initAvatarDragDrop() {
  const dropZone = document.getElementById('avatar-drop-zone')
  const fileInput = document.getElementById('avatar-input')
  
  if (!dropZone || !fileInput) return
  
  ;['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false)
    document.body.addEventListener(eventName, preventDefaults, false)
  })

  ;['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false)
  })

  ;['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false)
  })

  dropZone.addEventListener('drop', handleDrop, false)
  dropZone.addEventListener('click', () => fileInput.click())
  fileInput.addEventListener('change', handleFileSelect)
}

function preventDefaults(e) {
  e.preventDefault()
  e.stopPropagation()
}

function highlight(e) {
  e.currentTarget.classList.add('drag-over')
}

function unhighlight(e) {
  e.currentTarget.classList.remove('drag-over')
}

function handleDrop(e) {
  const files = e.dataTransfer.files
  if (files.length > 0) {
    handleAvatarFile(files[0])
  }
}

function handleFileSelect(e) {
  const files = e.target.files
  if (files.length > 0) {
    handleAvatarFile(files[0])
  }
}

function handleAvatarFile(file) {
  if (!validateAvatarFile(file)) return
  
  previewAvatar(file)
}

function validateAvatarFile(file) {
  const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']
  const maxSize = 5 * 1024 * 1024 // 5MB
  
  if (!validTypes.includes(file.type)) {
    showCustomNotification('Hanya file gambar (JPG, PNG, GIF) yang diperbolehkan!', 'error')
    return false
  }
  
  if (file.size > maxSize) {
    showCustomNotification('Ukuran file terlalu besar! Maksimal 5MB.', 'error')
    return false
  }
  
  return true
}

function previewAvatar(file) {
  const reader = new FileReader()
  const preview = document.getElementById('avatar-preview')
  const uploadBtn = document.getElementById('upload-avatar-btn')
  
  reader.onload = (e) => {
    if (preview) {
      preview.src = e.target.result
      preview.style.display = 'block'
    }
    if (uploadBtn) {
      uploadBtn.style.display = 'block'
    }
  }
  
  reader.readAsDataURL(file)
}

// ==========================================
// EDIT ITEM FUNCTIONS
// ==========================================

function editItem(id, type) {
  const itemCard = document.querySelector(`[data-id="${id}"]`)
  if (!itemCard) {
    console.error('Item card tidak ditemukan!')
    return
  }

  const dataScript = itemCard.querySelector('.item-data')
  if (!dataScript) {
    console.error('Data item tidak ditemukan!')
    return
  }

  const itemData = JSON.parse(dataScript.textContent)
  const modalId = `edit-${type}-modal`
  const modal = document.getElementById(modalId)

  if (!modal) {
    console.error(`Modal dengan ID ${modalId} tidak ditemukan!`)
    return
  }

  // Isi form dengan data yang ada
  modal.querySelector('input[name="item_id"]').value = itemData.id

  if (type === 'lost-found') {
    modal.querySelector('#edit_lf_type').value = itemData.type
    modal.querySelector('#edit_lf_title').value = itemData.title
    modal.querySelector('#edit_lf_category_id').value = itemData.category_id
    modal.querySelector('#edit_lf_description').value = itemData.description
    modal.querySelector('#edit_lf_location').value = itemData.location
    modal.querySelector('#edit_lf_date_occurred').value = itemData.date_occurred
    
    // Set max date untuk lost & found
    const today = new Date().toISOString().split('T')[0]
    modal.querySelector('#edit_lf_date_occurred').setAttribute('max', today)
    
    const currentImageContainer = modal.querySelector('#edit-lf-current-image')
    const currentImage = modal.querySelector('#edit-lf-current-img')
    if(itemData.image && currentImage) {
      currentImage.src = itemData.image
      currentImageContainer.style.display = 'block'
    } else if (currentImageContainer) {
      currentImageContainer.style.display = 'none'
    }
  } else if (type === 'activity') {
    modal.querySelector('#edit_act_title').value = itemData.title
    modal.querySelector('#edit_act_category_id').value = itemData.category_id
    modal.querySelector('#edit_act_description').value = itemData.description
    modal.querySelector('#edit_act_event_date').value = itemData.event_date
    modal.querySelector('#edit_act_event_time').value = itemData.event_time
    modal.querySelector('#edit_act_location').value = itemData.location
    modal.querySelector('#edit_act_organizer').value = itemData.organizer
    
    // Set min date untuk activities
    const today = new Date().toISOString().split('T')[0]
    modal.querySelector('#edit_act_event_date').setAttribute('min', today)
    
    const currentImageContainer = modal.querySelector('#edit-act-current-image')
    const currentImage = modal.querySelector('#edit-act-current-img')
    if(itemData.image && currentImage) {
      currentImage.src = itemData.image
      currentImageContainer.style.display = 'block'
    } else if (currentImageContainer) {
      currentImageContainer.style.display = 'none'
    }
  }

  openModal(modalId)
}

function deleteItem(id, type, title) {
  const modal = document.getElementById('delete-modal')
  if (!modal) {
    console.error('Delete modal tidak ditemukan!')
    showCustomNotification('Modal delete tidak ditemukan!', 'error')
    return
  }

  const titleElement = modal.querySelector('#delete-item-title')
  if (titleElement) {
    titleElement.textContent = title || 'Item'
  }

  const form = modal.querySelector('#delete-form')
  if (!form) {
    console.error('Delete form tidak ditemukan!')
    showCustomNotification('Form delete tidak ditemukan!', 'error')
    return
  }

  form.querySelector('input[name="item_id"]').value = id
  form.querySelector('input[name="item_type"]').value = type

  openModal('delete-modal')
}

// ==========================================
// MODAL FUNCTIONS
// ==========================================

function openModal(modalId) {
  closeModals() // Close any open modals first
  
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.classList.add("active")
    document.body.style.overflow = 'hidden'

    // Focus ke form pertama jika ada
    const firstInput = modal.querySelector("input:not([type='hidden']), select, textarea")
    if (firstInput) {
      setTimeout(() => firstInput.focus(), 100)
    }
  }
}

function closeModals() {
  const activeModals = document.querySelectorAll(".modal.active")
  activeModals.forEach((modal) => {
    modal.classList.remove("active")
    
    // Reset form jika ada
    const form = modal.querySelector("form")
    if (form) {
      form.reset()

      // Hide image preview
      const imagePreview = form.querySelector("#image-preview")
      if (imagePreview) {
        imagePreview.style.display = "none"
      }
    }
  })
  
  document.body.style.overflow = ''
}

// ==========================================
// IMAGE PREVIEW FUNCTIONS
// ==========================================

window.previewImage = (input) => {
  const preview = document.getElementById("image-preview")
  const previewImg = document.getElementById("preview-img")

  if (input.files && input.files[0]) {
    const file = input.files[0]

    // Validate file type
    const validTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif"]
    if (!validTypes.includes(file.type)) {
      showCustomNotification("Hanya file gambar (JPG, PNG, GIF) yang diperbolehkan!", "error")
      input.value = ""
      return
    }

    // Validate file size (max 5MB)
    const maxSize = 5 * 1024 * 1024 // 5MB
    if (file.size > maxSize) {
      showCustomNotification("Ukuran file terlalu besar! Maksimal 5MB.", "error")
      input.value = ""
      return
    }

    const reader = new FileReader()
    reader.onload = (e) => {
      if (previewImg) {
        previewImg.src = e.target.result
      }
      if (preview) {
        preview.style.display = "block"
      }
    }
    reader.readAsDataURL(file)
  }
}

window.removeImage = () => {
  const input = document.getElementById("image")
  const preview = document.getElementById("image-preview")

  if (input) input.value = ""
  if (preview) preview.style.display = "none"
}

// ==========================================
// NOTIFICATION FUNCTION
// ==========================================

function showCustomNotification(message, type = 'info') {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll('.custom-notification')
  existingNotifications.forEach(n => n.remove())

  // Create notification element
  const notification = document.createElement('div')
  notification.className = `custom-notification notification-${type}`
  
  // Set icon based on type
  let icon = ''
  switch(type) {
    case 'success': icon = '✅'; break
    case 'error': icon = '❌'; break
    case 'warning': icon = '⚠️'; break
    default: icon = 'ℹ️'
  }
  
  notification.innerHTML = `
    <div style="
      position: fixed;
      top: 20px;
      right: 20px;
      background: ${type === 'error' ? '#e74c3c' : type === 'success' ? '#2ecc71' : '#3498db'};
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 10000;
      max-width: 400px;
      font-weight: 500;
    ">
      ${icon} ${message}
    </div>
  `

  // Add to page
  document.body.appendChild(notification)

  // Auto hide after 4 seconds
  setTimeout(() => {
    notification.remove()
  }, 4000)
}

// Global function exports
window.editItem = editItem
window.deleteItem = deleteItem
window.openModal = openModal
window.closeModal = (modalId) => {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.classList.remove("active")
    document.body.style.overflow = ''
  }
}