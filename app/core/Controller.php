<?php
/**
 * Base Controller
 */
abstract class Controller
{
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

    protected function redirect(string $url): void
    {
        header('Location: ' . BASE_URL . '/' . ltrim($url, '/'));
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
}
