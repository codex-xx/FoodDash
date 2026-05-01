<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Restaurant Dashboard - FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h3 class="m-0">Restaurant Dashboard</h3>
      <small class="text-muted">Manage menu, orders, and track your daily performance</small>
      <p class="mt-1 mb-0">Welcome, <strong><?= esc(session()->get('restaurant_name')) ?></strong></p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('settings') ?>" class="btn btn-sm btn-outline-secondary">Settings</a>
      <button class="btn btn-sm btn-primary" id="refreshBtn">Refresh Data</button>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-12">
            <div class="mb-3">
              <small class="text-muted text-uppercase d-block">Today's Revenue</small>
              <h2 class="text-dark mb-3" id="totalRevenue">P0.00</h2>
            </div>
            <div class="row g-3">
              <div class="col-6">
                <div class="p-2 bg-light rounded">
                  <small class="text-muted d-block">Income</small>
                  <h5 class="text-success mb-0" id="totalIncome">P0.00</h5>
                </div>
              </div>
              <div class="col-6">
                <div class="p-2 bg-light rounded">
                  <small class="text-muted d-block">Expense</small>
                  <h5 class="text-danger mb-0" id="totalExpense">P0.00</h5>
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
              <span id="performancePercent">+0%</span>
            </div>
          </div>
        </div>
        <small class="text-muted text-center d-block">Compared to last month</small>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="card-title m-0">Order Rate</h5>
            <small class="text-muted">Total orders by month</small>
          </div>
          <select id="orderRateTimeframe" class="form-select form-select-sm" style="width: auto;">
            <option value="year">Year</option>
            <option value="month">Last 30 days</option>
            <option value="week">Last 7 days</option>
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
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="card-title m-0">Recent Orders</h5>
            <small class="text-muted">Live feed of your latest restaurant orders</small>
          </div>
          <a href="<?= site_url('orders') ?>" class="btn btn-sm btn-outline-primary">Manage Orders</a>
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
          <div id="noOrders" class="text-center py-4 text-muted d-none">
            <small>No orders yet</small>
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
            <h5 class="card-title m-0">Menu Snapshot</h5>
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
          <div id="noMenu" class="text-center py-4 text-muted d-none">
            <small>No menu items yet. <a href="<?= site_url('menu/create') ?>">Create one</a></small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="recentOrderDetailsModal" tabindex="-1" aria-labelledby="recentOrderDetailsLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="recentOrderDetailsLabel">Recent Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <small class="text-muted d-block">Order #</small>
          <div class="fw-semibold" id="recentOrderNumber">-</div>
        </div>
        <div class="mb-3">
          <small class="text-muted d-block">Customer</small>
          <div class="fw-semibold" id="recentOrderCustomer">-</div>
        </div>
        <div class="mb-3">
          <small class="text-muted d-block">Rider</small>
          <div class="fw-semibold" id="recentOrderRider">-</div>
        </div>
        <div class="mb-3">
          <small class="text-muted d-block">Status</small>
          <div id="recentOrderStatus">-</div>
        </div>
        <div class="mb-3">
          <small class="text-muted d-block">Amount</small>
          <div class="fw-semibold" id="recentOrderAmount">-</div>
        </div>
        <div>
          <small class="text-muted d-block">Created</small>
          <div id="recentOrderCreatedAt">-</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  let orderRateChart = null;
  let popularFoodChart = null;
  let recentOrdersById = {};

  function getStatusBadge(status) {
    const map = {
      pending: '<span class="badge bg-warning">Pending</span>',
      accepted: '<span class="badge bg-info">Accepted</span>',
      preparing: '<span class="badge bg-primary">Preparing</span>',
      ready: '<span class="badge bg-secondary">Ready</span>',
      assigned: '<span class="badge bg-dark">Assigned</span>',
      on_the_way: '<span class="badge bg-primary">On the Way</span>',
      delivered: '<span class="badge bg-success">Delivered</span>',
      cancelled: '<span class="badge bg-danger">Cancelled</span>'
    };
    return map[status] || '<span class="badge bg-secondary">' + status + '</span>';
  }

  function renderRecentOrders(recentOrders) {
    const ordersTable = document.querySelector('#ordersTable tbody');
    ordersTable.innerHTML = '';
    recentOrdersById = {};

    if (recentOrders.length > 0) {
      document.getElementById('noOrders').classList.add('d-none');
      recentOrders.forEach(order => {
        recentOrdersById[String(order.id)] = order;
        const row = document.createElement('tr');
        row.innerHTML = `
          <td><strong>${order.order_number}</strong></td>
          <td>${order.customer_name || '-'}</td>
          <td>${getStatusBadge(order.status)}</td>
          <td>P${parseFloat(order.total_amount || 0).toFixed(2)}</td>
          <td><button class="btn btn-sm btn-outline-secondary" onclick="showRecentOrderDetails(${order.id})">View Details</button></td>
        `;
        ordersTable.appendChild(row);
      });
    } else {
      document.getElementById('noOrders').classList.remove('d-none');
    }
  }

  function showRecentOrderDetails(orderId) {
    const order = recentOrdersById[String(orderId)];
    if (!order) {
      alert('Order details unavailable. Please refresh the page.');
      return;
    }

    document.getElementById('recentOrderNumber').textContent = order.order_number || '-';
    document.getElementById('recentOrderCustomer').textContent = order.customer_name || '-';
    document.getElementById('recentOrderRider').textContent = (order.rider_name || order.driver_name || '-');
    document.getElementById('recentOrderStatus').innerHTML = getStatusBadge(order.status);
    document.getElementById('recentOrderAmount').textContent = 'P' + parseFloat(order.total_amount || 0).toFixed(2);
    document.getElementById('recentOrderCreatedAt').textContent = order.created_at ? new Date(order.created_at).toLocaleString() : '-';

    const modal = new bootstrap.Modal(document.getElementById('recentOrderDetailsModal'));
    modal.show();
  }

  function initOrderRateChart(labels, data) {
    const ctx = document.getElementById('orderRateChart');
    if (!ctx) return;

    const chartLabels = Array.isArray(labels) && labels.length ? labels : [];
    const chartData = Array.isArray(data) ? data : [];

    if (orderRateChart) {
      orderRateChart.destroy();
    }

    orderRateChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: chartLabels,
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
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  function initPopularFoodChart(foodData) {
    const ctx = document.getElementById('popularFoodChart');
    if (!ctx) return;

    const emptyState = document.getElementById('popularFoodEmptyState');
    const legendDiv = document.getElementById('popularFoodLegend');
    const validFoods = foodData.filter(f => Number(f.order_count || 0) > 0).slice(0, 5);

    if (popularFoodChart) {
      popularFoodChart.destroy();
    }

    if (validFoods.length === 0) {
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
    const data = validFoods.map(f => Number(f.order_count || 0));

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

    const totalOrders = data.reduce((a, b) => a + b, 0);
    legendDiv.innerHTML = '';

    validFoods.forEach((item, idx) => {
      const percentage = totalOrders > 0 ? Math.round((Number(item.order_count || 0) / totalOrders) * 100) : 0;
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

  function loadRestaurantChartData(timeframe = 'year') {
    fetch('<?= site_url('dashboard/restaurant/chart-data') ?>' + '?timeframe=' + encodeURIComponent(timeframe))
      .then(r => r.json())
      .then(json => {
        const breakdown = json.orderBreakdown || {};
        $('#ordersCompleted').text(breakdown.completed || 0);
        $('#ordersDelivered').text(breakdown.delivered || 0);
        $('#ordersCanceled').text(breakdown.cancelled || 0);
        $('#ordersPending').text(breakdown.pending || 0);

        // Prefer orderRate (labels + data). Fallback to monthlyOrders (legacy year-only)
        if (json.orderRate && Array.isArray(json.orderRate.labels) && Array.isArray(json.orderRate.data)) {
          initOrderRateChart(json.orderRate.labels, json.orderRate.data);
        } else {
          const monthlyData = json.monthlyOrders || [0,0,0,0,0,0,0,0,0,0,0,0];
          const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
          initOrderRateChart(months, monthlyData);
        }

        const popularMenus = json.popularMenus || [];
        initPopularFoodChart(popularMenus);

        // Compute simple growth for year view if data available
        const dataForGrowth = (json.orderRate && json.orderRate.data) ? json.orderRate.data : (json.monthlyOrders || []);
        if (Array.isArray(dataForGrowth) && dataForGrowth.length >= 2) {
          const total = dataForGrowth.reduce((a, b) => a + b, 0);
          const prev = dataForGrowth[dataForGrowth.length - 2] || 0;
          const curr = dataForGrowth[dataForGrowth.length - 1] || 0;
          const growth = prev > 0 ? Math.round(((curr - prev) / prev) * 100) : (curr > 0 ? 100 : 0);
          $('#performancePercent').text((growth >= 0 ? '+' : '') + growth + '%');
        }
      })
      .catch(err => console.error('Error loading chart data:', err));
  }

  function loadDashboard() {
    fetch('<?= site_url('dashboard/restaurant/data') ?>')
      .then(r => r.json())
      .then(json => {
        const dailyRevenue = Number(json.metrics.dailyRevenue || 0);
        const income = dailyRevenue * 0.8;
        const expense = dailyRevenue * 0.2;

        $('#totalRevenue').text('P' + dailyRevenue.toFixed(2));
        $('#totalIncome').text('P' + income.toFixed(2));
        $('#totalExpense').text('P' + expense.toFixed(2));

        const recentOrders = json.recentOrders || [];
        renderRecentOrders(recentOrders);

        const menuTable = document.querySelector('#menuTable tbody');
        menuTable.innerHTML = '';

        const menuList = json.menuItems || [];
        if (menuList.length > 0) {
          document.getElementById('noMenu').classList.add('d-none');
          menuList.forEach(item => {
            const isAvailable = Number(item.availability ?? item.is_available ?? 1) === 1;
            const availBadge = isAvailable
              ? '<span class="badge bg-success">Available</span>'
              : '<span class="badge bg-danger">Unavailable</span>';
            const row = document.createElement('tr');
            row.innerHTML = `
              <td>${item.name}</td>
              <td>P${parseFloat(item.price || 0).toFixed(2)}</td>
              <td>${availBadge}</td>
            `;
            menuTable.appendChild(row);
          });
        } else {
          document.getElementById('noMenu').classList.remove('d-none');
        }

        loadRestaurantChartData();
      })
      .catch(err => console.error(err));
  }

  function refreshRestaurantLiveOrders() {
    fetch('<?= site_url('dashboard/restaurant/data') ?>')
      .then(r => r.json())
      .then(json => {
        const recentOrders = json.recentOrders || [];
        renderRecentOrders(recentOrders);

        const breakdown = json.metrics || {};
        const dailyRevenue = Number(breakdown.dailyRevenue || 0);
        const income = dailyRevenue * 0.8;
        const expense = dailyRevenue * 0.2;

        $('#totalRevenue').text('P' + dailyRevenue.toFixed(2));
        $('#totalIncome').text('P' + income.toFixed(2));
        $('#totalExpense').text('P' + expense.toFixed(2));

        loadRestaurantChartData();
      })
      .catch(err => console.error(err));
  }

  function resizeDashboardCharts() {
    if (orderRateChart) {
      orderRateChart.resize();
    }
    if (popularFoodChart) {
      popularFoodChart.resize();
    }
  }

  $('#refreshBtn').on('click', loadDashboard);

  $(document).ready(function () {
    loadDashboard();
    setInterval(loadDashboard, 15000);

    // Wire timeframe selector for order rate
    const tfSelect = document.getElementById('orderRateTimeframe');
    if (tfSelect) {
      tfSelect.addEventListener('change', () => loadRestaurantChartData(tfSelect.value));
    }
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
      refreshRestaurantLiveOrders();
    });
  })();
</script>
<?= $this->endSection() ?>
