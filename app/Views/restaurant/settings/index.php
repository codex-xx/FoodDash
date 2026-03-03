<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Store Settings — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12">
    <div>
      <h3 class="m-0">Store Settings</h3>
      <small class="text-muted">Manage your restaurant information</small>
    </div>
  </div>
</div>

<!-- Success/Error Messages -->
<div id="message-container"></div>

<div class="row">
  <div class="col-lg-8">
    <!-- Store Information Card -->
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h5 class="card-title mb-4"><i class="bi bi-shop"></i> Store Information</h5>
        <form id="settingsForm" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="restaurant_name" class="form-label">Restaurant Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="restaurant_name" name="name" value="<?= esc($restaurant['name']) ?>" required>
            <div class="form-text">This is how your restaurant will appear to customers</div>
          </div>

          <div class="mb-3">
            <label for="restaurant_address" class="form-label">Address</label>
            <textarea class="form-control" id="restaurant_address" name="address" rows="3"><?= esc($restaurant['address'] ?? '') ?></textarea>
            <div class="form-text">Your restaurant's physical address</div>
          </div>

          <div class="mb-4">
            <label for="restaurant_logo" class="form-label">Restaurant Logo</label>
            <div class="mb-3">
              <?php if (!empty($restaurant['logo'])): ?>
                <img src="<?= base_url('uploads/logos/' . $restaurant['logo']) ?>" alt="Current Logo" id="currentLogo" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
              <?php else: ?>
                <div id="noLogo" class="text-muted">
                  <i class="bi bi-image" style="font-size: 3rem;"></i>
                  <p class="mb-0">No logo uploaded</p>
                </div>
              <?php endif; ?>
            </div>
            <input type="file" class="form-control" id="restaurant_logo" name="logo" accept="image/*">
            <div class="form-text">Upload your restaurant logo (JPG, PNG, GIF - Max 2MB)</div>
            <div id="logoPreview" class="mt-2" style="display: none;">
              <img id="previewImage" src="#" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
            </div>
          </div>

          <hr class="my-4">

          <h5 class="mb-3"><i class="bi bi-clock"></i> Opening Hours</h5>
          <div class="mb-3">
            <textarea class="form-control" id="opening_hours" name="opening_hours" rows="7" placeholder="Example:&#10;Monday: 9:00 AM - 9:00 PM&#10;Tuesday: 9:00 AM - 9:00 PM&#10;Wednesday: 9:00 AM - 9:00 PM&#10;Thursday: 9:00 AM - 9:00 PM&#10;Friday: 9:00 AM - 10:00 PM&#10;Saturday: 10:00 AM - 10:00 PM&#10;Sunday: 10:00 AM - 8:00 PM"><?= esc($restaurant['opening_hours'] ?? '') ?></textarea>
            <div class="form-text">Enter your operating hours for each day of the week</div>
          </div>

          <hr class="my-4">

          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" onclick="resetForm()">
              <i class="bi bi-arrow-clockwise"></i> Reset
            </button>
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <i class="bi bi-check-circle"></i> Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- Status Card -->
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="bi bi-info-circle"></i> Status Information</h5>
        
        <div class="mb-3">
          <label class="text-muted mb-1">Approval Status</label>
          <div>
            <?php
              $statusClass = match ($restaurant['status']) {
                'approved' => 'success',
                'pending' => 'warning',
                'rejected' => 'danger',
                default => 'secondary'
              };
            ?>
            <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($restaurant['status']) ?></span>
          </div>
        </div>

        <div class="mb-3">
          <label class="text-muted mb-1">Account Status</label>
          <div>
            <span class="badge bg-<?= $restaurant['is_active'] ? 'success' : 'danger' ?>">
              <?= $restaurant['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </div>
          <small class="text-muted d-block mt-1">
            <?= $restaurant['is_active'] ? 'Accepting orders' : 'Not accepting orders' ?>
          </small>
        </div>
      </div>
    </div>

    <!-- Additional Information Card -->
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="bi bi-calendar"></i> Additional Info</h5>
        <div class="mb-3">
          <label class="text-muted mb-1">Restaurant ID</label>
          <p class="mb-0">#<?= $restaurant['id'] ?></p>
        </div>
        <div class="mb-3">
          <label class="text-muted mb-1">Member Since</label>
          <p class="mb-0"><?= date('F d, Y', strtotime($restaurant['created_at'])) ?></p>
        </div>
        <div class="mb-0">
          <label class="text-muted mb-1">Last Updated</label>
          <p class="mb-0"><?= date('F d, Y H:i', strtotime($restaurant['updated_at'])) ?></p>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  const originalData = {
    name: document.getElementById('restaurant_name').value,
    address: document.getElementById('restaurant_address').value,
    opening_hours: document.getElementById('opening_hours').value
  };

  // Logo preview
  document.getElementById('restaurant_logo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('previewImage').src = e.target.result;
        document.getElementById('logoPreview').style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });

  function resetForm() {
    document.getElementById('restaurant_name').value = originalData.name;
    document.getElementById('restaurant_address').value = originalData.address;
    document.getElementById('opening_hours').value = originalData.opening_hours;
    document.getElementById('restaurant_logo').value = '';
    document.getElementById('logoPreview').style.display = 'none';
    clearMessages();
  }

  function showMessage(message, type = 'success') {
    const container = document.getElementById('message-container');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill';
    
    container.innerHTML = `
      <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
        <i class="bi bi-${icon}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
      const alert = container.querySelector('.alert');
      if (alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      }
    }, 5000);
  }

  function clearMessages() {
    document.getElementById('message-container').innerHTML = '';
  }

  document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    clearMessages();

    const submitBtn = document.getElementById('submitBtn');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    const formData = new FormData(this);

    fetch('<?= site_url('settings/update') ?>', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showMessage(data.message, 'success');
        
        // Update original data
        originalData.name = formData.get('name');
        originalData.address = formData.get('address');
        originalData.opening_hours = formData.get('opening_hours');
        
        // Update logo preview if new logo was uploaded
        if (data.logo) {
          const currentLogo = document.getElementById('currentLogo');
          const noLogo = document.getElementById('noLogo');
          
          if (currentLogo) {
            currentLogo.src = data.logo;
          } else if (noLogo) {
            noLogo.outerHTML = '<img src="' + data.logo + '" alt="Current Logo" id="currentLogo" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">';
          }
          
          document.getElementById('logoPreview').style.display = 'none';
          document.getElementById('restaurant_logo').value = '';
        }
        
        // Scroll to top to show message
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        const errorMsg = typeof data.error === 'object' 
          ? Object.values(data.error).join(', ') 
          : data.error;
        showMessage(errorMsg, 'error');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    })
    .catch(error => {
      showMessage('An error occurred. Please try again.', 'error');
      console.error('Error:', error);
    })
    .finally(() => {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    });
  });
</script>
<?= $this->endSection() ?>
