<?php
/**
 * config.php
 * Bootstrap: โหลด .env, ตั้งค่า constants, autoload, session
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('ROUTES_PATH', ROOT_PATH . '/routes');

require_once APP_PATH . '/core/EnvLoader.php';
EnvLoader::load(ROOT_PATH . '/.env');

define('APP_NAME', EnvLoader::get('APP_NAME', 'Manga Stock System'));
define('APP_DEBUG', filter_var(EnvLoader::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN));

// BASE_URL คำนวณจาก script path เพื่อให้ลิงก์ในเมนูถูกต้องไม่ว่าจะวางโปรเจกต์ไว้โฟลเดอร์ไหน
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
define('BASE_URL', rtrim($scriptDir, '/'));

define('SHOPEE_FEE_PERCENT', (float) EnvLoader::get('SHOPEE_FEE_PERCENT', 13));
define('FACEBOOK_FEE_PERCENT', (float) EnvLoader::get('FACEBOOK_FEE_PERCENT', 0));

// Simple autoloader สำหรับ core / models / controllers
spl_autoload_register(function ($class) {
    $dirs = ['core', 'models', 'controllers'];
    foreach ($dirs as $dir) {
        $file = APP_PATH . "/{$dir}/{$class}.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/Router.php';
require_once APP_PATH . '/core/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
