<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Security Monitor - FoodDash'); ?>

<?= $this->section('content') ?>
<div class="row mb-4">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h3 class="m-0">Security Monitor</h3>
      <small class="text-muted">Track active sessions and security-sensitive user activity</small>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('dashboard/admin') ?>" class="btn btn-sm btn-outline-secondary">Back to Dashboard</a>
      <a href="<?= site_url('dashboard/admin/security/report?period=daily&format=csv') ?>" class="btn btn-sm btn-outline-success">Daily CSV</a>
      <a href="<?= site_url('dashboard/admin/security/report?period=weekly&format=pdf') ?>" class="btn btn-sm btn-outline-danger">Weekly PDF</a>
      <button class="btn btn-sm btn-primary" id="refreshSecurityBtn">Refresh</button>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-4 mb-3">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <small class="text-muted d-block">Active Sessions</small>
        <h3 class="mb-0" id="activeSessionsCount">0</h3>
      </div>
    </div>
  </div>
  <div class="col-md-4 mb-3">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <small class="text-muted d-block">Users With Active Sessions</small>
        <h3 class="mb-0" id="activeUsersCount">0</h3>
      </div>
    </div>
  </div>
  <div class="col-md-4 mb-3">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <small class="text-muted d-block">Last Refresh</small>
        <h6 class="mb-0" id="lastRefreshText">Never</h6>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <small class="text-muted d-block">Failed Logins (24h)</small>
        <h3 class="mb-0" id="failedLoginsCount">0</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <small class="text-muted d-block">Intrusion Alerts (24h)</small>
        <h3 class="mb-0" id="intrusionAlertsCount">0</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <small class="text-muted d-block">Blocked IP Events (24h)</small>
        <h3 class="mb-0" id="blockedIpsCount">0</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <small class="text-muted d-block">Vulnerabilities (24h)</small>
        <h3 class="mb-0" id="vulnerabilityCount">0</h3>
      </div>
    </div>
  </div>
</div>

<div class="alert alert-warning d-none" id="securityTablesWarning"></div>

