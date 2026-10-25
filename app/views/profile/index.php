<div class="page-title">
    <i class="bi bi-person-gear"></i>
    <div>
        <h3 class="mb-0">ข้อมูลส่วนตัว & รหัสผ่าน</h3>
        <span class="subtitle">จัดการข้อมูลบัญชีผู้ใช้และตั้งค่าความปลอดภัย</span>
    </div>
</div>

<div class="row g-4">
    <!-- Card: ข้อมูลส่วนตัว -->
    <div class="col-12 col-lg-6">
        <div class="card p-4 h-100">
            <div class="panel-title mb-3">
                <i class="bi bi-person-vcard text-primary"></i> ข้อมูลบัญชีผู้ใช้
            </div>

            <div class="d-flex align-items-center gap-3 p-3 mb-4 rounded bg-light border">
                <div class="user-avatar" style="width:48px;height:48px;font-size:1.3rem;border-radius:12px;background:linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;">
                    <?= mb_strtoupper(mb_substr($user['display_name'] ?? 'U', 0, 1, 'UTF-8')) ?>
                </div>
                <div>
                    <div class="fw-bold fs-6"><?= htmlspecialchars($user['display_name']) ?></div>
                    <div class="text-muted small">@<?= htmlspecialchars($user['username']) ?></div>
                    <div class="mt-1">
                        <span class="role-badge role-<?= htmlspecialchars($user['role']) ?>">
                            <?= $user['role'] === 'admin' ? 'ผู้ดูแลระบบ (Admin)' : 'ผู้ใช้ทั่วไป (User)' ?>
                        </span>
                    </div>
                </div>
            </div>

            <form method="POST" action="<?= url('profile/updateProfile') ?>">
                <div class="mb-3">
                    <label class="form-label">ชื่อผู้ใช้ (Username)</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                    <div class="form-text text-muted">ชื่อผู้ใช้ใช้สำหรับการเข้าสู่ระบบ ไม่สามารถเปลี่ยนแปลงได้</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">ชื่อที่แสดง (Display Name) <span class="text-danger">*</span></label>
                    <input type="text" name="display_name" class="form-control"
                           value="<?= htmlspecialchars($user['display_name']) ?>" required>
                    <div class="form-text text-muted">ชื่อที่ปรากฏในแถบเมนูและบันทึกรายการ</div>
                </div>

                <div class="mb-3 text-muted small">
                    <div><i class="bi bi-calendar-check me-1"></i> สมาชิกตั้งแต่: <?= htmlspecialchars($user['created_at'] ?? '—') ?></div>
                    <?php if (!empty($user['last_login_at'])): ?>
                    <div class="mt-1"><i class="bi bi-clock-history me-1"></i> เข้าสู่ระบบล่าสุด: <?= htmlspecialchars($user['last_login_at']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> บันทึกข้อมูลส่วนตัว
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Card: เปลี่ยนรหัสผ่าน -->
    <div class="col-12 col-lg-6">
        <div class="card p-4 h-100">
            <div class="panel-title mb-3">
                <i class="bi bi-shield-lock text-primary"></i> เปลี่ยนรหัสผ่าน
            </div>

            <form method="POST" action="<?= url('profile/updatePassword') ?>">
                <div class="mb-3">
                    <label class="form-label">รหัสผ่านปัจจุบัน <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="current_password" id="current_password" class="form-control" required autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary toggle-pwd" data-target="current_password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="new_password" id="new_password" class="form-control" minlength="4" required autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary toggle-pwd" data-target="new_password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text text-muted">ความยาวอย่างน้อย 4 ตัวอักษร</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">ยืนยันรหัสผ่านใหม่ <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="4" required autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary toggle-pwd" data-target="confirm_password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-4">
                    <i class="bi bi-info-circle me-2 fs-5"></i>
                    <div>เมื่อเปลี่ยนรหัสผ่านเรียบร้อย สามารถใช้รหัสผ่านใหม่สำหรับการเข้าสู่ระบบครั้งถัดไปได้ทันที</div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-pwd').forEach(btn => {
    btn.addEventListener('click', function () {
        const targetId = this.dataset.target;
        const input = document.getElementById(targetId);
        if (!input) return;
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });
});
</script>
