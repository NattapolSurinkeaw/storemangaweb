<?php
$__path = trim(Router::getRequestPath(), '/');
$__seg = $__path === '' ? 'dashboard' : explode('/', $__path)[0];
$__full = $__path === '' ? 'dashboard' : $__path;

$__user = $_SESSION['user_id'] ?? null;
$__userModel = new UserModel();
$__currentUser = $__user ? $__userModel->findById((int) $__user) : null;
$__isAdmin = $__currentUser && ($__currentUser['role'] ?? '') === 'admin';

// ซ่อนเมนูที่เป็นการ "สร้าง/จัดการ" สำหรับ user (read-only)
// - บันทึกซื้อเข้า (purchase/create) - มีฟอร์มบันทึก
// - จัดชุดขาย (stock/bundle) - มีฟอร์มสร้าง bundle
// user ยังสามารถดู purchase list (ผ่านหน้า create) และ stock list ได้
$__navItems = [
    ['url' => 'dashboard',        'icon' => 'bi-speedometer2', 'label' => 'Dashboard',      'short' => 'หน้าแรก',  'match' => 'dashboard'],
    ['url' => 'purchase/create',  'icon' => 'bi-bag-plus',     'label' => 'บันทึกซื้อเข้า',  'short' => 'ซื้อเข้า', 'match' => 'purchase', 'admin' => true],
    ['url' => 'stock',            'icon' => 'bi-boxes',        'label' => 'สต็อก',           'short' => 'สต็อก',    'match' => 'stock', 'exact' => 'stock'],
    ['url' => 'stock/bundle',     'icon' => 'bi-collection',   'label' => 'จัดชุดขาย',       'short' => 'จัดชุด',   'match' => 'stock/bundle', 'exact' => 'stock/bundle', 'admin' => true],
    ['url' => 'sales/create',     'icon' => 'bi-cash-coin',    'label' => 'บันทึกขาย',       'short' => 'ขาย',      'match' => 'sales'],
    ['url' => 'report',           'icon' => 'bi-bar-chart-line', 'label' => 'รายงานซื้อ-ขาย', 'short' => 'รายงาน',   'match' => 'report'],
];

$__navItems = array_values(array_filter($__navItems, function ($it) use ($__isAdmin) {
    if (!empty($it['admin']) && !$__isAdmin) return false;
    return true;
}));

function __manga_is_active($item, $seg, $full) {
    if (isset($item['exact'])) return $full === $item['exact'];
    return $seg === $item['match'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
<title><?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
var BASE_URL = <?= json_encode(BASE_URL) ?>;
function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
}
function escapeAttr(s) {
    return escapeHtml(s);
}
</script>
<style>
.skeleton-pulse {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: sk-glow 1.4s ease-in-out infinite;
  border-radius: 4px;
  display: inline-block;
  color: transparent !important;
  user-select: none;
}
@keyframes sk-glow {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
</head>
<body class="app-shell">

<!-- Desktop sidebar -->
<aside class="sidebar d-none d-lg-flex flex-column">
    <a class="sidebar-brand" href="<?= url('dashboard') ?>">
        <span class="seal">漫</span>
        <span class="brand-text"><?= htmlspecialchars(APP_NAME) ?><small>ระบบจัดการสต็อกมังงะ</small></span>
    </a>
    <nav class="sidebar-nav">
        <?php foreach ($__navItems as $item): ?>
            <a class="nav-link <?= __manga_is_active($item, $__seg, $__full) ? 'active' : '' ?>"
               href="<?= url($item['url']) ?>">
                <i class="bi <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($__currentUser): ?>
    <div class="sidebar-user">
        <a href="<?= url('profile') ?>" class="d-flex align-items-center gap-2 text-decoration-none text-reset flex-grow-1 min-w-0" title="ตั้งค่าข้อมูลส่วนตัว">
            <div class="user-avatar"><?= mb_strtoupper(mb_substr($__currentUser['display_name'] ?? 'U', 0, 1, 'UTF-8')) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($__currentUser['display_name']) ?></div>
                <div class="user-role">
                    <span class="role-badge role-<?= htmlspecialchars($__currentUser['role']) ?>">
                        <?= $__currentUser['role'] === 'admin' ? 'Admin' : 'ผู้ใช้ทั่วไป' ?>
                    </span>
                </div>
            </div>
        </a>
        <a class="btn-icon" href="<?= url('profile') ?>" title="แก้ไขข้อมูลส่วนตัว" aria-label="แก้ไขข้อมูลส่วนตัว">
            <i class="bi bi-gear"></i>
        </a>
        <a class="btn-icon" href="<?= url('logout') ?>" title="ออกจากระบบ" aria-label="ออกจากระบบ">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
    <?php endif; ?>

    <div class="sidebar-foot">&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?></div>
</aside>

<!-- Mobile offcanvas nav -->
<div class="offcanvas offcanvas-start mobile-nav" tabindex="-1" id="mobileNav">
    <div class="offcanvas-header">
        <span class="brand-text" style="font-family:var(--font-display);font-weight:700;color:#fff;">
            <span class="seal" style="display:inline-flex;">漫</span> <?= htmlspecialchars(APP_NAME) ?>
        </span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="sidebar-nav">
            <?php foreach ($__navItems as $item): ?>
                <a class="nav-link <?= __manga_is_active($item, $__seg, $__full) ? 'active' : '' ?>"
                   href="<?= url($item['url']) ?>">
                    <i class="bi <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
                </a>
            <?php endforeach; ?>
            <a class="nav-link <?= $__seg === 'profile' ? 'active' : '' ?>"
               href="<?= url('profile') ?>">
                <i class="bi bi-person-gear"></i> ข้อมูลส่วนตัว
            </a>
        </nav>

        <?php if ($__currentUser): ?>
        <div class="mobile-user-bar">
            <a href="<?= url('profile') ?>" class="user-info text-decoration-none">
                <div class="user-name"><?= htmlspecialchars($__currentUser['display_name']) ?></div>
                <div class="user-role">
                    <span class="role-badge role-<?= htmlspecialchars($__currentUser['role']) ?>">
                        <?= $__currentUser['role'] === 'admin' ? 'Admin' : 'ผู้ใช้ทั่วไป' ?>
                    </span>
                </div>
            </a>
            <a class="btn btn-sm btn-outline-light me-1" href="<?= url('profile') ?>" title="ตั้งค่าข้อมูล">
                <i class="bi bi-gear"></i>
            </a>
            <a class="btn btn-sm btn-outline-light" href="<?= url('logout') ?>" title="ออกจากระบบ">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="app-main">
    <!-- Mobile topbar -->
    <header class="topbar d-lg-none">
        <button class="menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-label="เมนู">
            <i class="bi bi-list" style="font-size:1.3rem;"></i>
        </button>
        <a class="brand-text" href="<?= url('dashboard') ?>">
            <span class="seal">漫</span> <?= htmlspecialchars(APP_NAME) ?>
        </a>
        <?php if ($__currentUser): ?>
            <div class="d-flex align-items-center gap-1">
                <a class="topbar-user" href="<?= url('profile') ?>" title="ข้อมูลส่วนตัว">
                    <i class="bi bi-person-circle"></i>
                </a>
                <a class="topbar-user" href="<?= url('logout') ?>" title="ออกจากระบบ">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        <?php else: ?>
            <span style="width:38px;"></span>
        <?php endif; ?>
    </header>

    <div class="content-area">
        <?php if (!empty($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
