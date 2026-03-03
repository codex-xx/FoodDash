<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Order History — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12">
    <div>
      <h3 class="m-0">Order History</h3>
      <small class="text-muted">View all completed and cancelled orders</small>
    </div>
  </div>
</div>

<!-- Date Filter Section -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h5 class="card-title mb-3"><i class="bi bi-calendar-range"></i> Filter by Date</h5>
    <form method="get" action="<?= site_url('orders/history') ?>" class="row g-3 align-items-end">
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
        <a href="<?= site_url('orders/history') ?>" class="btn btn-outline-secondary w-100 mt-2">
          Clear Filters
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Summary Cards -->
<?php 
  $totalOrders = count($orders);
  $completedOrders = count(array_filter($orders, fn($o) => $o['status'] === 'completed'));
  $cancelledOrders = count(array_filter($orders, fn($o) => $o['status'] === 'cancelled'));
  $totalRevenue = array_sum(array_column(array_filter($orders, fn($o) => $o['status'] === 'completed'), 'total_amount'));
?>
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="text-muted mb-2">Total Orders</h6>
        <h3 class="mb-0"><?= $totalOrders ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="text-muted mb-2">Completed</h6>
        <h3 class="mb-0 text-success"><?= $completedOrders ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="text-muted mb-2">Cancelled</h6>
        <h3 class="mb-0 text-danger"><?= $cancelledOrders ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="text-muted mb-2">Total Revenue</h6>
        <h3 class="mb-0 text-success">₱<?= number_format($totalRevenue, 2) ?></h3>
      </div>
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
              <th>Prep Time</th>
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
                      'completed' => 'success',
                      'cancelled' => 'danger',
                      default => 'secondary'
                    };
                  ?>
                  <span class="badge bg-<?= $statusClass ?>"><?= ucwords(str_replace('_', ' ', $order['status'])) ?></span>
                </td>
                <td>
                  <?php if (!empty($order['estimated_preparation_time'])): ?>
                    <?= $order['estimated_preparation_time'] ?> min
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>
                <td><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-info" 
                          data-bs-toggle="modal" 
                          data-bs-target="#orderDetailModal"
                          data-order-id="<?= $order['id'] ?>"
                          data-order-number="<?= $order['order_number'] ?>"
                          data-customer="<?= htmlspecialchars($order['customer_name']) ?>"
                          data-address="<?= htmlspecialchars($order['delivery_address'] ?? 'N/A') ?>"
                          data-status="<?= $order['status'] ?>"
                          data-amount="<?= $order['total_amount'] ?>"
                          data-date="<?= date('F d, Y h:i A', strtotime($order['created_at'])) ?>">
                    <i class="bi bi-eye"></i> View
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
        <h5 class="mt-3">No order history found</h5>
        <small>Completed and cancelled orders will appear here</small>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="orderDetailModalLabel">Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="text-muted mb-1">Order Number</label>
          <p class="fw-bold" id="detail_order_number"></p>
        </div>
        <div class="mb-3">
          <label class="text-muted mb-1">Customer Name</label>
          <p id="detail_customer"></p>
        </div>
        <div class="mb-3">
          <label class="text-muted mb-1">Delivery Address</label>
          <p id="detail_address"></p>
        </div>
        <div class="mb-3">
          <label class="text-muted mb-1">Status</label>
          <p><span id="detail_status_badge"></span></p>
        </div>
        <div class="mb-3">
          <label class="text-muted mb-1">Total Amount</label>
          <p class="fw-bold fs-5" id="detail_amount"></p>
        </div>
        <div class="mb-3">
          <label class="text-muted mb-1">Order Date</label>
          <p id="detail_date"></p>
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
<script>
  // Handle order detail modal
  const orderDetailModal = document.getElementById('orderDetailModal');
  orderDetailModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    
    document.getElementById('detail_order_number').textContent = button.getAttribute('data-order-number');
    document.getElementById('detail_customer').textContent = button.getAttribute('data-customer');
    document.getElementById('detail_address').textContent = button.getAttribute('data-address');
    document.getElementById('detail_amount').textContent = '₱' + parseFloat(button.getAttribute('data-amount')).toFixed(2);
    document.getElementById('detail_date').textContent = button.getAttribute('data-date');
    
    const status = button.getAttribute('data-status');
    const statusClass = status === 'completed' ? 'success' : 'danger';
    const statusText = status.charAt(0).toUpperCase() + status.slice(1);
    document.getElementById('detail_status_badge').innerHTML = `<span class="badge bg-${statusClass}">${statusText}</span>`;
  });
</script>
<?= $this->endSection() ?>
