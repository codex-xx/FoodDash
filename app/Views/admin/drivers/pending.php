<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Approve Drivers — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12">
    <div>
      <h3 class="m-0">Pending Driver Approvals</h3>
      <small class="text-muted">Review and approve new driver registrations</small>
    </div>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-body">
    <?php if (!empty($drivers)): ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Driver Name</th>
              <th>Email</th>
              <th>Contact Number</th>
              <th>Vehicle Type</th>
              <th>License Number</th>
              <th>Applied On</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($drivers as $driver): ?>
              <tr>
                <td><strong><?= esc($driver['name'] ?? 'N/A') ?></strong></td>
                <td><?= esc($driver['email'] ?? 'N/A') ?></td>
                <td><?= esc($driver['phone'] ?? 'N/A') ?></td>
                <td><?= esc($driver['vehicle_type'] ?? 'N/A') ?></td>
                <td><?= esc($driver['license_number'] ?? 'N/A') ?></td>
                <td><?= !empty($driver['created_at']) ? date('M d, Y', strtotime($driver['created_at'])) : 'N/A' ?></td>
                <td>
                  <button class="btn btn-sm btn-success" onclick="approveDriver(<?= $driver['id'] ?>)">Approve</button>
                  <button class="btn btn-sm btn-danger" onclick="rejectDriver(<?= $driver['id'] ?>)">Reject</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center py-5 text-muted">
        <h5>No pending approvals</h5>
        <small>All driver registrations have been reviewed</small>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="row mb-3">
  <div class="col-12">
    <div>
      <h4 class="m-0">Approved Drivers History</h4>
      <small class="text-muted">List of drivers already approved by admin</small>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <?php if (!empty($approvedDrivers)): ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Driver Name</th>
              <th>Email</th>
              <th>Contact Number</th>
              <th>Vehicle Type</th>
              <th>License Number</th>
              <th>Approved On</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($approvedDrivers as $driver): ?>
              <tr>
                <td><strong><?= esc($driver['name'] ?? 'N/A') ?></strong></td>
                <td><?= esc($driver['email'] ?? 'N/A') ?></td>
                <td><?= esc($driver['phone'] ?? 'N/A') ?></td>
                <td><?= esc($driver['vehicle_type'] ?? 'N/A') ?></td>
                <td><?= esc($driver['license_number'] ?? 'N/A') ?></td>
                <td><?= !empty($driver['updated_at']) ? date('M d, Y', strtotime($driver['updated_at'])) : 'N/A' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center py-5 text-muted">
        <h5>No approved drivers yet</h5>
        <small>Approved drivers will appear here as history.</small>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  function approveDriver(driverId) {
    if (!confirm('Approve this driver?')) return;
    
    fetch(`<?= site_url('admin/drivers') ?>/${driverId}/approve`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => r.json())
    .then(json => {
      alert(json.message || 'Driver approved');
      location.reload();
    })
    .catch(err => alert('Error: ' + err));
  }

  function rejectDriver(driverId) {
    if (!confirm('Reject this driver?')) return;
    
    fetch(`<?= site_url('admin/drivers') ?>/${driverId}/reject`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => r.json())
    .then(json => {
      alert(json.message || 'Driver rejected');
      location.reload();
    })
    .catch(err => alert('Error: ' + err));
  }
</script>
<?= $this->endSection() ?>
