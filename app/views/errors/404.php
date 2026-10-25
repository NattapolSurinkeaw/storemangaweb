<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
<title>404 — ไม่พบหน้าที่ต้องการ | <?= htmlspecialchars(defined('APP_NAME') ? APP_NAME : 'Manga Stock') ?></title>
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
    width: 100%;
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
    background: #fee2e2;
    color: #b91c1c;
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
  <div class="err-icon"><i class="bi bi-compass"></i></div>
  <h2 class="mb-2">404 — ไม่พบหน้าที่ต้องการ</h2>
  <p class="text-muted mb-4">
    ขออภัย ไม่พบเส้นทาง URL ที่คุณเรียกใช้ในระบบ<br>
    หน้าเว็บอาจถูกย้าย หรือพิมพ์ที่อยู่ไม่ถูกต้อง
  </p>
  <div class="d-flex justify-content-center gap-2">
    <a href="<?= url('dashboard') ?>" class="btn btn-primary">
      <i class="bi bi-house-door me-1"></i> กลับหน้า Dashboard
    </a>
    <a href="javascript:history.back()" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
    </a>
  </div>
</div>

</body>
</html>
