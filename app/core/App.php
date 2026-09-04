<?php
/**
 * App (Router)
 * รูปแบบ URL: index.php?url=controller/action/param1/param2
 * ตัวอย่าง: index.php?url=purchase/create
 *
 * Authorization rules:
 *  - ทุก request ต้อง login ก่อน (ยกเว้น AuthController สำหรับ login/logout)
 *  - HTTP method ที่เป็นการแก้ไขข้อมูล (POST/PUT/PATCH/DELETE) ต้องเป็น admin เท่านั้น
 */
class App
{
    protected string $controllerName = 'Dashboard';
    protected string $action = 'index';
    protected array $params = [];

    /** Controllers ที่อนุญาตให้เข้าถึงโดยไม่ต้อง login (public) */
    protected array $publicControllers = ['Login'];

    public function __construct()
    {
        $url = $this->parseUrl();

        // Controller
        if (!empty($url[0])) {
            $controllerFile = APP_PATH . '/controllers/' . ucfirst($url[0]) . 'Controller.php';
            if (file_exists($controllerFile)) {
                $this->controllerName = ucfirst($url[0]);
                unset($url[0]);
            }
        }

        $isPublic = in_array($this->controllerName, $this->publicControllers, true);

        // ---------- Authorization gate ----------
        if (!$isPublic) {
            // ต้อง login ก่อน
            if (empty($_SESSION['user_id'])) {
                $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
                $this->setFlash('warning', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
                $this->redirectTo(BASE_URL . '/index.php?url=login');
            }

            // ถ้าเป็น write method ต้องเป็น admin เท่านั้น
            $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
            $writeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
            if (in_array($method, $writeMethods, true)) {
                $role = $_SESSION['role'] ?? '';
                if ($role !== 'admin') {
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
        // ---------- /Authorization gate ----------

        require APP_PATH . '/controllers/' . $this->controllerName . 'Controller.php';
        $controllerClass = $this->controllerName . 'Controller';
        $controller = new $controllerClass();

        // Action
        if (!empty($url[1])) {
            if (method_exists($controller, $url[1])) {
                $this->action = $url[1];
                unset($url[1]);
            }
        }

        // Params
        $this->params = $url ? array_values($url) : [];

        call_user_func_array([$controller, $this->action], $this->params);
    }

    protected function parseUrl(): array
    {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            return explode('/', $url);
        }
        return [];
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
