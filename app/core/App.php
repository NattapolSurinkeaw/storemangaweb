<?php
/**
 * App (Application Core & Dispatcher)
 * จัดการ Lifecycle ของคำขอ, เรียกใช้ Router, ตรวจสอบสิทธิ์ (Authorization), และประมวลผล Controller
 */
class App
{
    /** Controllers ที่อนุญาตให้เข้าถึงโดยไม่ต้อง login (public) */
    protected array $publicControllers = ['Login'];

    public function __construct()
    {
        // 1. ตรวจสอบและ Redirect หากเข้าถึงผ่าน URL ที่มี .php หรือ index.php?url=
        // เพื่อให้บราวเซอร์แสดงผลเป็น Clean URL โดยอัตโนมัติ
        Router::redirectIfPhp();

        // 2. โหลดไฟล์กำหนดเส้นทาง (Route file)
        $routesFile = defined('ROUTES_PATH') ? ROUTES_PATH . '/web.php' : dirname(__DIR__, 2) . '/routes/web.php';
        if (file_exists($routesFile)) {
            require_once $routesFile;
        }

        // 3. Match Route ตาม HTTP Method และ URL Path
        $matched = Router::matchRoute();
        if (!$matched) {
            $this->handleNotFound();
            return;
        }

        // 4. Authorization Gate (ตรวจสอบการล็อกอินและสิทธิ์ Admin)
        $this->checkAuthorization($matched);

        // 5. สั่งรัน Controller/Action หรือ Callable
        Router::execute($matched);
    }

    /**
     * ตรวจสอบสิทธิ์การเข้าใช้งาน
     */
    protected function checkAuthorization(array $matched): void
    {
        $controllerName = $matched['controller'] ?? '';
        $baseControllerName = str_replace('Controller', '', $controllerName);
        $options = $matched['options'] ?? [];

        // Controller หรือ Route ที่เปิดเป็น public (เช่น Login)
        $isPublic = !empty($options['public']) || in_array($baseControllerName, $this->publicControllers, true);

        if ($isPublic) {
            return;
        }

        // 1. ตรวจสอบการ Login
        if (empty($_SESSION['user_id'])) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
            $this->setFlash('warning', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
            $this->redirectTo(url('login'));
        }

        // 2. ตรวจสอบสิทธิ์ Admin สำหรับ Write Methods (POST, PUT, PATCH, DELETE)
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $writeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        if (in_array($method, $writeMethods, true)) {
            $role = $_SESSION['role'] ?? '';
            $isSelfProfile = ($baseControllerName === 'Profile');

            if ($role !== 'admin' && !$isSelfProfile) {
                http_response_code(403);

                $isAjax = (
                    (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
                    || (($_GET['ajax'] ?? '') === '1')
                    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                );

                if ($isAjax || $this->looksLikeAjaxEndpoint()) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'ไม่มีสิทธิ์: ต้องใช้บัญชี admin เท่านั้น',
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $userModel = new UserModel();
                $user = $userModel->findById((int) $_SESSION['user_id']);
                $GLOBALS['__forbiddenUser'] = $user;
                require APP_PATH . '/views/errors/403.php';
                exit;
            }
        }
    }

    /**
     * จัดการกรณีไม่พบ Route (404 Not Found)
     */
    protected function handleNotFound(): void
    {
        http_response_code(404);

        $isAjax = (
            (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
            || (($_GET['ajax'] ?? '') === '1')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        );

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => '404 Not Found: ไม่พบ Endpoint หรือหน้าที่ร้องขอ',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $errorView = APP_PATH . '/views/errors/404.php';
        if (file_exists($errorView)) {
            require $errorView;
            exit;
        }

        echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>404 ไม่พบหน้าเว็บ</title>';
        echo '<style>body{font-family:sans-serif;text-align:center;padding:80px 20px;background:#f8f9fa;color:#333;}';
        echo 'h1{font-size:3rem;color:#e63946;margin-bottom:10px;}p{font-size:1.1rem;color:#6c757d;}';
        echo 'a{display:inline-block;margin-top:20px;padding:10px 20px;background:#2b2d42;color:#fff;text-decoration:none;border-radius:6px;}</style></head>';
        echo '<body><h1>404</h1><p>ไม่พบหน้าที่คุณต้องการเรียกใช้ (Page Not Found)</p>';
        echo '<a href="' . htmlspecialchars(url('/')) . '">กลับหน้าหลัก</a></body></html>';
        exit;
    }

    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    protected function redirectTo(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * เดา endpoint ที่คืน JSON (ใช้สำหรับเช็คตอน 403)
     */
    protected function looksLikeAjaxEndpoint(): bool
    {
        $path = strtolower($_SERVER['REQUEST_URI'] ?? '');
        $ajaxHints = ['/detail', '/delete', '/calculate', '/preview', '/available', '/dissolve'];
        foreach ($ajaxHints as $hint) {
            if (str_contains($path, $hint)) return true;
        }
        return false;
    }
}
