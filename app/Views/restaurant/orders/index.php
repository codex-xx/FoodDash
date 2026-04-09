<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Order Status Control — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12">
    <div>
      <h3 class="m-0">Order Status Control</h3>
      <small class="text-muted">Manage active orders and update their status</small>
    </div>
  </div>
</div>

<!-- Order Status Legend -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h5 class="card-title mb-3"><i class="bi bi-info-circle"></i> Order Status Control</h5>
    <div class="d-flex flex-wrap gap-2">
      <span class="badge bg-warning">Pending</span>
      <span class="badge bg-info">Accepted</span>
      <span class="badge bg-primary">Preparing</span>
      <span class="badge bg-secondary">Ready</span>
      <span class="badge bg-dark">Assigned</span>
      <span class="badge bg-primary">On the Way</span>
      <span class="badge bg-success">Delivered</span>
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
                      'accepted' => 'info',
                      'preparing' => 'primary',
                      'ready' => 'secondary',
                      'assigned' => 'dark',
                      'on_the_way' => 'primary',
                      'delivered' => 'success',
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
        <h5>No active orders found</h5>
        <small>New incoming orders will appear here</small>
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
            <option value="accepted">Accepted</option>
            <option value="preparing">Preparing</option>
            <option value="ready">Ready</option>
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
  function postOrderUpdate(orderId, formData) {
    return fetch(`<?= site_url('orders') ?>/${orderId}/status`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: formData.toString()
    }).then(async (response) => {
      const raw = await response.text();
      let json = null;

      try {
        json = raw ? JSON.parse(raw) : {};
      } catch (e) {
        throw new Error(raw || `Unexpected response (HTTP ${response.status})`);
      }

      if (!response.ok || !(json && json.success)) {
        const message = (json && (json.error || json.message))
          ? (json.error || json.message)
          : `Request failed (HTTP ${response.status})`;
        throw new Error(message);
      }

      return json;
    });
  }

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
    
    postOrderUpdate(orderId, formData)
      .then(() => {
        const modal = bootstrap.Modal.getInstance(statusModal);
        modal.hide();
        alert('Order status updated successfully!');
        location.reload();
      })
      .catch(err => alert('Error: ' + err.message));
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
    
    postOrderUpdate(orderId, formData)
      .then(() => {
        alert('Preparation time updated!');
        location.reload();
      })
      .catch(err => alert('Error: ' + err.message));
  }

  // Realtime updates from central API stream.
  (function setupRealtime() {
    if (!window.EventSource) {
      return;
    }

    let lastId = 0;
    const source = new EventSource(`<?= site_url('api/orders/stream') ?>?last_id=${lastId}`);

    source.addEventListener('order_update', function (event) {
      try {
        const payload = JSON.parse(event.data || '{}');
        if (payload.last_id) {
          lastId = payload.last_id;
        }
      } catch (e) {
        // Ignore malformed messages and still refresh.
      }
      location.reload();
    });
  })();
</script>
<?= $this->endSection() ?>

