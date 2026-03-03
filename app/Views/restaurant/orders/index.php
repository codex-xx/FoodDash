<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Order Status Control — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12">
    <div>
      <h3 class="m-0">Order Status Control</h3>
      <small class="text-muted">Manage and track all your orders</small>
    </div>
  </div>
</div>

<!-- Date Filter Section -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h5 class="card-title mb-3"><i class="bi bi-calendar-range"></i> Order History - Filter by Date</h5>
    <form method="get" action="<?= site_url('orders') ?>" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label for="start_date" class="form-label">From Date</label>
        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= request()->getGet('start_date') ?? '' ?>">
      </div>
      <div class="col-md-4">
        <label for="end_date" class="form-label">To Date</label>
        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= request()->getGet('end_date') ?? '' ?>">
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-primary w-100">
          <i class="bi bi-filter"></i> Filter Orders
        </button>
        <a href="<?= site_url('orders') ?>" class="btn btn-outline-secondary w-100 mt-2">
          Clear Filters
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Order Status Legend -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h5 class="card-title mb-3"><i class="bi bi-info-circle"></i> Order Status Control</h5>
    <div class="d-flex flex-wrap gap-2">
      <span class="badge bg-warning">Pending</span>
      <span class="badge bg-info">Confirmed</span>
      <span class="badge bg-primary">Preparing</span>
      <span class="badge bg-success">Ready for Pickup</span>
      <span class="badge bg-success">Completed</span>
      <span class="badge bg-danger">Cancelled</span>
    </div>
  </div>
</div>

<!-- Orders Table -->
<div class="card shadow-sm">
  <div class="card-body">
    <?php if (!empty($orders)): ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Est. Prep Time</th>
              <th>Amount</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <tr>
                <td><strong><?= $order['order_number'] ?></strong></td>
                <td><?= $order['customer_name'] ?></td>
                <td>
                  <?php
                    $statusClass = match ($order['status']) {
                      'pending' => 'warning',
                      'confirmed' => 'info',
                      'preparing' => 'primary',
                      'ready_for_pickup' => 'success',
                      'completed' => 'success',
                      'cancelled' => 'danger',
                      default => 'secondary'
                    };
                  ?>
                  <span class="badge bg-<?= $statusClass ?>"><?= ucwords(str_replace('_', ' ', $order['status'])) ?></span>
                </td>
                <td>
                  <div class="input-group input-group-sm" style="max-width: 150px;">
                    <input type="number" class="form-control" 
                           id="prep_time_<?= $order['id'] ?>" 
                           value="<?= $order['estimated_preparation_time'] ?? '' ?>" 
                           placeholder="Minutes" 
                           min="1">
                    <button class="btn btn-outline-primary" 
                            type="button" 
                            onclick="updatePrepTime(<?= $order['id'] ?>)"
                            title="Set preparation time">
                      <i class="bi bi-check2"></i>
                    </button>
                  </div>
                  <?php if (!empty($order['estimated_preparation_time'])): ?>
                    <small class="text-muted"><?= $order['estimated_preparation_time'] ?> min</small>
                  <?php endif; ?>
                </td>
                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                <td><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary" 
                          data-bs-toggle="modal" 
                          data-bs-target="#statusModal"
                          data-order-id="<?= $order['id'] ?>"
                          data-current-status="<?= $order['status'] ?>">
                    <i class="bi bi-pencil"></i> Update Status
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center py-5 text-muted">
        <h5>No orders found</h5>
        <small>Try adjusting your date filters or check back later</small>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="statusModalLabel">Update Order Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="modal_order_id">
        <div class="mb-3">
          <label for="status_select" class="form-label">Select Status</label>
          <select class="form-select" id="status_select">
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="preparing">Preparing</option>
            <option value="ready_for_pickup">Ready for Pickup</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="modal_prep_time" class="form-label">Estimated Preparation Time (minutes)</label>
          <input type="number" class="form-control" id="modal_prep_time" placeholder="e.g., 15" min="1">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="submitStatusUpdate()">Update</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // Handle modal show
  const statusModal = document.getElementById('statusModal');
  statusModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const orderId = button.getAttribute('data-order-id');
    const currentStatus = button.getAttribute('data-current-status');
    
    document.getElementById('modal_order_id').value = orderId;
    document.getElementById('status_select').value = currentStatus;
    
    // Try to get prep time from the input field
    const prepTimeInput = document.getElementById('prep_time_' + orderId);
    if (prepTimeInput && prepTimeInput.value) {
      document.getElementById('modal_prep_time').value = prepTimeInput.value;
    } else {
      document.getElementById('modal_prep_time').value = '';
    }
  });

  // Update status from modal
  function submitStatusUpdate() {
    const orderId = document.getElementById('modal_order_id').value;
    const status = document.getElementById('status_select').value;
    const prepTime = document.getElementById('modal_prep_time').value;
    
    const formData = new URLSearchParams();
    formData.append('status', status);
    if (prepTime) {
      formData.append('estimated_preparation_time', prepTime);
    }
    
    fetch(`<?= site_url('orders') ?>/${orderId}/status`, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData.toString()
    })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        // Close modal
        const modal = bootstrap.Modal.getInstance(statusModal);
        modal.hide();
        
        // Show success message
        alert('Order status updated successfully!');
        
        // Reload page
        location.reload();
      } else {
        alert('Error: ' + (json.error || 'Unknown error'));
      }
    })
    .catch(err => alert('Error: ' + err));
  }

  // Update preparation time directly from input
  function updatePrepTime(orderId) {
    const prepTime = document.getElementById('prep_time_' + orderId).value;
    
    if (!prepTime || prepTime < 1) {
      alert('Please enter a valid preparation time');
      return;
    }
    
    const formData = new URLSearchParams();
    formData.append('estimated_preparation_time', prepTime);
    
    fetch(`<?= site_url('orders') ?>/${orderId}/status`, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData.toString()
    })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        alert('Preparation time updated!');
        location.reload();
      } else {
        alert('Error: ' + (json.error || 'Unknown error'));
      }
    })
    .catch(err => alert('Error: ' + err));
  }
</script>
<?= $this->endSection() ?>

