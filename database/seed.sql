-- =========================================================
-- Seed Data: ผู้ใช้งานเริ่มต้น (Quick Start)
-- =========================================================
-- ค่าเริ่มต้น:
--   admin / admin123  → สิทธิ์ admin  (ใช้งานได้ทุกฟีเจอร์)
--   user  / user123   → สิทธิ์ user   (ดูข้อมูลได้อย่างเดียว)
-- *** ต้องเปลี่ยนรหัสผ่านทันทีหลังเข้าใช้งานครั้งแรก ***
--
-- หากต้องการสร้างผู้ใช้ด้วยรหัสผ่านของคุณเอง ให้รัน:
--   php database/create_user.php <username> <password> [display_name] [admin|user]

-- ---------- Admin ----------
INSERT INTO users (username, password_hash, display_name, role, is_active)
VALUES (
    'admin',
    '$2y$10$/X5NdRuboDlpy766SfQpNO8nlk7DN6fa/wUohz53A015DgL8U1Yfq',
    'Administrator',
    'admin',
    1
)
ON DUPLICATE KEY UPDATE username = username;

-- ---------- User (read-only) ----------
INSERT INTO users (username, password_hash, display_name, role, is_active)
VALUES (
    'user',
    '$2y$10$KvYaqy1SjFycnb5j1IpoIulCqKgGvSh4cJYmFU5Kiw/kVqtWaSwAq',
    'ผู้ใช้ทั่วไป',
    'user',
    1
)
ON DUPLICATE KEY UPDATE username = username;
