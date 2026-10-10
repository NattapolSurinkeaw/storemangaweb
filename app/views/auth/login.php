<?php
$__old = $old ?? [];
$__username = htmlspecialchars($__old['username'] ?? '');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
<title>เข้าสู่ระบบ — <?= htmlspecialchars(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root {
    --font-display: 'Plus Jakarta Sans', 'Noto Sans Thai', system-ui, sans-serif;
    --font-body: 'Noto Sans Thai', 'Plus Jakarta Sans', system-ui, sans-serif;
    --ink: #0F172A;
    --bg: #F1F5F9;
    --primary: #2563EB;
    --primary-hover: #1D4ED8;
    --border: #E2E8F0;
  }
  * { box-sizing: border-box; }
  html, body { height: 100%; margin: 0; }
  body {
    font-family: var(--font-body);
    color: var(--ink);
    background:
      radial-gradient(1000px 500px at 10% 10%, rgba(37, 99, 235, 0.08), transparent 60%),
      radial-gradient(900px 450px at 90% 90%, rgba(15, 23, 42, 0.05), transparent 60%),
      linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
  }
  .login-card {
    width: 100%;
    max-width: 420px;
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 12px 36px -4px rgba(15, 23, 42, 0.12);
    overflow: hidden;
  }
  .login-header {
    background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
    color: #FFFFFF;
    padding: 2rem 1.5rem 1.6rem;
    text-align: center;
  }
  .login-header .seal {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 58px; height: 58px;
    border-radius: 14px;
    background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
    font-family: var(--font-display);
    font-size: 1.85rem;
    font-weight: 800;
    margin-bottom: 0.85rem;
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
  }
  .login-header h1 {
    font-family: var(--font-display);
    font-size: 1.35rem;
    margin: 0;
    font-weight: 700;
  }
  .login-header p {
    margin: 0.25rem 0 0;
    color: #94A3B8;
    font-size: 0.88rem;
  }
  .login-body {
    padding: 1.75rem 1.5rem 1.5rem;
  }
  .form-floating > label { color: #64748B; font-size: 0.9rem; }
  .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
  }
  .btn-primary {
    background: var(--primary);
    border-color: var(--primary);
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
  }
  .btn-primary:hover, .btn-primary:focus {
    background: var(--primary-hover);
    border-color: var(--primary-hover);
  }
  .role-hint {
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    border-radius: 10px;
    padding: 0.75rem 0.85rem;
    font-size: 0.82rem;
    color: #1E40AF;
    margin-top: 1.15rem;
  }
  .role-hint strong { color: #1D4ED8; }
  .footer-credit {
    text-align: center;
    color: #94A3B8;
    font-size: 0.8rem;
    margin-top: 1.25rem;
  }
</style>
</head>
<body>

<div class="login-card">
  <div class="login-header">
    <span class="seal">漫</span>
    <h1><?= htmlspecialchars(APP_NAME) ?></h1>
    <p>ระบบจัดการสต็อกมังงะ</p>
  </div>

  <div class="login-body">
    <?php if (!empty($_SESSION['flash'])): ?>
      <?php foreach ($_SESSION['flash'] as $flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endforeach; ?>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/index.php?url=login" autocomplete="off" novalidate>
      <div class="form-floating mb-3">
        <input type="text" class="form-control" id="username" name="username"
               placeholder="ชื่อผู้ใช้" required autofocus
               value="<?= $__username ?>">
        <label for="username"><i class="bi bi-person-circle"></i> ชื่อผู้ใช้</label>
      </div>

      <div class="form-floating mb-3">
        <input type="password" class="form-control" id="password" name="password"
               placeholder="รหัสผ่าน" required>
        <label for="password"><i class="bi bi-shield-lock"></i> รหัสผ่าน</label>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ
      </button>
    </form>

    <div class="role-hint">
      <i class="bi bi-info-circle"></i>
      <strong>สิทธิ์ผู้ใช้งาน:</strong>
      <strong>admin</strong> ใช้งานได้ทุกฟีเจอร์ (เพิ่ม/แก้ไข/ลบ),
      <strong>user</strong> ดูข้อมูลได้อย่างเดียว (read-only)
    </div>

    <div class="footer-credit">
      &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>
    </div>
  </div>
</div>

</body>
</html>