<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title">Recent Sessions</h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm align-middle" id="recentSessionsTable">
            <thead class="table-light">
              <tr>
                <th>User Type</th>
                <th>User ID</th>
                <th>Issued</th>
                <th>Last Seen</th>
                <th>Expires</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-lg-6 mb-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h5 class="card-title">Recent Login Attempts</h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm align-middle" id="loginAttemptsTable">
            <thead class="table-light">
              <tr>
                <th>User Type</th>
                <th>User ID</th>
                <th>Result</th>
                <th>Reason</th>
                <th>Time</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6 mb-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h5 class="card-title">Account Activity Events</h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm align-middle" id="accountActivitiesTable">
            <thead class="table-light">
              <tr>
                <th>User Type</th>
                <th>User ID</th>
                <th>Activity</th>
                <th>Target</th>
                <th>Time</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-lg-6 mb-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h5 class="card-title">Recent Intrusion Alerts</h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm align-middle" id="intrusionAlertsTable">
            <thead class="table-light">
              <tr>
                <th>Type</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Count</th>
                <th>Triggered At</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6 mb-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h5 class="card-title">Active Blocked IP Entries</h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm align-middle" id="blockedIpsTable">
            <thead class="table-light">
              <tr>
                <th>IP Hash</th>
                <th>Reason</th>
                <th>Blocked At</th>
                <th>Blocked Until</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function renderRows(tableSelector, rows, rowRenderer, emptyColspan) {
    const tbody = document.querySelector(tableSelector + ' tbody');
    tbody.innerHTML = '';

    if (!Array.isArray(rows) || rows.length === 0) {
      tbody.innerHTML = `<tr><td colspan="${emptyColspan}" class="text-center text-muted">No data available.</td></tr>`;
      return;
    }

    rows.forEach(row => {
      const tr = document.createElement('tr');
      tr.innerHTML = rowRenderer(row);
      tbody.appendChild(tr);
    });
  }

  function badgeForSession(row) {
    if (row.revoked_at) {
      return '<span class="badge bg-danger">Revoked</span>';
    }

    if (row.expires_at && new Date(row.expires_at) < new Date()) {
      return '<span class="badge bg-secondary">Expired</span>';
    }

    return '<span class="badge bg-success">Active</span>';
  }

  function badgeForLogin(result) {
    return Number(result) === 1
      ? '<span class="badge bg-success">Success</span>'
      : '<span class="badge bg-danger">Failed</span>';
  }

  function updateTablesWarning(tables) {
    const warn = document.getElementById('securityTablesWarning');
    const missing = [];

    if (!tables.auth_tokens) missing.push('auth_tokens');
    if (!tables.login_activities) missing.push('login_activities');
    if (!tables.user_activity_logs) missing.push('user_activity_logs');
    if (!tables.audit_logs) missing.push('audit_logs');
    if (!tables.intrusion_alerts) missing.push('intrusion_alerts');
    if (!tables.blocked_ips) missing.push('blocked_ips');

    if (missing.length === 0) {
      warn.classList.add('d-none');
      warn.textContent = '';
      return;
    }

    warn.classList.remove('d-none');
    warn.textContent = 'Some monitoring tables are missing: ' + missing.join(', ') + '. Run migrations to enable full monitoring.';
  }

  function loadSecurityData() {
    fetch('<?= site_url('dashboard/admin/security/data') ?>')
      .then(response => response.json())
      .then(data => {
        const stats = data.sessionStats || {};
        const threatStats = data.threatStats || {};
        document.getElementById('activeSessionsCount').textContent = Number(stats.active_sessions || 0);
        document.getElementById('activeUsersCount').textContent = Number(stats.active_users || 0);
        document.getElementById('lastRefreshText').textContent = new Date().toLocaleString();
        document.getElementById('failedLoginsCount').textContent = Number(threatStats.failed_login_attempts || 0);
        document.getElementById('intrusionAlertsCount').textContent = Number(threatStats.intrusion_attempts || 0);
        document.getElementById('blockedIpsCount').textContent = Number(threatStats.blocked_ip_events || 0);
        document.getElementById('vulnerabilityCount').textContent = Number(threatStats.system_vulnerabilities_detected || 0);

        updateTablesWarning(data.tables || {});

        renderRows('#recentSessionsTable', data.recentSessions || [], (row) => {
          return `
            <td>${escapeHtml(row.user_type || '-')}</td>
            <td>${escapeHtml(row.user_id || '-')}</td>
            <td>${escapeHtml(row.issued_at || '-')}</td>
            <td>${escapeHtml(row.last_seen_at || '-')}</td>
            <td>${escapeHtml(row.expires_at || '-')}</td>
            <td>${badgeForSession(row)}</td>
          `;
        }, 6);

        renderRows('#loginAttemptsTable', data.loginAttempts || [], (row) => {
          return `
            <td>${escapeHtml(row.user_type || '-')}</td>
            <td>${escapeHtml(row.user_id || '-')}</td>
            <td>${badgeForLogin(row.success)}</td>
            <td>${escapeHtml(row.failure_reason || '-')}</td>
            <td>${escapeHtml(row.login_at || row.created_at || '-')}</td>
          `;
        }, 5);

        renderRows('#accountActivitiesTable', data.accountActivities || [], (row) => {
          const target = [row.target_type, row.target_id].filter(Boolean).join(' #') || '-';
          return `
            <td>${escapeHtml(row.user_type || '-')}</td>
            <td>${escapeHtml(row.user_id || '-')}</td>
            <td>${escapeHtml(row.activity_type || '-')}</td>
            <td>${escapeHtml(target)}</td>
            <td>${escapeHtml(row.created_at || '-')}</td>
          `;
        }, 5);

        renderRows('#intrusionAlertsTable', data.recentAlerts || [], (row) => {
          return `
            <td>${escapeHtml(row.alert_type || '-')}</td>
            <td><span class="badge ${row.severity === 'critical' ? 'bg-danger' : (row.severity === 'high' ? 'bg-warning text-dark' : 'bg-info text-dark')}">${escapeHtml(row.severity || '-')}</span></td>
            <td>${escapeHtml(row.status || '-')}</td>
            <td>${escapeHtml(row.trigger_count || 0)}</td>
            <td>${escapeHtml(row.triggered_at || '-')}</td>
          `;
        }, 5);

        renderRows('#blockedIpsTable', data.activeBlocks || [], (row) => {
          return `
            <td>${escapeHtml(row.ip_address_hash || '-')}</td>
            <td>${escapeHtml(row.reason || '-')}</td>
            <td>${escapeHtml(row.blocked_at || '-')}</td>
            <td>${escapeHtml(row.blocked_until || 'manual')}</td>
          `;
        }, 4);
      })
      .catch(() => {
        alert('Failed to load security monitoring data.');
      });
  }

  document.getElementById('refreshSecurityBtn').addEventListener('click', loadSecurityData);

  document.addEventListener('DOMContentLoaded', () => {
    loadSecurityData();
    setInterval(loadSecurityData, 15000);
  });
</script>
<?= $this->endSection() ?>
