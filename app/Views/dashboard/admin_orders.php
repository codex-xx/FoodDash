<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Admin Orders — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h3 class="m-0">Orders</h3>
      <small class="text-muted">Monitor recent orders and assign active riders</small>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('dashboard/admin/orders/history') ?>" class="btn btn-outline-secondary btn-sm">History</a>
      <button class="btn btn-sm btn-primary" id="refreshOrdersBtn">Refresh Data</button>
    </div>
  </div>
</div>

<section class="mb-4">
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <h5 class="card-title m-0">Recent Orders</h5>
          <small class="text-muted">Same order feed shown on the dashboard, with assignment controls</small>
        </div>
        <div class="d-flex gap-2">
          <select id="statusFilter" class="form-select form-select-sm">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="accepted">Accepted</option>
            <option value="preparing">Preparing</option>
            <option value="ready">Ready</option>
            <option value="assigned">Assigned</option>
            <option value="on_the_way">On the way</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <input id="ordersSearch" type="search" class="form-control form-control-sm" placeholder="Search orders">
        </div>
      </div>

      <div class="table-responsive">
        <table id="ordersTable" class="table table-striped table-hover table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Restaurant</th>
              <th>Driver</th>
              <th>Status</th>
              <th>Amount</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="assignDriverModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Assign Active Rider</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Choose an active rider for the selected order.</p>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Vehicle</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="activeDriversModalBody"></tbody>
          </table>
        </div>
        <div id="activeDriversModalEmpty" class="text-center text-muted py-3 d-none">
          No active riders available.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  let selectedOrderId = null;
  let activeDriversList = [];

  function statusBadge(status) {
    const map = {
      pending: '<span class="badge bg-warning">Pending</span>',
      accepted: '<span class="badge bg-info">Accepted</span>',
      preparing: '<span class="badge bg-primary">Preparing</span>',
      ready: '<span class="badge bg-secondary">Ready</span>',
      assigned: '<span class="badge bg-info">Assigned</span>',
      on_the_way: '<span class="badge bg-primary">On the way</span>',
      delivered: '<span class="badge bg-success">Delivered</span>',
      cancelled: '<span class="badge bg-danger">Cancelled</span>'
    };
    return map[status] || '<span class="badge bg-secondary">' + status + '</span>';
  }

  function loadOrders() {
    fetch('<?= site_url('dashboard/admin/orders/data') ?>?scope=active')
      .then(r => r.json())
      .then(json => {
        activeDriversList = Array.isArray(json.activeDriversList) ? json.activeDriversList : [];

        const ordersBody = document.querySelector('#ordersTable tbody');
        ordersBody.innerHTML = '';

        (json.orders || []).forEach(order => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td><strong>${order.order_number}</strong></td>
            <td>${order.customer_name || '-'}</td>
            <td>${order.restaurant_name || '-'}</td>
            <td>${order.driver_name || 'Unassigned'}</td>
            <td>${statusBadge(order.status)}</td>
            <td>₱${parseFloat(order.total_amount || 0).toFixed(2)}</td>
            <td>${order.created_at ? new Date(order.created_at).toLocaleDateString() : '-'}</td>
            <td><button class="btn btn-sm btn-outline-secondary" onclick="assignDriver(${order.id})">Assign</button></td>
          `;
          ordersBody.appendChild(row);
        });
      })
      .catch(err => console.error(err));
  }

  function assignDriver(orderId) {
    selectedOrderId = orderId;

    const modalBody = document.getElementById('activeDriversModalBody');
    const emptyState = document.getElementById('activeDriversModalEmpty');
    modalBody.innerHTML = '';

    if (!activeDriversList.length) {
      emptyState.classList.remove('d-none');
    } else {
      emptyState.classList.add('d-none');

      activeDriversList.forEach(driver => {
        const row = document.createElement('tr');
        const vehicle = [driver.vehicle_type, driver.vehicle_number].filter(Boolean).join(' • ') || '-';
        row.innerHTML = `
          <td><strong>${driver.name || '-'}</strong></td>
          <td>${driver.email || '-'}</td>
          <td>${driver.phone || '-'}</td>
          <td>${vehicle}</td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="confirmAssignDriver(${driver.id})">Select</button>
          </td>
        `;
        modalBody.appendChild(row);
      });
    }

    const modal = new bootstrap.Modal(document.getElementById('assignDriverModal'));
    modal.show();
  }

  function confirmAssignDriver(driverId) {
    if (!selectedOrderId) return;

    fetch(`<?= site_url('orders') ?>/${selectedOrderId}/assign-driver`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `driver_id=${encodeURIComponent(driverId)}`
    })
    .then(r => r.json())
    .then(json => {
      const modalEl = document.getElementById('assignDriverModal');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();

      alert(json.message || 'Driver assigned');
      loadOrders();
    })
    .catch(err => alert('Error: ' + err));
  }

  $('#ordersSearch').on('keyup', function () {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(val) ? '' : 'none';
    });
  });

  $('#statusFilter').on('change', function () {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
      const status = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
      row.style.display = !val || status.includes(val) ? '' : 'none';
    });
  });

  $('#refreshOrdersBtn').on('click', loadOrders);

  $(document).ready(function () {
    loadOrders();
  });
</script>
<?= $this->endSection() ?>