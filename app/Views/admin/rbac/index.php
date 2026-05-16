<?php
    $roleNames = array_map(static fn (array $role): array => [
        'id' => (int) $role['id'],
        'name' => (string) $role['name'],
        'scope' => (string) $role['scope'],
    ], $roles ?? []);
    $activeTab = (string) ($activeTab ?? 'roles');
?>
<?= $this->extend('layouts/dashboard') ?>

<?php $this->setVar('pageTitle', 'Role & User Management — FoodDash'); ?>

<?php
    $flashSuccess = session()->getFlashdata('success');
    $flashError = session()->getFlashdata('error');
?>

<?= $this->section('content') ?>
<div class="fd-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="m-0"><?= $activeTab === 'users' ? 'User Management' : 'Role Management' ?></h3>
            <small class="text-muted"><?= $activeTab === 'users' ? 'Add, edit, and deactivate staff accounts.' : 'Create a custom role and choose its permissions.' ?></small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (!empty($canManageRoles)): ?><a href="<?= site_url('admin/rbac?tab=roles') ?>" class="btn btn-sm <?= $activeTab === 'roles' ? 'btn-primary' : 'btn-outline-dark' ?>">Role Management</a><?php endif; ?>
            <?php if (!empty($canManageStaffAccounts)): ?><a href="<?= site_url('admin/rbac?tab=users') ?>" class="btn btn-sm <?= $activeTab === 'users' ? 'btn-primary' : 'btn-outline-dark' ?>">User Management</a><?php endif; ?>
        </div>
    </div>
