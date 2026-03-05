<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Manage Users — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12">
    <div>
      <h3 class="m-0">User Management</h3>
      <small class="text-muted">View and manage all platform users</small>
    </div>
  </div>
</div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                  <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= $user['email'] ?></td>
                    <td><span class="badge bg-secondary"><?= ucfirst($user['role']) ?></span></td>
                    <td>
                      <?php if ($user['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                      <?php else: ?>
                        <span class="badge bg-danger">Suspended</span>
                      <?php endif; ?>
                    </td>
                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                      <?php if ((int) session()->get('user_id') !== (int) $user['id']): ?>
                        <?php if ($user['is_active']): ?>
                          <button class="btn btn-sm btn-danger" onclick="suspendUser(<?= $user['id'] ?>)">Suspend</button>
                        <?php else: ?>
                          <button class="btn btn-sm btn-success" onclick="activateUser(<?= $user['id'] ?>)">Activate</button>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">No users found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  function suspendUser(userId) {
    if (!confirm('Are you sure you want to suspend this user?')) return;
    
    fetch(`<?= site_url('admin/users') ?>/${userId}/suspend`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => r.json())
    .then(json => {
      alert(json.message || 'User suspended');
      location.reload();
    })
    .catch(err => alert('Error: ' + err));
  }

  function activateUser(userId) {
    if (!confirm('Are you sure you want to activate this user?')) return;
    
    fetch(`<?= site_url('admin/users') ?>/${userId}/activate`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => r.json())
    .then(json => {
      alert(json.message || 'User activated');
      location.reload();
    })
    .catch(err => alert('Error: ' + err));
  }
</script>
<?= $this->endSection() ?>
