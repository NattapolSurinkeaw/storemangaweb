<?php
/**
 * UserModel
 * จัดการข้อมูลผู้ใช้งาน + ตรวจสอบ login
 */
class UserModel extends Model
{
    /**
     * ค้นหา user ด้วย username (เฉพาะคนที่ is_active = 1)
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, display_name, role, is_active, created_at, last_login_at
             FROM users
             WHERE username = :username AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * ค้นหา user ด้วย id
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, display_name, role, is_active, created_at, last_login_at
             FROM users
             WHERE id = :id AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * ค้นหา user ด้วย id พร้อม password_hash (สำหรับตรวจสอบตอนเปลี่ยนรหัสผ่าน)
     */
    public function findByIdWithPassword(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, display_name, role, is_active, created_at, last_login_at
             FROM users
             WHERE id = :id AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * ตรวจสอบ username/password แล้วคืน array ข้อมูล user (ไม่รวม password_hash) หรือ null ถ้าไม่ผ่าน
     */
    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->findByUsername($username);
        if (!$user) {
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }
        // ตรวจสอบว่า hash เก่าต้อง rehash หรือไม่ (เช่น algorithm เปลี่ยน)
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $this->db->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
            $upd->execute([':h' => $newHash, ':id' => $user['id']]);
        }

        unset($user['password_hash']);
        return $user;
    }

    /**
     * อัปเดต last_login_at
     */
    public function touchLogin(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }

    /**
     * สร้างผู้ใช้ใหม่ (ใช้จาก CLI script create_user.php)
     */
    public function createUser(string $username, string $password, string $displayName, string $role = 'user'): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, password_hash, display_name, role, is_active)
             VALUES (:u, :h, :n, :r, 1)'
        );
        $stmt->execute([
            ':u' => $username,
            ':h' => $hash,
            ':n' => $displayName,
            ':r' => $role,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * รายชื่อผู้ใช้ทั้งหมด (ไม่รวม password_hash)
     */
    public function listAll(): array
    {
        return $this->db->query(
            'SELECT id, username, display_name, role, is_active, created_at, last_login_at
             FROM users
             ORDER BY role DESC, username ASC'
        )->fetchAll();
    }

    /**
     * อัปเดตข้อมูลทั่วไปของผู้ใช้ (ชื่อที่แสดง)
     */
    public function updateProfile(int $id, string $displayName): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET display_name = :n WHERE id = :id');
        return $stmt->execute([':n' => $displayName, ':id' => $id]);
    }

    /**
     * เปลี่ยนรหัสผ่านของผู้ใช้
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
        return $stmt->execute([':h' => $hash, ':id' => $id]);
    }
}
