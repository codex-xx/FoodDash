<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Admin Dashboard — FoodDash'); ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h3 class="m-0">Admin Dashboard</h3>
      <small class="text-muted">System management and platform overview</small>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('dashboard/admin/security') ?>" class="btn btn-sm btn-outline-dark">Security Monitor</a>
      <button class="btn btn-sm btn-primary" id="refreshBtn">Refresh Data</button>
    </div>
  </div>
</div>

<!-- Top Revenue Section -->
<div class="row mb-4">
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-12">
            <div class="mb-3">
              <small class="text-muted text-uppercase d-block">Total Revenue</small>
              <h2 class="text-dark mb-3" id="totalRevenue">₱0.00</h2>
            </div>
            <div class="row g-3">
              <div class="col-6">
                <div class="p-2 bg-light rounded">
                  <small class="text-muted d-block">Income</small>
                  <h5 class="text-success mb-0" id="totalIncome">₱0.00</h5>
                </div>
              </div>
              <div class="col-6">
                <div class="p-2 bg-light rounded">
                  <small class="text-muted d-block">Expense</small>
                  <h5 class="text-danger mb-0" id="totalExpense">₱0.00</h5>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="card-title m-0">Performance</h5>
            <small class="text-muted">Monthly growth</small>
          </div>
        </div>
        <div class="text-center py-3">
          <div class="d-inline-circle" style="width: 80px; height: 80px; border-radius: 50%; background: conic-gradient(var(--bs-success) 0deg, var(--bs-success) 216deg, var(--bs-light) 216deg); display: flex; align-items: center; justify-content: center;">
            <div style="background: white; width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--bs-success);">
              <span id="performancePercent">+15%</span>
            </div>
          </div>
        </div>
        <small class="text-muted text-center d-block">Compared to last month</small>
      </div>
    </div>
  </div>
</div>

<!-- Charts Section -->
<div class="row mb-4">
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="card-title m-0">Order Rate</h5>
            <small class="text-muted">Total orders by month</small>
          </div>
          <select class="form-select form-select-sm" style="width: auto;">
            <option>This Year</option>
            <option>Last Year</option>
          </select>
        </div>
        <canvas id="orderRateChart" style="max-height: 300px;"></canvas>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="card-title m-0">Popular Food</h5>
            <small class="text-muted">Top selling items</small>
          </div>
          <a href="#" class="text-muted" style="text-decoration: none;">...</a>
        </div>
        <div id="popularFoodChartWrap" class="position-relative" style="height: 250px;">
          <canvas id="popularFoodChart"></canvas>
          <div id="popularFoodEmptyState" class="position-absolute top-50 start-50 translate-middle text-muted d-none">
            <small>No orders for now</small>
          </div>
        </div>
        <div id="popularFoodLegend" class="mt-3"></div>
      </div>
    </div>
  </div>
</div>

<!-- Statistics Row -->
<div class="row mb-4">
  <div class="col-lg-8"></div>
  <div class="col-lg-4">
    <div class="row g-2">
      <div class="col-6">
        <div class="card shadow-sm border-0 text-center">
          <div class="card-body p-3">
            <small class="text-muted d-block">Total Completed</small>
            <h4 class="text-success mb-0" id="ordersCompleted">0</h4>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="card shadow-sm border-0 text-center">
          <div class="card-body p-3">
            <small class="text-muted d-block">Total Delivered</small>
            <h4 class="text-info mb-0" id="ordersDelivered">0</h4>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="card shadow-sm border-0 text-center">
          <div class="card-body p-3">
            <small class="text-muted d-block">Total Canceled</small>
            <h4 class="text-danger mb-0" id="ordersCanceled">0</h4>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="card shadow-sm border-0 text-center">
          <div class="card-body p-3">
            <small class="text-muted d-block">Order Pending</small>
            <h4 class="text-warning mb-0" id="ordersPending">0</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="card-title m-0">Restaurant Map</h5>
            <small class="text-muted">All restaurants with saved map coordinates</small>
          </div>
        </div>
        <div id="adminRestaurantMap" style="height: 430px; border-radius: 10px;"></div>
      </div>
    </div>
  </div>
