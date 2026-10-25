<?php
/**
 * helpers.php
 * ฟังก์ชันตัวช่วยส่วนกลาง (Global Helper Functions)
 */

if (!function_exists('remove_php_extension')) {
    /**
     * ฟังก์ชันสำหรับตัดนามสกุล .php ออกจากไฟล์หรือ URL Path
     * ตัวอย่าง:
     *   remove_php_extension('stock.php') => 'stock'
     *   remove_php_extension('/sales/create.php') => '/sales/create'
     *   remove_php_extension('index.php?url=stock/bundle') => 'stock/bundle'
     */
    function remove_php_extension(string $path): string
    {
        // 1. แยก query string ออกมาก่อน
        $query = '';
        if (($pos = strpos($path, '?')) !== false) {
            $query = substr($path, $pos);
            $path = substr($path, 0, $pos);
        }

        // 2. ถ้าส่งรูปแบบ index.php?url=xxx ให้ถอดเอาค่า url ออกมา
        if (preg_match('/index\.php\?url=([^&]+)/', $path . $query, $m)) {
            $clean = urldecode($m[1]);
            return trim($clean, '/');
        }

        // 3. ตัด .php ออกจากปลาย path
        $clean = preg_replace('/\.php$/i', '', $path);

        // 4. ตัด index.php ออกจาก path
        $clean = preg_replace('#(^|/)index\.php$#i', '', $clean);

        return $clean . $query;
    }
}

if (!function_exists('clean_url')) {
    /**
     * ทำความสะอาด URL ให้เป็น Clean URL ไม่มี .php หรือ index.php?url=
     */
    function clean_url(string $path): string
    {
        $query = '';
        if (($pos = strpos($path, '?')) !== false) {
            $query = substr($path, $pos);
            $path = substr($path, 0, $pos);
        }

        // จัดการกรณี index.php?url=
        if (preg_match('/index\.php\?url=([^&]+)/', $path . $query, $matches)) {
            $path = urldecode($matches[1]);
            // ลบ url parameter ออกจาก query string
            $query = preg_replace('/(\?|&)url=[^&]*(&|$)/', '$1', $query);
            $query = rtrim($query, '?&');
        }

        // ตัด .php และ /index.php ออก
        $path = preg_replace('/\.php$/i', '', $path);
        $path = preg_replace('#(^|/)index\.php$#i', '', $path);

        $clean = trim($path, '/');
        return $clean . ($query !== '' ? (str_starts_with($query, '?') ? $query : '?' . $query) : '');
    }
}

if (!function_exists('url')) {
    /**
     * สร้าง URL สำหรับนำไปใช้ในเว็บ (Clean URL ไม่มี .php)
     *
     * @param string $path เส้นทาง เช่น 'stock', 'sales/create', 'purchase/detail/1'
     * @param array $params Query parameters เช่น ['edit' => 1]
     * @return string URL เต็ม เช่น '/storemangaweb/public/sales/create?edit=1'
     */
    function url(string $path = '', array $params = []): string
    {
        // ถ้าเป็น External URL ให้ส่งคืนทันที
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            if (!empty($params)) {
                $path .= (str_contains($path, '?') ? '&' : '?') . http_build_query($params);
            }
            return $path;
        }

        $clean = clean_url($path);

        $queryString = '';
        if (!empty($params)) {
            $queryString = (str_contains($clean, '?') ? '&' : '?') . http_build_query($params);
        }

        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        $finalPath = ltrim($clean, '/');

        if ($finalPath === '') {
            $root = $baseUrl !== '' ? $baseUrl : '';
            return ($root !== '' ? $root : '/') . ($queryString !== '' ? $queryString : '');
        }

        return ($baseUrl !== '' ? $baseUrl : '') . '/' . $finalPath . $queryString;
    }
}

if (!function_exists('asset')) {
    /**
     * สร้าง URL สำหรับ static assets (CSS, JS, Uploads)
     */
    function asset(string $path): string
    {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        return ($baseUrl !== '' ? $baseUrl : '') . '/' . ltrim($path, '/');
    }
}
