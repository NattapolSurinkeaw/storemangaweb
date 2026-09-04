<?php $__user = $currentUser ?? null; ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>403 — ไม่มีสิทธิ์เข้าถึง</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body {
    font-family: 'Noto Sans Thai', system-ui, sans-serif;
    background: linear-gradient(135deg, #fbf6ea 0%, #f1e9d2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    color: #1f2937;
  }
  .err-card {
    max-width: 520px;
    background: #fffaf0;
    border: 1px solid #d8cfb8;
    border-radius: 14px;
    padding: 2.5rem;
    text-align: center;
    box-shadow: 0 20px 50px rgba(31, 41, 55, 0.12);
  }
  .err-icon {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: #f8d7da;
    color: #8b1e1e;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin-bottom: 1rem;
  }
</style>
</head>
<body>

<div class="err-card">
  <div class="err-icon"><i class="bi bi-shield-lock"></i></div>
  <h2 class="mb-2">403 — ไม่มีสิทธิ์เข้าถึง</h2>
  <p class="text-muted mb-4">
    คุณ<?= $__user ? ' <strong>' . htmlspecialchars($__user['display_name']) . '</strong> (' . htmlspecialchars($__user['role']) . ')' : '' ?>
    ไม่มีสิทธิ์ใช้งานส่วนนี้<br>
    ต้องใช้สิทธิ์ระดับ <strong>admin</strong> เท่านั้น
  </p>
  <a href="<?= BASE_URL ?>/index.php?url=dashboard" class="btn btn-primary">
    <i class="bi bi-arrow-left"></i> กลับหน้า Dashboard
  </a>
  <a href="<?= BASE_URL ?>/index.php?url=login/logout" class="btn btn-outline-secondary">
    <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
  </a>
</div>

</body>
</html>
