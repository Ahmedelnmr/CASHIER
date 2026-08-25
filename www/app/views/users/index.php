<!-- Users Management View -->
<?php
$toastSuccess = $_SESSION['toast_success'] ?? '';
$toastError = $_SESSION['toast_error'] ?? '';
unset($_SESSION['toast_success'], $_SESSION['toast_error']);
$roleLabels = ['admin' => 'مدير', 'manager' => 'مشرف', 'cashier' => 'كاشير'];
$roleBadges = ['admin' => 'badge-danger', 'manager' => 'badge-warning', 'cashier' => 'badge-info'];
?>

<div class="page-header">
    <div>
        <h1>إدارة المستخدمين</h1>
        <p class="subtitle">إضافة وتعديل مستخدمي النظام</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addUserModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        إضافة مستخدم
    </button>
</div>

<?php if ($toastSuccess): ?>
<script>showToast('<?= htmlspecialchars($toastSuccess) ?>', 'success');</script>
<?php endif; ?>
<?php if ($toastError): ?>
<script>showToast('<?= htmlspecialchars($toastError) ?>', 'error');</script>
<?php endif; ?>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>اسم المستخدم</th>
                    <th>الصلاحية</th>
                    <th>الحالة</th>
                    <th>تاريخ الإنشاء</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><span class="badge <?= $roleBadges[$user['role']] ?? 'badge-info' ?>"><?= $roleLabels[$user['role']] ?? $user['role'] ?></span></td>
                    <td><?= $user['is_active'] ? '<span class="badge badge-success">نشط</span>' : '<span class="badge badge-danger">معطل</span>' ?></td>
                    <td class="text-muted fs-sm"><?= $user['created_at'] ?></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline" onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>', '<?= $user['role'] ?>', <?= $user['is_active'] ?>)">تعديل</button>
                            <button class="btn btn-sm btn-outline" onclick="openChangePassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')">كلمة المرور</button>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="?page=users&action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">حذف</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3>إضافة مستخدم جديد</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST" action="?page=users&action=store">
            <div class="modal-body">
                <div class="form-group">
                    <label>الاسم الكامل *</label>
                    <input type="text" name="name" class="form-control" required placeholder="اسم المستخدم">
                </div>
                <div class="form-group">
                    <label>اسم المستخدم *</label>
                    <input type="text" name="username" class="form-control" required placeholder="اسم الدخول" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>كلمة المرور *</label>
                    <input type="password" name="password" class="form-control" required minlength="4" placeholder="كلمة المرور" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label>الصلاحية</label>
                    <select name="role" class="form-control">
                        <option value="cashier">كاشير</option>
                        <option value="manager">مشرف</option>
                        <option value="admin">مدير</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">إلغاء</button>
                <button type="submit" class="btn btn-primary">إضافة</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3>تعديل المستخدم</h3>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form method="POST" action="?page=users&action=update">
            <input type="hidden" name="id" id="edit_user_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>الاسم الكامل</label>
                    <input type="text" name="name" id="edit_user_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>الصلاحية</label>
                    <select name="role" id="edit_user_role" class="form-control">
                        <option value="cashier">كاشير</option>
                        <option value="manager">مشرف</option>
                        <option value="admin">مدير</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" id="edit_user_active" value="1"> نشط
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal-overlay" id="changePasswordModal">
    <div class="modal">
        <div class="modal-header">
            <h3>تغيير كلمة المرور — <span id="pw_user_name"></span></h3>
            <button class="modal-close" onclick="closeModal('changePasswordModal')">&times;</button>
        </div>
        <form method="POST" action="?page=users&action=changePassword">
            <input type="hidden" name="user_id" id="pw_user_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>كلمة المرور الجديدة</label>
                    <input type="password" name="new_password" class="form-control" required minlength="4" placeholder="كلمة المرور الجديدة" autocomplete="new-password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('changePasswordModal')">إلغاء</button>
                <button type="submit" class="btn btn-primary">تغيير</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, name, role, isActive) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_user_name').value = name;
    document.getElementById('edit_user_role').value = role;
    document.getElementById('edit_user_active').checked = isActive == 1;
    openModal('editUserModal');
}

function openChangePassword(userId, userName) {
    document.getElementById('pw_user_id').value = userId;
    document.getElementById('pw_user_name').textContent = userName;
    openModal('changePasswordModal');
}
</script>
