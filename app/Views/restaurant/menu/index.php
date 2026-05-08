<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Manage Menu — FoodDash'); ?>

<?= $this->section('content') ?>
<?php
$totalItems = count($items ?? []);
$availableItems = 0;

foreach (($items ?? []) as $menuItem) {
    if (!empty($menuItem['is_available'])) {
        $availableItems++;
    }
}

$unavailableItems = $totalItems - $availableItems;
$archivedItems = $archivedItems ?? [];
$archivedCount = count($archivedItems);

$itemsByCategory = [];
foreach (($items ?? []) as $menuItem) {
  $categoryName = trim((string) ($menuItem['category'] ?? ''));
  if ($categoryName === '') {
    $categoryName = 'Uncategorized';
  }

  if (!array_key_exists($categoryName, $itemsByCategory)) {
    $itemsByCategory[$categoryName] = [];
  }

  $itemsByCategory[$categoryName][] = $menuItem;
}

ksort($itemsByCategory, SORT_NATURAL | SORT_FLAG_CASE);
$categoryCount = count($itemsByCategory);
?>

<style>
  .menu-page-header {
    border: 1px solid rgba(58, 63, 69, 0.14);
    border-left: 4px solid var(--fd-primary);
    border-radius: .85rem;
    background: linear-gradient(120deg, rgba(255, 255, 255, 0.96), rgba(243, 211, 154, 0.14));
    padding: 1rem 1.25rem;
  }

  .menu-page-header h3 {
    letter-spacing: .01em;
  }

  .menu-stat-card {
    border: 1px solid rgba(58, 63, 69, 0.16);
    border-radius: .75rem;
    background: #fff;
    padding: .9rem 1rem;
    height: 100%;
  }

  .menu-stat-label {
    text-transform: uppercase;
    letter-spacing: .08em;
    font-size: .72rem;
    color: #6B7280;
    display: block;
    margin-bottom: .2rem;
  }

  .menu-stat-value {
    font-size: 1.45rem;
    margin: 0;
    color: #1F2937;
  }

  .menu-grid-card .card-body {
    padding: 1rem;
  }

  .menu-product-grid {
    margin-top: .15rem;
  }

  .menu-category-section {
    border-top: 1px solid rgba(58, 63, 69, 0.12);
    padding-top: 1.1rem;
    margin-top: 1.1rem;
  }

  .menu-category-section:first-child {
    border-top: 0;
    margin-top: 0;
    padding-top: 0;
  }

  .menu-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    margin-bottom: .65rem;
  }

  .menu-category-title {
    margin: 0;
    font-size: 1rem;
    color: #111827;
    letter-spacing: .01em;
  }

  .menu-category-count {
    display: inline-flex;
    align-items: center;
    border: 1px solid rgba(58, 63, 69, 0.2);
    border-radius: 999px;
    background: #F8F9FA;
    color: #4B5563;
    font-size: .7rem;
    padding: .22rem .62rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    font-weight: 600;
  }

  .menu-product-card {
    border: 1px solid rgba(58, 63, 69, 0.16);
    border-radius: .95rem;
    background: #FFFFFF;
    box-shadow: 0 10px 24px rgba(31, 41, 55, 0.06);
    padding: .9rem;
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease, background .22s ease;
  }

  .menu-image-wrap {
    position: relative;
    border-radius: .8rem;
    border: 1px solid rgba(58, 63, 69, 0.18);
    background: linear-gradient(180deg, #FFFFFF, #F9FAFB);
    overflow: hidden;
    aspect-ratio: 4 / 3;
    min-height: 190px;
    padding: .35rem .45rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color .22s ease, background .22s ease;
  }

  .menu-product-image {
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    object-position: center;
    transition: transform .28s ease;
  }

  .menu-item-placeholder {
    color: #6B7280;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 600;
  }

  .menu-product-body {
    display: flex;
    flex-direction: column;
    gap: .6rem;
    margin-top: .85rem;
    height: 100%;
  }

  .menu-item-name {
    font-size: 1.04rem;
    color: #111827;
    margin: 0;
    line-height: 1.3;
    transition: color .22s ease;
  }

  .menu-category-badge {
    display: inline-flex;
    align-items: center;
    border: 1px solid rgba(58, 63, 69, 0.2);
    background-color: #F8F9FA;
    color: #4B5563;
    border-radius: 999px;
    font-size: .67rem;
    padding: .2rem .55rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-weight: 600;
  }

  .menu-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
  }

  .menu-description-text {
    color: #374151;
    font-size: .9rem;
    line-height: 1.45;
    margin: 0;
    min-height: 2.6em;
  }

  .menu-status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .74rem;
    font-weight: 600;
    border-radius: 999px;
    padding: .32rem .62rem;
    letter-spacing: .04em;
    text-transform: uppercase;
    transition: transform .2s ease;
  }

  .menu-status-pill.available {
    background: rgba(22, 163, 74, 0.12);
    color: #166534;
    border: 1px solid rgba(22, 163, 74, 0.24);
  }

  .menu-status-pill.unavailable {
    background: rgba(220, 38, 38, 0.11);
    color: #991B1B;
    border: 1px solid rgba(220, 38, 38, 0.2);
  }

  .menu-price-block {
    border: 1px solid rgba(58, 63, 69, 0.12);
    border-radius: .65rem;
    background: #F9FAFB;
    padding: .5rem .65rem;
  }

  .menu-price-block .current-price {
    font-weight: 700;
    color: #111827;
    line-height: 1.1;
    font-size: 1.03rem;
  }

  .menu-date {
    color: #374151;
    white-space: nowrap;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-weight: 600;
  }

  .menu-card-actions {
    margin-top: auto;
    display: grid;
    gap: .45rem;
  }

  .menu-row-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .45rem;
  }

  .menu-card-actions .btn {
    transition: transform .18s ease, box-shadow .18s ease;
  }

  @media (hover: hover) and (pointer: fine) {
    .menu-product-card:hover {
      transform: translateY(-6px) scale(1.03);
      box-shadow: 0 16px 34px rgba(31, 41, 55, 0.14);
      border-color: rgba(22, 163, 74, 0.58);
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(22, 163, 74, 0.12));
    }

    .menu-product-card:hover .menu-image-wrap {
      border-color: rgba(22, 163, 74, 0.46);
      background: linear-gradient(180deg, #FFFFFF, rgba(22, 163, 74, 0.10));
    }

    .menu-product-card:hover .menu-product-image {
      transform: scale(1.035);
    }

    .menu-product-card:hover .menu-item-name {
      color: #0F172A;
    }

    .menu-product-card:hover .menu-status-pill {
      transform: translateX(2px);
    }

    .menu-card-actions .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 14px rgba(31, 41, 55, 0.14);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .menu-product-card,
    .menu-image-wrap,
    .menu-product-image,
    .menu-item-name,
    .menu-status-pill,
    .menu-card-actions .btn {
      transition: none;
    }
  }

  @media (max-width: 767.98px) {
    .menu-item-name {
      font-size: .96rem;
    }

    .menu-image-wrap {
      min-height: 165px;
    }

    .menu-grid-card .card-body {
      padding: .85rem;
    }

    .menu-product-card {
      padding: .8rem;
    }

    .menu-row-actions {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="menu-page-header mb-4">
  <h3 class="m-0">Menu Management</h3>
  <small class="text-muted">Manage item pricing, availability, and presentation with better visibility.</small>
</div>

<div class="row g-3 mb-4">
  <div class="col-12 col-md-6 col-xl-3">
    <div class="menu-stat-card">
      <small class="menu-stat-label">Total Items</small>
      <p class="menu-stat-value"><?= $totalItems ?></p>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="menu-stat-card">
      <small class="menu-stat-label">Available</small>
      <p class="menu-stat-value text-success"><?= $availableItems ?></p>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="menu-stat-card">
      <small class="menu-stat-label">Unavailable</small>
      <p class="menu-stat-value text-danger"><?= $unavailableItems ?></p>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="menu-stat-card">
      <small class="menu-stat-label">Categories</small>
      <p class="menu-stat-value text-primary"><?= $categoryCount ?></p>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="menu-stat-card">
      <small class="menu-stat-label">Archived</small>
      <p class="menu-stat-value text-secondary"><?= $archivedCount ?></p>
    </div>
  </div>
</div>

<div class="card shadow-sm menu-grid-card">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-2 p-md-3 mb-3">
      <h5 class="card-title m-0">Your Menu Items</h5>
      <div class="flex-grow-1 mx-lg-4" style="max-width: 400px; min-width: 200px;">
        <input type="search" id="menuSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Search products by name or description...">
      </div>
      <a href="<?= site_url('menu/create') ?>" class="btn btn-sm btn-primary px-3">+ Add Menu Item</a>
    </div>

    <?php if (!empty($items)): ?>
      <?php foreach ($itemsByCategory as $categoryName => $categoryItems): ?>
        <section class="menu-category-section">
          <div class="menu-category-header">
            <h6 class="menu-category-title"><?= esc($categoryName) ?></h6>
            <span class="menu-category-count"><?= count($categoryItems) ?> item<?= count($categoryItems) === 1 ? '' : 's' ?></span>
          </div>

          <div class="row g-3 menu-product-grid">
            <?php foreach ($categoryItems as $item): ?>
              <?php
                $description = trim((string) ($item['description'] ?? ''));
                if (strlen($description) > 95) {
                    $description = substr($description, 0, 92) . '...';
                }
                $createdAt = !empty($item['created_at']) ? strtotime($item['created_at']) : false;
                $itemCategory = trim((string) ($item['category'] ?? ''));
                if ($itemCategory === '') {
                    $itemCategory = 'Uncategorized';
                }
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <article class="menu-product-card">
                  <div class="menu-image-wrap">
                    <?php if (!empty($item['image'])): ?>
                      <img src="<?= base_url($item['image']) ?>" alt="<?= esc($item['name']) ?>" class="menu-product-image">
                    <?php else: ?>
                      <div class="menu-item-placeholder">No Image</div>
                    <?php endif; ?>
                  </div>

                  <div class="menu-product-body">
                    <h6 class="menu-item-name fw-semibold"><?= esc($item['name']) ?></h6>

                    <div class="menu-meta-row">
                      <span class="menu-category-badge"><?= esc($itemCategory) ?></span>
                      <span class="menu-date"><?= $createdAt ? date('M d, Y', $createdAt) : 'N/A' ?></span>
                    </div>

                    <div class="menu-price-block">
                      <div class="current-price">₱<?= number_format($item['price'], 2) ?></div>
                    </div>

                    <p class="menu-description-text">
                      <?php if ($description !== ''): ?>
                        <?= esc($description) ?>
                      <?php else: ?>
                        <span class="text-muted">No description provided.</span>
                      <?php endif; ?>
                    </p>

                    <div class="menu-card-actions">
                      <div>
                        <span class="menu-status-pill <?= !empty($item['is_available']) ? 'available' : 'unavailable' ?>">
                          <?= !empty($item['is_available']) ? 'Available' : 'Unavailable' ?>
                        </span>
                      </div>

                      <button class="btn btn-sm btn-outline-secondary w-100" onclick="toggleAvailability(<?= $item['id'] ?>)">
                        <?= !empty($item['is_available']) ? 'Mark Unavailable' : 'Mark Available' ?>
                      </button>

                      <div class="menu-row-actions">
                        <a href="<?= site_url('menu/' . $item['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary w-100">Edit</a>
                        <button class="btn btn-sm btn-outline-danger w-100" onclick="deleteItem(<?= $item['id'] ?>)">Archive</button>
                      </div>
                    </div>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="text-center py-5 text-muted">
        <h5 class="mb-2">No menu items yet</h5>
        <small><a href="<?= site_url('menu/create') ?>">Create your first menu item</a></small>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($archivedItems)): ?>
  <div class="card shadow-sm mt-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <h5 class="card-title m-0">Archived Menu Items</h5>
        <small class="text-muted">Restore items to make them active again.</small>
      </div>

      <div class="row g-3">
        <?php foreach ($archivedItems as $item): ?>
          <?php
            $itemCategory = trim((string) ($item['category'] ?? ''));
            if ($itemCategory === '') {
                $itemCategory = 'Uncategorized';
            }
          ?>
          <div class="col-12 col-md-6 col-xl-4">
            <article class="menu-product-card">
              <div class="menu-image-wrap">
                <?php if (!empty($item['image'])): ?>
                  <img src="<?= base_url($item['image']) ?>" alt="<?= esc($item['name']) ?>" class="menu-product-image">
                <?php else: ?>
                  <div class="menu-item-placeholder">No Image</div>
                <?php endif; ?>
              </div>

              <div class="menu-product-body">
                <h6 class="menu-item-name fw-semibold"><?= esc($item['name']) ?></h6>
                <div class="menu-meta-row">
                  <span class="menu-category-badge"><?= esc($itemCategory) ?></span>
                  <span class="menu-status-pill unavailable">Archived</span>
                </div>
                <div class="menu-price-block">
                  <div class="current-price">₱<?= number_format($item['price'], 2) ?></div>
                </div>
                <button class="btn btn-sm btn-outline-success w-100" onclick="restoreItem(<?= $item['id'] ?>)">Restore</button>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

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
    if (!confirm('Archive this item? You can restore it later.')) return;
    
    fetch(`<?= site_url('menu') ?>/${itemId}/delete`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => r.json())
    .then(json => {
      alert(json.message || 'Item archived');
      location.reload();
    })
    .catch(err => alert('Error: ' + err));
  }

  function restoreItem(itemId) {
    fetch(`<?= site_url('menu') ?>/${itemId}/restore`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => r.json())
    .then(json => {
      alert(json.message || 'Item restored');
      location.reload();
    })
    .catch(err => alert('Error: ' + err));
  }

  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('menuSearchInput');
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const categories = document.querySelectorAll('.menu-category-section');

        categories.forEach(category => {
          let hasVisibleItems = false;
          const items = category.querySelectorAll('.menu-product-grid > div');

          items.forEach(item => {
            const nameEl = item.querySelector('.menu-item-name');
            const descEl = item.querySelector('.menu-description-text');
            const name = nameEl ? nameEl.textContent.toLowerCase() : '';
            const desc = descEl ? descEl.textContent.toLowerCase() : '';
            
            if (name.includes(searchTerm) || desc.includes(searchTerm)) {
              item.style.display = '';
              hasVisibleItems = true;
            } else {
              item.style.display = 'none';
            }
          });

          if (hasVisibleItems) {
            category.style.display = '';
          } else {
            category.style.display = 'none';
          }
        });
      });
    }
  });
</script>
<?= $this->endSection() ?>
