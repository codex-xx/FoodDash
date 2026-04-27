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
      <span class="badge bg-primary">Picked Up</span>
      <span class="badge bg-secondary">Arrived At Restaurant</span>
      <span class="badge bg-primary">Out For Delivery</span>
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
              <th>Rider</th>
              <th>Status</th>
              <th>Est. Prep Time</th>
              <th>Amount</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <tr id="order-row-<?= (int) $order['id'] ?>" data-order-id="<?= (int) $order['id'] ?>">
                <td><strong><?= $order['order_number'] ?></strong></td>
                <td>
                  <div class="fw-semibold"><?= esc($order['display_customer_name'] ?? $order['customer_name']) ?></div>
                  <?php if (!empty($order['display_customer_phone'])): ?>
                    <small class="text-muted d-block"><?= esc($order['display_customer_phone']) ?></small>
                  <?php endif; ?>
                </td>
                <td class="order-driver-cell">
                  <?php if (!empty($order['display_driver_name'] ?? $order['driver_name'])): ?>
                    <div class="fw-semibold"><?= esc($order['display_driver_name'] ?? $order['driver_name']) ?></div>
                  <?php else: ?>
                    <small class="text-muted">No driver accepts</small>
                  <?php endif; ?>
                </td>
                <td class="order-status-cell">
                  <?php
                    $statusClass = match ($order['status']) {
                      'pending' => 'warning',
                      'accepted' => 'info',
                      'preparing' => 'primary',
                      'ready' => 'secondary',
                      'picked_up' => 'primary',
                      'arrived_at_restaurant' => 'secondary',
                      'out_for_delivery' => 'primary',
                      'delivered' => 'success',
                      'cancelled' => 'danger',
                      default => 'secondary'
                    };
                  ?>
                  <span class="badge bg-<?= $statusClass ?>"><?= ucwords(str_replace('_', ' ', $order['status'])) ?></span>
                </td>
                <td class="order-prep-cell">
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
                <td class="order-actions-cell">
                  <button class="btn btn-sm btn-outline-secondary mb-1" type="button" onclick="showOrderDetails(<?= (int) $order['id'] ?>)">
                    <i class="bi bi-receipt"></i> View Details
                  </button>
                  <br>
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

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="orderDetailsLabel">Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <h6 class="mb-2">Customer Info</h6>
              <div><strong>Name:</strong> <span id="detail_customer_name">-</span></div>
              <div><strong>Phone:</strong> <span id="detail_customer_phone">-</span></div>
              <div><strong>Email:</strong> <span id="detail_customer_email">-</span></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <h6 class="mb-2">Delivery Info</h6>
              <div><strong>Order #:</strong> <span id="detail_order_number">-</span></div>
              <div><strong>Status:</strong> <span id="detail_order_status">-</span></div>
              <div><strong>Payment Type:</strong> <span id="detail_payment_type">-</span></div>
              <div><strong>Address:</strong> <span id="detail_delivery_address">-</span></div>
            </div>
          </div>
        </div>

        <h6 class="mb-2">Items</h6>
        <div class="table-responsive">
          <table class="table table-sm align-middle" id="detail_items_table">
            <thead class="table-light">
              <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
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
          <select class="form-select" id="status_select"></select>
          <small class="text-muted d-none" id="status_help_text"></small>
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
  const orderDetails = <?= json_encode(array_map(static function ($order) {
    return [
      'id' => (int) ($order['id'] ?? 0),
      'order_number' => (string) ($order['order_number'] ?? ''),
      'status' => (string) ($order['status'] ?? ''),
      'payment_type_label' => (string) ($order['display_payment_type'] ?? '-'),
      'customer_name' => (string) ($order['display_customer_name'] ?? $order['customer_name'] ?? ''),
      'customer_phone' => (string) ($order['display_customer_phone'] ?? ''),
      'customer_email' => (string) ($order['display_customer_email'] ?? ''),
      'delivery_address' => (string) ($order['display_customer_address'] ?? $order['delivery_address'] ?? ''),
      'items_data' => is_array($order['items_data'] ?? null) ? $order['items_data'] : [],
    ];
  }, $orders), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  const orderDetailsById = orderDetails.reduce((acc, order) => {
    acc[String(order.id)] = order;
    return acc;
  }, {});

  const orderDetailsModalEl = document.getElementById('orderDetailsModal');
  const orderDetailsModal = new bootstrap.Modal(orderDetailsModalEl);

  function textOrDash(value) {
    const text = (value ?? '').toString().trim();
    return text !== '' ? text : '-';
  }

  function statusBadgeHtml(status) {
    const map = {
      pending: '<span class="badge bg-warning">Pending</span>',
      accepted: '<span class="badge bg-info">Accepted</span>',
      preparing: '<span class="badge bg-primary">Preparing</span>',
      ready: '<span class="badge bg-secondary">Ready</span>',
      picked_up: '<span class="badge bg-primary">Picked Up</span>',
      arrived_at_restaurant: '<span class="badge bg-secondary">Arrived At Restaurant</span>',
      out_for_delivery: '<span class="badge bg-primary">Out For Delivery</span>',
      on_the_way: '<span class="badge bg-primary">Out For Delivery</span>',
      delivered: '<span class="badge bg-success">Delivered</span>',
      cancelled: '<span class="badge bg-danger">Cancelled</span>'
    };

    return map[status] || '<span class="badge bg-secondary">' + textOrDash(status).replaceAll('_', ' ') + '</span>';
  }

  function getRestaurantStatusOptions(currentStatus) {
    const status = (currentStatus || '').toString();

    if (status === 'pending') {
      return [{ value: 'accepted', label: 'Accepted' }];
    }

    if (status === 'accepted') {
      return [{ value: 'preparing', label: 'Preparing' }];
    }

    if (status === 'preparing') {
      return [{ value: 'ready', label: 'Ready' }];
    }

    return [];
  }

  function applyOrderPatch(order) {
    if (!order || !order.id) {
      return;
    }

    const row = document.getElementById(`order-row-${order.id}`);
    if (!row) {
      return;
    }

    const status = (order.status || '').toString();
    const driverName = (order.driver_name || order.rider_name || '').toString().trim();
    const driverCell = row.querySelector('.order-driver-cell');
    const statusCell = row.querySelector('.order-status-cell');
    const prepCell = row.querySelector('.order-prep-cell');
    const prepInput = document.getElementById(`prep_time_${order.id}`);
    const statusButton = row.querySelector('button[data-bs-target="#statusModal"]');

    if (driverCell) {
      driverCell.innerHTML = driverName
        ? `<div class="fw-semibold">${driverName}</div>`
        : '<small class="text-muted">No driver accepts</small>';
    }

    if (statusCell) {
      statusCell.innerHTML = statusBadgeHtml(status);
    }

    if (statusButton) {
      statusButton.setAttribute('data-current-status', status);
    }

    if (typeof order.estimated_preparation_time !== 'undefined' && prepInput) {
      prepInput.value = order.estimated_preparation_time ?? '';
      if (prepCell) {
        const hint = prepCell.querySelector('small.text-muted');
        if (hint) {
          hint.textContent = order.estimated_preparation_time ? `${order.estimated_preparation_time} min` : '';
          hint.classList.toggle('d-none', !order.estimated_preparation_time);
        } else if (order.estimated_preparation_time) {
          const newHint = document.createElement('small');
          newHint.className = 'text-muted';
          newHint.textContent = `${order.estimated_preparation_time} min`;
          prepCell.appendChild(newHint);
        }
      }
    }

    if (status === 'delivered' || status === 'cancelled') {
      row.remove();
    }
  }

  function applyOrderPatchFromResponse(orderId, payload) {
    applyOrderPatch(Object.assign({ id: Number(orderId) }, payload || {}));
  }

  function showOrderDetails(orderId) {
    const order = orderDetailsById[String(orderId)];
    if (!order) {
      alert('Order details unavailable. Please refresh the page.');
      return;
    }

    document.getElementById('detail_customer_name').textContent = textOrDash(order.customer_name);
    document.getElementById('detail_customer_phone').textContent = textOrDash(order.customer_phone);
    document.getElementById('detail_customer_email').textContent = textOrDash(order.customer_email);
    document.getElementById('detail_order_number').textContent = textOrDash(order.order_number);
    document.getElementById('detail_order_status').textContent = textOrDash(order.status).replaceAll('_', ' ');
    document.getElementById('detail_payment_type').textContent = textOrDash(order.payment_type_label);
    document.getElementById('detail_delivery_address').textContent = textOrDash(order.delivery_address);

    const tbody = document.querySelector('#detail_items_table tbody');
    tbody.innerHTML = '';

    const items = Array.isArray(order.items_data) ? order.items_data : [];
    if (!items.length) {
      const emptyRow = document.createElement('tr');
      emptyRow.innerHTML = '<td colspan="4" class="text-muted">No item details available.</td>';
      tbody.appendChild(emptyRow);
    } else {
      items.forEach(item => {
        const qty = Math.max(1, parseInt(item.quantity || 1, 10));
        const unit = parseFloat(item.unit_price || 0);
        const total = parseFloat(item.line_total || (unit * qty));
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${textOrDash(item.item_name)}</td>
          <td>${qty}</td>
          <td>PHP ${unit.toFixed(2)}</td>
          <td>PHP ${total.toFixed(2)}</td>
        `;
        tbody.appendChild(row);
      });
    }

    orderDetailsModal.show();
  }

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
    const statusSelect = document.getElementById('status_select');
    const statusHelpText = document.getElementById('status_help_text');
    const updateButton = statusModal.querySelector('button.btn.btn-primary[onclick="submitStatusUpdate()"]');
    const statusOptions = getRestaurantStatusOptions(currentStatus);
    
    document.getElementById('modal_order_id').value = orderId;

    statusSelect.innerHTML = '';
    if (statusOptions.length > 0) {
      statusOptions.forEach((optionData, index) => {
        const option = document.createElement('option');
        option.value = optionData.value;
        option.textContent = optionData.label;
        option.selected = index === 0;
        statusSelect.appendChild(option);
      });
    } else {
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'No restaurant updates available';
      option.disabled = true;
      option.selected = true;
      statusSelect.appendChild(option);
    }

    const canEdit = statusOptions.length > 0;
    if (updateButton) {
      updateButton.disabled = !canEdit;
    }

    if (statusHelpText) {
      if (canEdit) {
        statusHelpText.classList.add('d-none');
        statusHelpText.textContent = '';
      } else {
        statusHelpText.textContent = 'Restaurant status changes are only available while the order is pending, accepted, or preparing.';
        statusHelpText.classList.remove('d-none');
      }
    }
    
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
      .then((json) => {
        const modal = bootstrap.Modal.getInstance(statusModal);
        modal.hide();
        applyOrderPatchFromResponse(orderId, json.order || { status: json.status });
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
      .then((json) => {
        applyOrderPatchFromResponse(orderId, json.order || { estimated_preparation_time: prepTime });
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
        if (Array.isArray(payload.orders)) {
          payload.orders.forEach(applyOrderPatch);
        }
      } catch (e) {
        // Ignore malformed messages.
      }
    });
  })();

  // Fallback polling so rider/status updates still appear even if SSE is blocked.
  (function setupAutoRefreshFallback() {
    // No full-page fallback refresh; the page updates in place.
  })();
</script>
<?= $this->endSection() ?>

