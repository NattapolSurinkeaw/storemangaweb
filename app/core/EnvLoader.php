<?php
/**
 * EnvLoader
 * Custom Simple .env Loader (ไม่พึ่ง composer / vlucas/phpdotenv)
 */
class EnvLoader
{
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            // ถ้าไม่มี .env ให้ fallback ไปใช้ .env.example เพื่อไม่ให้ระบบล่ม (dev only)
            $example = dirname($path) . '/.env.example';
            if (file_exists($example)) {
                $path = $example;
            } else {
                throw new RuntimeException(".env file not found at: {$path}");
            }
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // ข้าม comment และบรรทัดว่าง
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));

            // ตัดเครื่องหมาย quote ออกถ้ามี
            $value = trim($value, "\"'");

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}
