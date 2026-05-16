<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Unauthorized Access — FoodDash'); ?>

<?= $this->section('content') ?>
<div class="d-flex align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="card shadow-sm border-0" style="max-width: 720px; width: 100%;">
        <div class="card-body p-4 p-md-5 text-center">
            <div class="display-6 mb-3">Access restricted</div>
            <h3 class="mb-3">Unauthorized access</h3>
            <p class="text-muted mb-4"><?= esc($message ?? 'You do not have permission to view this page.') ?></p>
            <a href="<?= site_url('dashboard/admin') ?>" class="btn btn-primary me-2">Back to Dashboard</a>
            <a href="<?= site_url('logout') ?>" class="btn btn-outline-secondary">Logout</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>