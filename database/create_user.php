<?php
/**
 * create_user.php (CLI)
 * สร้างผู้ใช้ใหม่เข้า database พร้อม hash password ที่ปลอดภัย
 *
 * ใช้งาน:
 *   php database/create_user.php <username> <password> [display_name] [admin|user]
 *
 * ตัวอย่าง:
 *   php database/create_user.php admin MyStrongPass123 "ผู้ดูแลระบบ" admin
 *   php database/create_user.php viewer ViewerPass "ผู้ดูข้อมูล" user
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script must be run from CLI.');
}

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_PATH . '/core/Model.php';
require_once APP_PATH . '/models/UserModel.php';

$argv = $argv ?? [];
array_shift($argv); // remove script name

$username    = trim($argv[0] ?? '');
$password    = (string) ($argv[1] ?? '');
$displayName = trim($argv[2] ?? '');
$role        = strtolower(trim($argv[3] ?? 'user'));

if ($username === '' || $password === '') {
    echo "Usage: php database/create_user.php <username> <password> [display_name] [admin|user]\n";
    exit(1);
}

if (!preg_match('/^[A-Za-z0-9_.\-]{3,50}$/', $username)) {
    echo "Error: username ต้องเป็น A-Z, 0-9, _, ., - ความยาว 3-50 ตัวอักษร\n";
    exit(1);
}

if (strlen($password) < 6) {
    echo "Error: password ต้องยาวอย่างน้อย 6 ตัวอักษร\n";
    exit(1);
}

if (!in_array($role, ['admin', 'user'], true)) {
    echo "Error: role ต้องเป็น 'admin' หรือ 'user' เท่านั้น\n";
    exit(1);
}

if ($displayName === '') {
    $displayName = $username;
}

try {
    $userModel = new UserModel();

    // เช็คว่ามี user นี้อยู่แล้วหรือไม่
    if ($userModel->findByUsername($username)) {
        echo "Error: มี username '{$username}' อยู่ในระบบแล้ว\n";
        exit(1);
    }

    $id = $userModel->createUser($username, $password, $displayName, $role);

    echo "✓ สร้างผู้ใช้สำเร็จ\n";
    echo "  ID          : {$id}\n";
    echo "  Username    : {$username}\n";
    echo "  Display Name: {$displayName}\n";
    echo "  Role        : {$role}\n";
    exit(0);
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
