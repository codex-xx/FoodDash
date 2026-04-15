<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Admin Order History — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h3 class="m-0">Delivered Orders</h3>
      <small class="text-muted">Delivered and cancelled orders across all restaurants</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-primary" id="refreshHistoryBtn">Refresh Data</button>
    </div>
  </div>
</div>

<section class="mb-4">
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <h5 class="card-title m-0">Delivery History</h5>
          <small class="text-muted">Delivered and cancelled orders across all restaurants</small>
        </div>
        <div class="d-flex gap-2">
          <select id="historyStatusFilter" class="form-select form-select-sm">
            <option value="">All history</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <input id="historySearch" type="search" class="form-control form-control-sm" placeholder="Search history">
        </div>
      </div>

      <div class="table-responsive">
        <table id="historyTable" class="table table-striped table-hover table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Restaurant</th>
              <th>Driver</th>
              <th>Status</th>
              <th>Amount</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  function statusBadge(status) {
    const map = {
      delivered: '<span class="badge bg-success">Delivered</span>',
      cancelled: '<span class="badge bg-danger">Cancelled</span>'
    };
    return map[status] || '<span class="badge bg-secondary">' + status + '</span>';
  }

  function loadHistory() {
    fetch('<?= site_url('dashboard/admin/orders/data') ?>?scope=history')
      .then(r => r.json())
      .then(json => {
        const historyBody = document.querySelector('#historyTable tbody');
        historyBody.innerHTML = '';

        (json.orders || []).forEach(order => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td><strong>${order.order_number}</strong></td>
            <td>${order.customer_name || '-'}</td>
            <td>${order.restaurant_name || '-'}</td>
            <td>${order.rider_name || order.driver_name || 'No driver accepts'}</td>
            <td>${statusBadge(order.status)}</td>
            <td>₱${parseFloat(order.total_amount || 0).toFixed(2)}</td>
            <td>${order.created_at ? new Date(order.created_at).toLocaleDateString() : '-'}</td>
          `;
          historyBody.appendChild(row);
        });
      })
      .catch(err => console.error(err));
  }

  $('#historySearch').on('keyup', function () {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#historyTable tbody tr').forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(val) ? '' : 'none';
    });
  });

  $('#historyStatusFilter').on('change', function () {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#historyTable tbody tr').forEach(row => {
      const status = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
      row.style.display = !val || status.includes(val) ? '' : 'none';
    });
  });

  $('#refreshHistoryBtn').on('click', loadHistory);

  $(document).ready(function () {
    loadHistory();
  });
</script>
<?= $this->endSection() ?>