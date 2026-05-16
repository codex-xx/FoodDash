<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'MFA Settings - FoodDash'); ?>

<?= $this->section('content') ?>
<div class="fd-page-header mb-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h3 class="m-0">MFA Settings</h3>
      <small class="text-muted">Enable or disable email-based login verification for the platform</small>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('dashboard/admin') ?>" class="btn btn-sm btn-outline-secondary">Back to Dashboard</a>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-8 col-xl-6">
    <div class="card shadow-sm border-0">
      <div class="card-body p-4">
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('error')) ?>
          </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
          <div class="alert alert-success">
            <?= esc(session()->getFlashdata('success')) ?>
          </div>
        <?php endif; ?>

        <div class="mb-3">
          <h5 class="card-title mb-1">Email OTP MFA</h5>
          <p class="text-muted mb-0">When enabled, users must complete email verification after entering a valid password.</p>
        </div>

        <form method="post" action="<?= site_url('dashboard/admin/mfa') ?>">
          <?= csrf_field(); ?>
          <div class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-3 border mb-4">
            <div>
              <div class="fw-semibold">MFA Status</div>
              <small class="text-muted">Currently <?= $mfaEnabled ? 'enabled' : 'disabled' ?></small>
            </div>
            <div class="form-check form-switch m-0">
              <input class="form-check-input" type="checkbox" role="switch" id="mfa_enabled" name="mfa_enabled" value="1" <?= $mfaEnabled ? 'checked' : '' ?>>
              <label class="form-check-label" for="mfa_enabled"><?= $mfaEnabled ? 'ON' : 'OFF' ?></label>
            </div>
          </div>

          <div class="alert alert-info border-0">
            OTP codes expire after 5 minutes and are sent through Gmail SMTP using the existing FoodDash email service.
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save MFA Setting</button>
            <a href="<?= site_url('dashboard/admin/security') ?>" class="btn btn-outline-dark">Security Monitor</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>