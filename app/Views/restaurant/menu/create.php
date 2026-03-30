<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Add Menu Item — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row">
  <div class="col-lg-6 offset-lg-3">
    <div class="mb-4">
      <h3 class="m-0">Add Menu Item</h3>
      <small class="text-muted">Create a new menu item for your restaurant</small>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <form id="menuForm" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Item Name *</label>
            <input type="text" class="form-control" name="name" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Category</label>
            <select class="form-select" name="category">
              <option value="">Select Category</option>
              <option value="Drinks">Drinks</option>
              <option value="Meals">Meals</option>
              <option value="Desserts">Desserts</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Price *</label>
            <input type="number" class="form-control" name="price" step="0.01" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" class="form-control" name="image" accept="image/*">
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="is_available" value="1" checked>
            <label class="form-check-label">Available</label>
          </div>

          <button type="submit" class="btn btn-primary">Create Item</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  $('#menuForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?= site_url('menu/store') ?>', {
      method: 'POST',
      body: formData
    })
    .then(async (response) => {
      const rawText = await response.text();
      let payload = {};

      try {
        payload = rawText ? JSON.parse(rawText) : {};
      } catch (parseError) {
        payload = { raw: rawText };
      }

      if (response.ok && payload.success) {
        alert(payload.message || 'Item created');
        window.location.href = '<?= site_url('menu') ?>';
        return;
      }

      let err = payload.error || payload.message || payload.detail || payload.title || '';
      if (!err && payload.messages && typeof payload.messages === 'object') {
        err = Object.values(payload.messages).flat().join('\n');
      }
      if (typeof err === 'object' && err !== null) {
        err = Object.values(err).flat().join('\n');
      }
      if (!err && payload.raw) {
        err = 'Server returned an unexpected response.';
      }
      if (!err) {
        err = 'Request failed (' + response.status + ')';
      }

      throw new Error(err);
    })
    .catch(err => alert('Error: ' + (err.message || err)));
  });
</script>
<?= $this->endSection() ?>
