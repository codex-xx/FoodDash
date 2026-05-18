<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Store Settings — FoodDash'); ?>

<?= $this->section('head') ?>
<?= $this->endSection() ?>

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
          <div class="card border-0 bg-light mb-3">
            <div class="card-body py-3">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <label class="form-label fw-semibold mb-1" for="is_open_toggle">Store Open for Orders</label>
                  <div class="text-muted small">Quickly pause or resume incoming orders.</div>
                </div>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" role="switch" id="is_open_toggle" <?= !array_key_exists('is_open', $restaurant) || (int) $restaurant['is_open'] === 1 ? 'checked' : '' ?>>
                  <label class="form-check-label" id="is_open_label" for="is_open_toggle">
                    <?= !array_key_exists('is_open', $restaurant) || (int) $restaurant['is_open'] === 1 ? 'Open' : 'Closed' ?>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <input type="hidden" id="is_open" name="is_open" value="<?= !array_key_exists('is_open', $restaurant) || (int) $restaurant['is_open'] === 1 ? '1' : '0' ?>">

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
            <input type="hidden" id="opening_hours" name="opening_hours" value="<?= esc($restaurant['opening_hours'] ?? '') ?>">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 26%;">Day</th>
                    <th style="width: 28%;">Open</th>
                    <th style="width: 28%;">Close</th>
                    <th style="width: 18%;">Closed</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; ?>
                  <?php foreach ($days as $day): ?>
                    <?php $dayKey = strtolower($day); ?>
                    <tr>
                      <td><strong><?= esc($day) ?></strong></td>
                      <td>
                        <input type="time" class="form-control form-control-sm" id="open_<?= $dayKey ?>" data-day="<?= $day ?>" data-type="open" step="900">
                      </td>
                      <td>
                        <input type="time" class="form-control form-control-sm" id="close_<?= $dayKey ?>" data-day="<?= $day ?>" data-type="close" step="900">
                      </td>
                      <td>
                        <div class="form-check mb-0">
                          <input class="form-check-input day-closed" type="checkbox" id="closed_<?= $dayKey ?>" data-day="<?= $day ?>">
                          <label class="form-check-label" for="closed_<?= $dayKey ?>">Yes</label>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="form-text">Pick open and close times for each day using the time picker.</div>
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

        <div class="mb-3">
          <label class="text-muted mb-1">Store Availability</label>
          <div>
            <span class="badge bg-<?= !array_key_exists('is_open', $restaurant) || (int) $restaurant['is_open'] === 1 ? 'success' : 'secondary' ?>" id="storeAvailabilityBadge">
              <?= !array_key_exists('is_open', $restaurant) || (int) $restaurant['is_open'] === 1 ? 'Open' : 'Closed' ?>
            </span>
          </div>
          <small class="text-muted d-block mt-1" id="storeAvailabilityText">
            <?= !array_key_exists('is_open', $restaurant) || (int) $restaurant['is_open'] === 1 ? 'Visible as open to customers' : 'Temporarily not accepting new orders' ?>
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
  const DAYS_OF_WEEK = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  const DEFAULT_SCHEDULE = {
    Monday: { open: '09:00', close: '21:00', closed: false },
    Tuesday: { open: '09:00', close: '21:00', closed: false },
    Wednesday: { open: '09:00', close: '21:00', closed: false },
    Thursday: { open: '09:00', close: '21:00', closed: false },
    Friday: { open: '09:00', close: '22:00', closed: false },
    Saturday: { open: '10:00', close: '22:00', closed: false },
    Sunday: { open: '10:00', close: '20:00', closed: false }
  };

  const originalData = {
    name: document.getElementById('restaurant_name').value,
    address: document.getElementById('restaurant_address').value,
    opening_hours: document.getElementById('opening_hours').value,
    is_open: document.getElementById('is_open').value
  };
  let restaurantCsrfName = '<?= csrf_token() ?>';
  let restaurantCsrfHash = '<?= csrf_hash() ?>';



  function applyStoreOpenState(isOpen) {
    const hidden = document.getElementById('is_open');
    const toggle = document.getElementById('is_open_toggle');
    const label = document.getElementById('is_open_label');
    const badge = document.getElementById('storeAvailabilityBadge');
    const helper = document.getElementById('storeAvailabilityText');

    hidden.value = isOpen ? '1' : '0';
    toggle.checked = isOpen;
    label.textContent = isOpen ? 'Open' : 'Closed';

    badge.textContent = isOpen ? 'Open' : 'Closed';
    badge.classList.remove('bg-success', 'bg-secondary');
    badge.classList.add(isOpen ? 'bg-success' : 'bg-secondary');

    helper.textContent = isOpen
      ? 'Visible as open to customers'
      : 'Temporarily not accepting new orders';
  }

  function to24Hour(timeText) {
    const text = (timeText || '').trim().toUpperCase();
    if (!text) return null;

    let match = text.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/);
    if (match) {
      let hour = parseInt(match[1], 10);
      const minute = match[2];
      const period = match[3];
      if (period === 'PM' && hour < 12) hour += 12;
      if (period === 'AM' && hour === 12) hour = 0;
      return String(hour).padStart(2, '0') + ':' + minute;
    }

    match = text.match(/^(\d{1,2}):(\d{2})$/);
    if (match) {
      const hour = parseInt(match[1], 10);
      const minute = parseInt(match[2], 10);
      if (hour >= 0 && hour <= 23 && minute >= 0 && minute <= 59) {
        return String(hour).padStart(2, '0') + ':' + String(minute).padStart(2, '0');
      }
    }

    match = text.match(/^(\d{1,2})\s*(AM|PM)$/);
    if (match) {
      let hour = parseInt(match[1], 10);
      const period = match[2];
      if (hour < 1 || hour > 12) return null;
      if (period === 'PM' && hour < 12) hour += 12;
      if (period === 'AM' && hour === 12) hour = 0;
      return String(hour).padStart(2, '0') + ':00';
    }

    return null;
  }

  function parseOpeningHours(rawText) {
    const schedule = JSON.parse(JSON.stringify(DEFAULT_SCHEDULE));
    if (!rawText) return schedule;

    rawText.split('\n').forEach(line => {
      const trimmed = line.trim();
      if (!trimmed) return;

      const parts = trimmed.split(':');
      if (parts.length < 2) return;

      const day = parts[0].trim();
      const value = parts.slice(1).join(':').trim();
      if (!DAYS_OF_WEEK.includes(day)) return;

      if (/^closed$/i.test(value)) {
        schedule[day].closed = true;
        return;
      }

      const range = value.split(/\s+-\s+|\s+to\s+/i);
      if (range.length !== 2) return;

      const openTime = to24Hour(range[0]);
      const closeTime = to24Hour(range[1]);
      if (openTime && closeTime) {
        schedule[day].open = openTime;
        schedule[day].close = closeTime;
        schedule[day].closed = false;
      }
    });

    return schedule;
  }

  function applyScheduleToUI(schedule) {
    DAYS_OF_WEEK.forEach(day => {
      const key = day.toLowerCase();
      const openInput = document.getElementById('open_' + key);
      const closeInput = document.getElementById('close_' + key);
      const closedInput = document.getElementById('closed_' + key);
      const dayData = schedule[day] || DEFAULT_SCHEDULE[day];

      openInput.value = dayData.open;
      closeInput.value = dayData.close;
      closedInput.checked = !!dayData.closed;
      openInput.disabled = !!dayData.closed;
      closeInput.disabled = !!dayData.closed;
    });
  }

  function serializeSchedule() {
    const lines = [];

    for (const day of DAYS_OF_WEEK) {
      const key = day.toLowerCase();
      const openInput = document.getElementById('open_' + key);
      const closeInput = document.getElementById('close_' + key);
      const closedInput = document.getElementById('closed_' + key);

      if (closedInput.checked) {
        lines.push(day + ': Closed');
        continue;
      }

      const open = openInput.value;
      const close = closeInput.value;

      if (!open || !close) {
        return {
          valid: false,
          error: day + ': Please select both opening and closing times, or mark the day as closed.'
        };
      }

      if (open >= close) {
        return {
          valid: false,
          error: day + ': Closing time must be later than opening time.'
        };
      }

      lines.push(day + ': ' + open + ' - ' + close);
    }

    return { valid: true, value: lines.join('\n') };
  }

  function refreshOpeningHoursField() {
    const serialized = serializeSchedule();
    if (!serialized.valid) return serialized;
    document.getElementById('opening_hours').value = serialized.value;
    return serialized;
  }

  function bindScheduleEvents() {
    DAYS_OF_WEEK.forEach(day => {
      const key = day.toLowerCase();
      const openInput = document.getElementById('open_' + key);
      const closeInput = document.getElementById('close_' + key);
      const closedInput = document.getElementById('closed_' + key);

      closedInput.addEventListener('change', function() {
        const isClosed = this.checked;
        openInput.disabled = isClosed;
        closeInput.disabled = isClosed;
        refreshOpeningHoursField();
      });

      openInput.addEventListener('change', refreshOpeningHoursField);
      closeInput.addEventListener('change', refreshOpeningHoursField);
    });
  }

  applyScheduleToUI(parseOpeningHours(originalData.opening_hours));
  bindScheduleEvents();
  refreshOpeningHoursField();

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
    applyScheduleToUI(parseOpeningHours(originalData.opening_hours));
    refreshOpeningHoursField();
    applyStoreOpenState(originalData.is_open === '1');
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

  function saveStoreAvailabilityState() {
    const toggle = document.getElementById('is_open_toggle');
    const previousState = originalData.is_open === '1';
    const formData = new FormData();

    formData.append('name', document.getElementById('restaurant_name').value);
    formData.append('address', document.getElementById('restaurant_address').value);
    formData.append('opening_hours', document.getElementById('opening_hours').value);
    formData.append('is_open', document.getElementById('is_open').value);

    toggle.disabled = true;

    return fetch('<?= site_url('settings/update') ?>', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        originalData.is_open = formData.get('is_open');
        return;
      }

      const errorMsg = typeof data.error === 'object'
        ? Object.values(data.error).join(', ')
        : (data.error || 'Failed to update store availability.');

      applyStoreOpenState(previousState);
      showMessage(errorMsg, 'error');
    })
    .catch(() => {
      applyStoreOpenState(previousState);
      showMessage('Failed to update store availability. Please try again.', 'error');
    })
    .finally(() => {
      toggle.disabled = false;
    });
  }

  document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    clearMessages();

    const serialized = refreshOpeningHoursField();
    if (!serialized.valid) {
      showMessage(serialized.error, 'error');
      return;
    }

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
        originalData.is_open = formData.get('is_open');
        
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

  document.getElementById('is_open_toggle').addEventListener('change', function() {
    applyStoreOpenState(this.checked);
    saveStoreAvailabilityState();
  });

  applyStoreOpenState(document.getElementById('is_open').value === '1');
</script>
<?= $this->endSection() ?>
