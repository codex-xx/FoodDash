<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Admin Dashboard — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h3 class="m-0">Admin Dashboard</h3>
      <small class="text-muted">System management and platform overview</small>
    </div>
    <button class="btn btn-sm btn-primary" id="refreshBtn">Refresh Data</button>
  </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4" id="summaryRow">
  <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
    <div class="card summary-card text-center border-primary shadow-sm">
      <div class="card-body">
        <small class="text-muted text-uppercase">Total Users</small>
        <h3 class="mt-2 mb-0" id="totalUsers">0</h3>
      </div>
    </div>
  </div>

  <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
    <div class="card summary-card text-center border-warning shadow-sm">
      <div class="card-body">
        <small class="text-muted text-uppercase">Total Restaurants</small>
        <h3 class="mt-2 mb-0" id="totalRestaurants">0</h3>
      </div>
    </div>
  </div>

  <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
    <div class="card summary-card text-center border-info shadow-sm">
      <div class="card-body">
        <small class="text-muted text-uppercase">Active Drivers</small>
        <h3 class="mt-2 mb-0" id="activeDrivers">0</h3>
      </div>
    </div>
  </div>

  <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
    <div class="card summary-card text-center border-success shadow-sm">
      <div class="card-body">
        <small class="text-muted text-uppercase">Orders Today</small>
        <h3 class="mt-2 mb-0" id="totalOrdersToday">0</h3>
      </div>
    </div>
  </div>

  <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
    <div class="card summary-card text-center border-secondary shadow-sm">
      <div class="card-body">
        <small class="text-muted text-uppercase">Daily Revenue</small>
        <h3 class="mt-2 mb-0" id="dailyRevenue">₱0.00</h3>
      </div>
    </div>
  </div>

  <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
    <div class="card summary-card text-center border-danger shadow-sm">
      <div class="card-body">
        <small class="text-muted text-uppercase">Pending Approvals</small>
        <h3 class="mt-2 mb-0" id="pendingApprovals">0</h3>
      </div>
    </div>
  </div>
</div>

<!-- Pending Driver Approvals -->
<section id="pendingDrivers" class="mb-5">
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h5 class="card-title m-0">Pending Driver Registrations</h5>
          <small class="text-muted">Approve or reject new driver applications</small>
        </div>
        <a href="<?= site_url('admin/drivers/pending') ?>" class="btn btn-sm btn-outline-primary">View All</a>
      </div>

      <div class="table-responsive" id="pendingDriversTableWrap">
        <table id="pendingDriversTable" class="table table-striped table-hover table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Vehicle</th>
              <th>Applied On</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <div id="pendingDriversEmpty" class="text-center py-4 text-muted d-none">
        <small>No pending driver registrations.</small>
      </div>
    </div>
  </div>
</section>

<!-- Recent Orders Table -->
<section id="orders" class="mb-5">
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <h5 class="card-title m-0">Recent Orders</h5>
          <small class="text-muted">Monitor and manage orders across all restaurants</small>
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

