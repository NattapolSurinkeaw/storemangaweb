<?php
$__old = $old ?? [];
$__username = htmlspecialchars($__old['username'] ?? '');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เข้าสู่ระบบ — <?= htmlspecialchars(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root {
    --font-display: 'Noto Serif Thai', serif;
    --font-body: 'Noto Sans Thai', system-ui, sans-serif;
    --ink: #1f2937;
    --paper: #f6f1e7;
    --accent: #8b1e1e;
    --accent-dark: #6b1414;
    --line: #d8cfb8;
  }
  * { box-sizing: border-box; }
  html, body { height: 100%; margin: 0; }
  body {
    font-family: var(--font-body);
    color: var(--ink);
    background:
      radial-gradient(1200px 600px at 10% 10%, rgba(139, 30, 30, 0.12), transparent 60%),
      radial-gradient(1000px 500px at 90% 90%, rgba(31, 41, 55, 0.08), transparent 60%),
      linear-gradient(135deg, #fbf6ea 0%, #f1e9d2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
  }
  .login-card {
    width: 100%;
    max-width: 420px;
    background: #fffaf0;
    border: 1px solid var(--line);
    border-radius: 14px;
    box-shadow: 0 20px 50px rgba(31, 41, 55, 0.15);
    overflow: hidden;
  }
  .login-header {
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
    color: #fff;
    padding: 1.8rem 1.5rem;
    text-align: center;
  }
  .login-header .seal {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 64px; height: 64px;
    border: 2px solid rgba(255,255,255,.4);
    border-radius: 12px;
    background: rgba(255,255,255,.08);
    font-family: var(--font-display);
    font-size: 2.2rem;
    margin-bottom: .75rem;
  }
  .login-header h1 {
    font-family: var(--font-display);
    font-size: 1.4rem;
    margin: 0;
    font-weight: 700;
  }
  .login-header p {
    margin: .25rem 0 0;
    opacity: .9;
    font-size: .9rem;
  }
  .login-body {
    padding: 1.75rem 1.5rem 1.5rem;
  }
  .form-floating > label { color: #8a7c5d; }
  .form-control:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 .2rem rgba(139, 30, 30, .15);
  }
  .btn-primary {
    background: var(--accent);
    border-color: var(--accent);
  }
  .btn-primary:hover, .btn-primary:focus {
    background: var(--accent-dark);
    border-color: var(--accent-dark);
  }
  .role-hint {
    background: #fff4e0;
    border: 1px dashed #d4a83a;
    border-radius: 8px;
    padding: .6rem .75rem;
    font-size: .82rem;
    color: #6b5a25;
    margin-top: 1rem;
  }
  .role-hint strong { color: var(--accent); }
  .footer-credit {
    text-align: center;
    color: #8a7c5d;
    font-size: .8rem;
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