</div>

<!-- Bottom Sections -->
<div class="row mb-4">
  <div class="col-lg-6">
    <div class="card shadow-sm border-0">
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
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm border-0">
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
  </div>
</div>

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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
  let selectedOrderId = null;
  let activeDriversList = [];
  let orderRateChart = null;
  let popularFoodChart = null;
  let adminRestaurantMap = null;
  let adminRestaurantLayer = null;

  function initAdminRestaurantMap() {
    const mapEl = document.getElementById('adminRestaurantMap');
    if (!mapEl || typeof L === 'undefined') return;

    adminRestaurantMap = L.map('adminRestaurantMap').setView([14.5995, 120.9842], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(adminRestaurantMap);

    adminRestaurantLayer = L.layerGroup().addTo(adminRestaurantMap);
    loadAdminRestaurantMarkers();
  }

  function loadAdminRestaurantMarkers() {
    if (!adminRestaurantMap || !adminRestaurantLayer) return;

    fetch('<?= site_url('dashboard/admin/restaurant-locations') ?>')
      .then(r => r.json())
      .then(json => {
        const rows = Array.isArray(json.restaurants) ? json.restaurants : [];
        adminRestaurantLayer.clearLayers();

        if (rows.length === 0) {
          return;
        }

        const bounds = [];
        rows.forEach(function (row) {
          if (row.latitude === null || row.longitude === null) {
            return;
          }

          const lat = Number(row.latitude);
          const lng = Number(row.longitude);
          const marker = L.marker([lat, lng]).addTo(adminRestaurantLayer);
          marker.bindPopup(
            '<strong>' + (row.name || 'Restaurant') + '</strong><br>' +
            (row.address || 'No address') + '<br>' +
            'Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6)
          );
          bounds.push([lat, lng]);
        });

        if (bounds.length > 0) {
          adminRestaurantMap.fitBounds(bounds, { padding: [30, 30] });
        }
      })
      .catch(err => console.error('Failed to load restaurant markers:', err));
  }

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

  function initOrderRateChart(monthlyData) {
    const ctx = document.getElementById('orderRateChart');
    if (!ctx) return;

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const chartData = months.map((month, idx) => monthlyData[idx] || 0);

    if (orderRateChart) {
      orderRateChart.destroy();
    }

    orderRateChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: months,
        datasets: [{
          label: 'Orders',
          data: chartData,
          borderColor: 'var(--bs-primary)',
          backgroundColor: 'rgba(13, 110, 253, 0.05)',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: 'var(--bs-primary)',
          pointBorderColor: 'white',
          pointBorderWidth: 2,
          pointRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }

  function initPopularFoodChart(foodData) {
    const ctx = document.getElementById('popularFoodChart');
    if (!ctx) return;
    const emptyState = document.getElementById('popularFoodEmptyState');
    const legendDiv = document.getElementById('popularFoodLegend');

    // Filter out items with zero orders and take top 5
    const validFoods = foodData.filter(f => f.order_count > 0).slice(0, 5);

    if (popularFoodChart) {
      popularFoodChart.destroy();
    }

    if (validFoods.length === 0) {
      // Keep chart area stable and show a neutral donut when no data exists.
      emptyState.classList.remove('d-none');
      legendDiv.innerHTML = '<div class="text-center text-muted py-4"><small>No orders for now</small></div>';

      popularFoodChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['No orders'],
          datasets: [{
            data: [1],
            backgroundColor: ['#e9ecef'],
            borderColor: 'white',
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '65%',
          plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
          }
        }
      });

      return;
    }

    emptyState.classList.add('d-none');

    const colors = ['#FFC107', '#DC3545', '#28A745', '#17A2B8', '#6F42C1'];
    const labels = validFoods.map(f => f.name);
    const data = validFoods.map(f => f.order_count);

    popularFoodChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: colors.slice(0, labels.length),
          borderColor: 'white',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
          legend: { display: false }
        }
      }
    });

    // Show legend with percentages
    const totalOrders = data.reduce((a, b) => a + b, 0);
    legendDiv.innerHTML = '';
    
    validFoods.forEach((item, idx) => {
      const percentage = Math.round((item.order_count / totalOrders) * 100);
      const legendItem = document.createElement('div');
      legendItem.className = 'mb-2 d-flex align-items-center justify-content-between';
      legendItem.innerHTML = `
        <div class="d-flex align-items-center">
          <span style="width: 12px; height: 12px; background-color: ${colors[idx]}; border-radius: 2px; display: inline-block; margin-right: 8px;"></span>
          <small><strong>${item.name}</strong> (${percentage}%)</small>
        </div>
        <small class="text-muted">${item.order_count} orders</small>
      `;
      legendDiv.appendChild(legendItem);
    });
  }

  function loadDashboard() {
    fetch('<?= site_url('dashboard/admin/data') ?>')
      .then(r => r.json())
      .then(json => {
        // Update top metrics
        const totalRev = Number(json.metrics.dailyRevenue || 0);
        const income = totalRev * 0.8;
        const expense = totalRev * 0.2;

        $('#totalRevenue').text('₱' + totalRev.toFixed(2));
        $('#totalIncome').text('₱' + income.toFixed(2));
        $('#totalExpense').text('₱' + expense.toFixed(2));

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

        // Fetch and load chart data (menu-based)
        loadChartData();
        loadAdminRestaurantMarkers();
      })
      .catch(err => console.error(err));
  }

  function loadChartData() {
    fetch('<?= site_url('dashboard/admin/chart-data') ?>')
      .then(r => r.json())
      .then(json => {
        // Update order statistics with real data
        const breakdown = json.orderBreakdown || {};
        $('#ordersCompleted').text(breakdown.completed || 0);
        $('#ordersDelivered').text(breakdown.delivered || 0);
        $('#ordersCanceled').text(breakdown.cancelled || 0);
        $('#ordersPending').text(breakdown.pending || 0);

        // Initialize order rate chart with real monthly data
        const monthlyData = json.monthlyOrders || [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        initOrderRateChart(monthlyData);

        // Initialize popular food chart with real menu items data
        const popularMenus = json.popularMenus || [];
        initPopularFoodChart(popularMenus);
      })
      .catch(err => console.error('Error loading chart data:', err));
  }

  function refreshAdminLiveSummary() {
    fetch('<?= site_url('dashboard/admin/data') ?>')
      .then(r => r.json())
      .then(json => {
        const totalRev = Number(json.metrics.dailyRevenue || 0);
        const income = totalRev * 0.8;
        const expense = totalRev * 0.2;

        $('#totalRevenue').text('₱' + totalRev.toFixed(2));
        $('#totalIncome').text('₱' + income.toFixed(2));
        $('#totalExpense').text('₱' + expense.toFixed(2));

        activeDriversList = Array.isArray(json.activeDriversList) ? json.activeDriversList : [];

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
            const appliedOn = driver.created_at ? new Date(driver.created_at).toLocaleDateString() : '-';
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

        loadChartData();
        loadAdminRestaurantMarkers();
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

  $('#refreshBtn').on('click', loadDashboard);

  function resizeDashboardCharts() {
    if (orderRateChart) {
      orderRateChart.resize();
    }
    if (popularFoodChart) {
      popularFoodChart.resize();
    }
  }

  $(document).ready(function () {
    initAdminRestaurantMap();
    loadDashboard();
    setInterval(loadDashboard, 15000);
  });

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
      resizeDashboardCharts();
    }
  });

  window.addEventListener('resize', resizeDashboardCharts);

  (function setupRealtime() {
    if (!window.EventSource) {
      return;
    }

    const source = new EventSource('<?= site_url('api/orders/stream') ?>');
    source.addEventListener('order_update', function () {
      refreshAdminLiveSummary();
    });
  })();
</script>
<?= $this->endSection() ?>
