<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Manage Menu — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12">
    <div>
      <h3 class="m-0">Menu Management</h3>
      <small class="text-muted">Add, edit, and manage your menu items</small>
    </div>
  </div>
</div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title m-0">Your Menu Items</h5>
          <a href="<?= site_url('menu/create') ?>" class="btn btn-sm btn-primary">+ Add Item</a>
        </div>
        <?php if (!empty($items)): ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Item Name</th>
                  <th>Price</th>
                  <th>Description</th>
                  <th>Availability</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                  <tr>
                    <td>
                      <?php if (!empty($item['image'])): ?>
                        <img src="<?= base_url($item['image']) ?>" alt="" style="width:50px;height:50px;object-fit:cover;margin-right:8px;vertical-align:middle">
                      <?php endif; ?>
                      <strong><?= $item['name'] ?></strong><br>
                      <small class="text-muted"><?= esc($item['category']) ?></small>
                    </td>
                    <td>₱<?= number_format($item['price'], 2) ?></td>
                    <td><?= substr($item['description'] ?? '', 0, 50) ?>...</td>
                    <td>
                      <button class="btn btn-sm btn-<?= $item['is_available'] ? 'success' : 'danger' ?>" onclick="toggleAvailability(<?= $item['id'] ?>)">
                        <?= $item['is_available'] ? 'Available' : 'Unavailable' ?>
                      </button>
                    </td>
                    <td><?= date('M d, Y', strtotime($item['created_at'])) ?></td>
                    <td>
                      <a href="<?= site_url('menu/' . $item['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                      <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(<?= $item['id'] ?>)">Delete</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-5 text-muted">
            <h5>No menu items</h5>
            <small><a href="<?= site_url('menu/create') ?>">Create your first menu item</a></small>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  function toggleAvailability(itemId) {
    fetch(`<?= site_url('menu') ?>/${itemId}/toggle`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => r.json())
    .then(json => {
      location.reload();
    })
    .catch(err => alert('Error: ' + err));
  }

  function deleteItem(itemId) {
    if (!confirm('Are you sure you want to delete this item?')) return;
    
    fetch(`<?= site_url('menu') ?>/${itemId}/delete`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => r.json())
    .then(json => {
      alert(json.message || 'Item deleted');
      location.reload();
    })
    .catch(err => alert('Error: ' + err));
  }
</script>
<?= $this->endSection() ?>