</div>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?= esc($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger"><?= esc($flashError) ?></div>
<?php endif; ?>

<?php if (!empty($canManageRoles) && $activeTab === 'roles'): ?>
<div class="row g-4" id="roleSection">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Create Custom Role</h5>
                        <small class="text-muted">Add a new role and choose the permissions it should have.</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="resetRoleForm()">New Role</button>
                </div>

                <form method="post" action="<?= site_url('admin/rbac/roles/save') ?>" id="roleForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="role_id" id="role_id" value="0">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Role name</label>
                            <input type="text" name="name" id="role_name" class="form-control" placeholder="Kitchen Staff" required>
                            <input type="hidden" name="scope" id="role_scope_input" value="restaurant">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="role_description" class="form-control" rows="2" placeholder="Describe what this role can do"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Permissions</label>
                            <div class="border rounded-3 p-3">
                                <div class="row g-3">
                                    <?php foreach ($permissionGroups as $module => $permissions): ?>
                                        <div class="col-12">
                                            <div class="small text-uppercase text-muted mb-2"><?= esc($module) ?></div>
                                            <div class="row g-2">
                                                <?php foreach ($permissions as $permission): ?>
                                                    <div class="col-md-6">
                                                        <div class="border rounded-3 p-2 h-100">
                                                            <div class="form-check m-0">
                                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= esc($permission['permission_key']) ?>" id="perm_<?= esc($permission['permission_key']) ?>">
                                                                <label class="form-check-label fw-semibold" for="perm_<?= esc($permission['permission_key']) ?>"><?= esc($permission['label']) ?></label>
                                                                <small class="d-block text-muted mt-1"><?= esc($permission['description'] ?? '') ?></small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Role</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetRoleForm()">Clear</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($canManageStaffAccounts) && $activeTab === 'users'): ?>
<div class="row g-4 mt-1" id="userSection">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
                    <div>
                        <h5 class="card-title mb-1">Manage Users</h5>
                        <small class="text-muted">Add, edit, and deactivate staff accounts.</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="resetUserForm()">New User</button>
                </div>

                <form method="post" action="<?= site_url('admin/rbac/users/save') ?>" id="userForm" class="mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" id="user_id" value="0">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="name" id="user_name" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" id="user_email" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Username</label><input type="text" name="username" id="user_username" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Password</label><input type="password" name="password" id="user_password" class="form-control" placeholder="Required for new users"></div>
                        <div class="col-md-4"><label class="form-label">Role</label><select name="role_id" id="user_role_id" class="form-select" required><option value="">Choose role</option><?php foreach ($roles as $role): ?><option value="<?= (int) $role['id'] ?>" data-scope="<?= esc($role['scope']) ?>"><?= esc($role['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4" id="restaurantFieldWrap"><label class="form-label">Assigned Restaurant</label><select name="restaurant_id" id="user_restaurant_id" class="form-select"><option value="0">Not applicable</option><?php foreach ($restaurants as $restaurant): ?><option value="<?= (int) $restaurant['id'] ?>"><?= esc($restaurant['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="is_active" id="user_is_active" value="1" checked><label class="form-check-label" for="user_is_active">Active account</label></div></div>
                        <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-primary">Save User</button><button type="button" class="btn btn-outline-secondary" onclick="resetUserForm()">Clear</button></div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Email</th><th>Username</th><th>Role</th><th>Restaurant</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><div class="fw-semibold"><?= esc($user['name'] ?: 'Unnamed user') ?></div><small class="text-muted">#<?= (int) $user['id'] ?></small></td>
                                    <td><?= esc($user['email']) ?></td>
                                    <td><?= esc($user['username'] ?? '-') ?></td>
                                    <td><span class="badge bg-secondary"><?= esc($user['role_name'] ?? ucfirst((string) ($user['role'] ?? ''))) ?></span></td>
                                    <td><?= esc($user['restaurant_name'] ?? '-') ?></td>
                                    <td><?php if ((int) ($user['is_active'] ?? 0) === 1): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-danger">Inactive</span><?php endif; ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" onclick="editUser(<?= htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8') ?>)">Edit</button>
                                            <?php if (($user['role_slug'] ?? '') !== 'admin' && strtolower($user['role'] ?? '') !== 'admin'): ?>
                                                <form method="post" action="<?= site_url('admin/rbac/users/toggle-status/' . (int) $user['id']) ?>" onsubmit="return confirm('Update this account status?');"><?= csrf_field() ?><button type="submit" class="btn btn-outline-dark"><?= (int) $user['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function resetRoleForm() {
        document.getElementById('roleForm').reset();
        document.getElementById('role_id').value = 0;
        document.getElementById('role_scope_input').value = 'restaurant';
        document.querySelectorAll('#roleForm input[type="checkbox"]').forEach(el => el.checked = false);
    }

    function editRole(role) {
        document.getElementById('role_id').value = role.id || 0;
        document.getElementById('role_name').value = role.name || '';
        document.getElementById('role_description').value = role.description || '';
        document.getElementById('role_scope_input').value = role.scope || 'restaurant';
        const selected = new Set(Array.isArray(role.permissions) ? role.permissions.map(v => String(v).toLowerCase()) : []);
        document.querySelectorAll('#roleForm input[type="checkbox"]').forEach(el => el.checked = selected.has(String(el.value).toLowerCase()));
        document.getElementById('role_name').focus();
    }

    function resetUserForm() {
        document.getElementById('userForm').reset();
        document.getElementById('user_id').value = 0;
        document.getElementById('user_is_active').checked = true;
        document.getElementById('user_password').placeholder = "Required for new users";
        toggleRestaurantField();
    }

    function editUser(user) {
        document.getElementById('user_id').value = user.id || 0;
        document.getElementById('user_name').value = user.name || '';
        document.getElementById('user_email').value = user.email || '';
        document.getElementById('user_username').value = user.username || '';
        document.getElementById('user_password').value = '';
        document.getElementById('user_password').placeholder = "Leave blank to keep current password";
        document.getElementById('user_role_id').value = user.role_id || '';
        document.getElementById('user_restaurant_id').value = user.restaurant_id || 0;
        document.getElementById('user_is_active').checked = Number(user.is_active) === 1;
        toggleRestaurantField();
        document.getElementById('user_name').focus();
    }

    function toggleRestaurantField() {
        const select = document.getElementById('user_role_id');
        const selected = select.options[select.selectedIndex];
        const scope = selected ? selected.dataset.scope : '';
        const wrapper = document.getElementById('restaurantFieldWrap');
        wrapper.style.display = scope === 'restaurant' ? '' : 'none';
        if (scope !== 'restaurant') {
            document.getElementById('user_restaurant_id').value = 0;
        }
    }

    document.getElementById('user_role_id').addEventListener('change', toggleRestaurantField);
    toggleRestaurantField();

</script>
<?= $this->endSection() ?>