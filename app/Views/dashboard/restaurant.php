<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Restaurant Dashboard — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h3 class="m-0">Restaurant Dashboard</h3>
      <small class="text-muted">Manage menu, orders, and track your daily performance</small>
      <p class="mt-1">Welcome, <strong><?= esc(session()->get('restaurant_name')) ?></strong></p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('settings') ?>" class="btn btn-sm btn-outline-secondary">Settings</a>
      <button class="btn btn-sm btn-primary" id="refreshBtn">Refresh Data</button>
    </div>
  </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <small class="text-muted text-uppercase">Today's Orders</small>
        <h3 class="mt-2 mb-0" id="todayOrders">0</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <small class="text-muted text-uppercase">Pending Orders</small>
        <h3 class="mt-2 mb-0" id="pendingOrders">0</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <small class="text-muted text-uppercase">Today's Revenue</small>
        <h3 class="mt-2 mb-0" id="dailyRevenue">₱0.00</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <small class="text-muted text-uppercase">Menu Items</small>
        <h3 class="mt-2 mb-0" id="menuItems">0</h3>
      </div>
    </div>
  </div>
</div>

<!-- Active Orders Section -->
<div class="row mb-4">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="card-title m-0">Active Orders</h5>
            <small class="text-muted">Orders pending and being prepared</small>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0" id="ordersTable">
            <thead class="table-light">
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <div id="noOrders" class="text-center py-4 text-muted">
            <small>No orders yet</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Menu Snapshot Section -->
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="card-title m-0">Menu Items</h5>
            <small class="text-muted">Your latest items</small>
          </div>
          <a href="<?= site_url('menu') ?>" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0" id="menuTable">
            <thead class="table-light">
              <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Available</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <div id="noMenu" class="text-center py-4 text-muted">
            <small>No menu items yet. <a href="<?= site_url('menu/create') ?>">Create one</a></small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  function loadDashboard() {
    fetch('<?= site_url('dashboard/restaurant/data') ?>')
      .then(r => r.json())
      .then(json => {
        // Update metrics
        $('#todayOrders').text(json.metrics.todayOrders);
        $('#pendingOrders').text(json.metrics.pendingOrders);
        $('#dailyRevenue').text('₱' + Number(json.metrics.dailyRevenue).toFixed(2));
        $('#menuItems').text(json.metrics.menuItems);

        // Update orders table
        const ordersTable = document.querySelector('#ordersTable tbody');
        ordersTable.innerHTML = '';
        
        if (json.recentOrders && json.recentOrders.length > 0) {
          document.getElementById('noOrders').style.display = 'none';
          json.recentOrders.forEach(order => {
            const statusBadge = getStatusBadge(order.status);
            const row = `
              <tr>
                <td><strong>${order.order_number}</strong></td>
                <td>${order.customer_name}</td>
                <td>${statusBadge}</td>
                <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                <td>
                  <button class="btn btn-sm btn-outline-primary" onclick="updateOrderStatus(${order.id})">Update Status</button>
                </td>
              </tr>
            `;
            ordersTable.insertAdjacentHTML('beforeend', row);
          });
        } else {
          document.getElementById('noOrders').style.display = 'block';
        }

        // Update menu table
        const menuTable = document.querySelector('#menuTable tbody');
        menuTable.innerHTML = '';
        
        if (json.menuItems && json.menuItems.length > 0) {
          document.getElementById('noMenu').style.display = 'none';
          json.menuItems.forEach(item => {
            const availBadge = item.is_available 
              ? '<span class="badge bg-success">Available</span>'
              : '<span class="badge bg-danger">Unavailable</span>';
            const row = `
              <tr>
                <td>${item.name}</td>
                <td>₱${parseFloat(item.price).toFixed(2)}</td>
                <td>${availBadge}</td>
              </tr>
            `;
            menuTable.insertAdjacentHTML('beforeend', row);
          });
        } else {
          document.getElementById('noMenu').style.display = 'block';
        }
      })
      .catch(err => console.error(err));
  }

  function getStatusBadge(status) {
    const map = {
      'pending': '<span class="badge bg-warning">Pending</span>',
      'confirmed': '<span class="badge bg-info">Confirmed</span>',
      'preparing': '<span class="badge bg-primary">Preparing</span>',
      'ready_for_pickup': '<span class="badge bg-success">Ready</span>',
      'completed': '<span class="badge bg-success">Completed</span>',
      'cancelled': '<span class="badge bg-danger">Cancelled</span>'
    };
    return map[status] || '<span class="badge bg-secondary">' + status + '</span>';
  }

  function updateOrderStatus(orderId) {
    const newStatus = prompt('Enter new status (pending, confirmed, preparing, ready_for_pickup, completed, cancelled):');
    if (!newStatus) return;

    fetch(`<?= site_url('orders') ?>/${orderId}/status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `status=${newStatus}`
    })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        alert('Order status updated');
      } else {
        alert('Error: ' + (json.error || 'Unknown error'));
      }
      loadDashboard();
    })
    .catch(err => {
      alert('Error updating status');
      console.error(err);
    });
  }

  $('#refreshBtn').on('click', loadDashboard);

  $(document).ready(function() {
    loadDashboard();
    setInterval(loadDashboard, 30000); // Refresh every 30 seconds
  });
</script>
<?= $this->endSection() ?>
