<?php
/**
 * Base Controller
 */
abstract class Controller
{
    /**
     * ผู้ใช้ที่ล็อกอินอยู่ (array) หรือ null ถ้ายังไม่ล็อกอิน
     */
    protected ?array $currentUser = null;

    public function __construct()
    {
        // โหลดข้อมูลผู้ใช้จาก session (ถ้ามี) เพื่อให้ทุก controller เรียกดูได้
        $this->currentUser = $this->loadCurrentUser();
    }

    /**
     * โหลด view จาก app/views/{path}.php พร้อมส่งข้อมูลเข้าไปเป็นตัวแปร
     */
    protected function view(string $path, array $data = []): void
    {
        extract($data);
        $viewFile = APP_PATH . '/views/' . $path . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            die("View not found: {$path}");
        }

        require APP_PATH . '/views/layouts/header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/footer.php';
    }

    /**
     * โหลด view แบบไม่มี layout (สำหรับหน้า login, error ที่ไม่ต้องการ sidebar/header)
     * ต้องส่ง $__isLoginView หรือจัดการ assets เอง
     */
    protected function viewPlain(string $path, array $data = []): void
    {
        extract($data);
        $viewFile = APP_PATH . '/views/' . $path . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            die("View not found: {$path}");
        }

        require $viewFile;
    }

    protected function redirect(string $url): void
    {
        $dest = url($url);
        header('Location: ' . $dest);
        exit;
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function isAjax(): bool
    {
        return (
            (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
            || (($_GET['ajax'] ?? '') === '1')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        );
    }

    // ---------------------------------------------------------
    // Authentication helpers
    // ---------------------------------------------------------

    /**
     * คืนค่า user ปัจจุบันจาก session หรือ null
     */
    protected function loadCurrentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $userModel = new UserModel();
        $user = $userModel->findById((int) $_SESSION['user_id']);
        if (!$user) {
            // session อ้าง user ที่ถูกลบ/ปิดใช้งาน -> ล้าง session
            $this->logout();
            return null;
        }
        return $user;
    }

    /**
     * ตรวจสอบว่าล็อกอินอยู่หรือไม่
     */
    protected function isLoggedIn(): bool
    {
        return $this->currentUser !== null;
    }

    /**
     * ตรวจสอบว่าเป็น admin หรือไม่
     */
    protected function isAdmin(): bool
    {
        return $this->currentUser !== null && ($this->currentUser['role'] ?? '') === 'admin';
    }

    /**
     * บังคับให้ล็อกอินก่อนเข้าใช้งาน ไม่งั้นเด้งไปหน้า login
     */
    protected function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
            $this->flash('warning', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
            $this->redirect('login');
        }
    }

    /**
     * บังคับให้เป็น admin เท่านั้น ไม่งั้นแสดง 403
     */
    protected function requireAdmin(): void
    {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            http_response_code(403);
            $this->viewPlain('errors/403', [
                'currentUser' => $this->currentUser,
            ]);
            exit;
        }
    }

    /**
     * ล้าง session ทั้งหมด (ใช้ตอน logout)
     */
    protected function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
