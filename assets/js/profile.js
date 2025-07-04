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

// ================================================================
// Inisialisasi dan event listener yang tidak dipanggil via onclick
// Boleh tetap di dalam DOMContentLoaded
// ================================================================
document.addEventListener("DOMContentLoaded", () => {
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
});