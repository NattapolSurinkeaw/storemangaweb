<?php
/**
 * ProfileController
 * จัดการข้อมูลส่วนตัวและเปลี่ยนรหัสผ่านของผู้ใช้
 */
class ProfileController extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    /**
     * GET: แสดงหน้าจอแก้ไขข้อมูลส่วนตัวและเปลี่ยนรหัสผ่าน
     */
    public function index(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $user = $this->userModel->findById($userId);

        if (!$user) {
            $this->flash('danger', 'ไม่พบข้อมูลผู้ใช้');
            $this->redirect('dashboard');
        }

        $this->view('profile/index', [
            'user' => $user,
        ]);
    }

    /**
     * POST: แก้ไขข้อมูลชื่อที่แสดง (Display Name)
     */
    public function updateProfile(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $displayName = trim((string) $this->input('display_name', ''));

        if ($displayName === '') {
            $this->flash('danger', 'กรุณาระบุชื่อที่แสดง');
            $this->redirect('profile');
        }

        try {
            $this->userModel->updateProfile($userId, $displayName);
            $this->flash('success', 'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว');
        } catch (Throwable $e) {
            $this->flash('danger', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        $this->redirect('profile');
    }

    /**
     * POST: เปลี่ยนรหัสผ่าน
     */
    public function updatePassword(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $currentPassword = (string) $this->input('current_password', '');
        $newPassword = (string) $this->input('new_password', '');
        $confirmPassword = (string) $this->input('confirm_password', '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->flash('danger', 'กรุณากรอกข้อมูลรหัสผ่านให้ครบทุกช่อง');
            $this->redirect('profile');
        }

        if (mb_strlen($newPassword) < 4) {
            $this->flash('danger', 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 4 ตัวอักษร');
            $this->redirect('profile');
        }

        if ($newPassword !== $confirmPassword) {
            $this->flash('danger', 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน');
            $this->redirect('profile');
        }

        $user = $this->userModel->findByIdWithPassword($userId);
        if (!$user) {
            $this->flash('danger', 'ไม่พบข้อมูลผู้ใช้');
            $this->redirect('login');
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $this->flash('danger', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
            $this->redirect('profile');
        }

        try {
            $this->userModel->updatePassword($userId, $newPassword);
            $this->flash('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
        } catch (Throwable $e) {
            $this->flash('danger', 'ไม่สามารถเปลี่ยนรหัสผ่านได้: ' . $e->getMessage());
        }

        $this->redirect('profile');
    }
}
