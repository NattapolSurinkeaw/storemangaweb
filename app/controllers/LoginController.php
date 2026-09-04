<?php
/**
 * LoginController
 * จัดการ Login / Logout
 *
 * Routes (URL → action):
 *   ?url=login                  → index()   GET: แสดงฟอร์ม, POST: ตรวจสอบและล็อกอิน
 *   ?url=login/logout           → logout()
 *
 * หมายเหตุ: Controller นี้ถูก bypass จาก App router (publicControllers)
 */
class LoginController extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    /**
     * GET  : แสดงฟอร์ม login — ถ้าล็อกอินอยู่แล้วเด้งไป dashboard
     * POST : ตรวจสอบ username/password แล้วเข้าสู่ระบบ
     */
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processLogin();
            return;
        }

        if ($this->isLoggedIn()) {
            $this->redirect('dashboard');
        }
        $this->showLoginForm();
    }

    /**
     * ออกจากระบบ
     */
    public function logout(): void
    {
        // ล้างและทำลาย session
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

        // เปิด session ใหม่เพื่อให้ flash ทำงาน
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->flash('info', 'ออกจากระบบเรียบร้อย');
        $this->redirect('login');
    }

    /**
     * ประมวลผล login (POST เท่านั้น)
     */
    private function processLogin(): void
    {
        $username = trim((string) $this->input('username', ''));
        $password = (string) $this->input('password', '');

        if ($username === '' || $password === '') {
            $this->flash('danger', 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
            $this->showLoginForm(['username' => $username]);
            return;
        }

        try {
            $user = $this->userModel->authenticate($username, $password);
        } catch (Throwable $e) {
            $this->flash('danger', 'ระบบไม่สามารถตรวจสอบผู้ใช้ได้ในขณะนี้');
            $this->showLoginForm(['username' => $username]);
            return;
        }

        if (!$user) {
            // หน่วงเวลาเล็กน้อย ลดการเดาสุ่มรหัส
            usleep(400000);
            $this->flash('danger', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
            $this->showLoginForm(['username' => $username]);
            return;
        }

        // ตั้ง session ใหม่ป้องกัน session fixation
        session_regenerate_id(true);
        $_SESSION['user_id']    = (int) $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['logged_in']  = true;
        $_SESSION['login_time'] = time();

        // อัปเดต last_login_at (best-effort)
        try {
            $this->userModel->touchLogin((int) $user['id']);
        } catch (Throwable $e) {
            // ไม่ให้กระทบ flow หลัก
        }

        $this->flash('success', 'เข้าสู่ระบบสำเร็จ ยินดีต้อนรับคุณ ' . $user['display_name']);

        // กลับไปยัง intended URL หรือ dashboard
        $intended = $_SESSION['intended_url'] ?? '';
        unset($_SESSION['intended_url']);

        // ป้องกัน open redirect: ต้องเป็น path ภายในเท่านั้น
        if ($intended !== '' && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
            header('Location: ' . $intended);
            exit;
        }
        $this->redirect('dashboard');
    }

    /**
     * แสดงฟอร์ม login (ใช้ view แบบไม่มี layout)
     */
    private function showLoginForm(array $old = []): void
    {
        $this->viewPlain('auth/login', [
            'old' => $old,
        ]);
    }
}