<!-- Revenue Summary Table -->
<section id="revenue" class="mb-5">
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h5 class="card-title m-0">Revenue Summary (Last 30 Days)</h5>
          <small class="text-muted">By Restaurant</small>
        </div>
      </div>

      <div class="table-responsive">
        <table id="revenueTable" class="table table-striped table-hover table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>Restaurant</th>
              <th>Orders</th>
              <th>Revenue</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<!-- Assign Driver Modal -->
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
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

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

  function loadDashboard() {
    fetch('<?= site_url('dashboard/admin/data') ?>')
      .then(r => r.json())
      .then(json => {
        // Update metrics
        $('#totalUsers').text(json.metrics.totalUsers);
        $('#totalRestaurants').text(json.metrics.totalRestaurants);
        $('#activeDrivers').text(json.metrics.activeDrivers);
        $('#totalOrdersToday').text(json.metrics.totalOrdersToday);
        $('#dailyRevenue').text('₱' + Number(json.metrics.dailyRevenue).toFixed(2));
        $('#pendingApprovals').text(json.metrics.pendingRestaurants + json.metrics.pendingDrivers);
        activeDriversList = Array.isArray(json.activeDriversList) ? json.activeDriversList : [];

        // Update pending drivers table
        const pendingDriversBody = document.querySelector('#pendingDriversTable tbody');
        const pendingDrivers = json.pendingDrivers || [];
        const pendingDriversEmpty = document.getElementById('pendingDriversEmpty');
        const pendingDriversTableWrap = document.getElementById('pendingDriversTableWrap');

        pendingDriversBody.innerHTML = '';

        if (pendingDrivers.length === 0) {
          pendingDriversTableWrap.classList.add('d-none');
          pendingDriversEmpty.classList.remove('d-none');
        } else {
          pendingDriversTableWrap.classList.remove('d-none');
          pendingDriversEmpty.classList.add('d-none');

          pendingDrivers.forEach(driver => {
            const row = document.createElement('tr');
            const appliedOn = driver.created_at
              ? new Date(driver.created_at).toLocaleDateString()
              : '-';
            const vehicle = driver.vehicle_type || '-';

            row.innerHTML = `
              <td><strong>${driver.name || '-'}</strong></td>
              <td>${driver.email || '-'}</td>
              <td>${driver.phone || '-'}</td>
              <td>${vehicle}</td>
              <td>${appliedOn}</td>
              <td>
                <button class="btn btn-sm btn-success" onclick="approvePendingDriver(${driver.id})">Approve</button>
                <button class="btn btn-sm btn-danger" onclick="rejectPendingDriver(${driver.id})">Reject</button>
              </td>
            `;
            pendingDriversBody.appendChild(row);
          });
        }

        // Update orders table
        const ordersBody = document.querySelector('#ordersTable tbody');
        ordersBody.innerHTML = '';
        (json.recentOrders || []).forEach(order => {
          const canAssign = order.status === 'preparing';
          const row = document.createElement('tr');
          row.innerHTML = `
            <td><strong>${order.order_number}</strong></td>
            <td>${order.customer_name}</td>
            <td>${order.restaurant_name || '-'}</td>
            <td>${order.driver_name || 'Unassigned'}</td>
            <td>${statusBadge(order.status)}</td>
            <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
            <td>${new Date(order.created_at).toLocaleDateString()}</td>
            <td><button class="btn btn-sm btn-outline-secondary" onclick="assignDriver(${order.id})" ${canAssign ? '' : 'disabled'} title="${canAssign ? 'Assign rider' : 'Only available when order is Preparing'}">Assign</button></td>
          `;
          ordersBody.appendChild(row);
        });

        // Load and display revenue summary
        fetch('<?= site_url('api/admin/revenue-summary') ?>')
          .then(r => r.json())
          .then(revData => {
            const revBody = document.querySelector('#revenueTable tbody');
            revBody.innerHTML = '';
            (revData.revenueByRestaurant || []).forEach(rest => {
              const row = document.createElement('tr');
              row.innerHTML = `
                <td>${rest.name}</td>
                <td>${rest.orders}</td>
                <td>₱${parseFloat(rest.revenue).toFixed(2)}</td>
              `;
              revBody.appendChild(row);
            });
          });
      })
      .catch(err => console.error(err));
  }

  function approvePendingDriver(driverId) {
    if (!confirm('Approve this driver?')) return;

    fetch(`<?= site_url('admin/drivers') ?>/${driverId}/approve`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => r.json())
    .then(json => {
      alert(json.message || 'Driver approved');
      loadDashboard();
    })
    .catch(err => alert('Error: ' + err));
  }

  function rejectPendingDriver(driverId) {
    if (!confirm('Reject this driver?')) return;

    fetch(`<?= site_url('admin/drivers') ?>/${driverId}/reject`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => r.json())
    .then(json => {
      alert(json.message || 'Driver rejected');
      loadDashboard();
    })
    .catch(err => alert('Error: ' + err));
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
        const vehicle = driver.vehicle_type || '-';
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
      loadDashboard();
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

  $('#refreshBtn').on('click', loadDashboard);

  $(document).ready(function () {
    loadDashboard();
    setInterval(loadDashboard, 15000);
  });

  (function setupRealtime() {
    if (!window.EventSource) {
      return;
    }

    const source = new EventSource('<?= site_url('api/orders/stream') ?>');
    source.addEventListener('order_update', function () {
      loadDashboard();
    });
  })();
</script>
<?= $this->endSection() ?>
