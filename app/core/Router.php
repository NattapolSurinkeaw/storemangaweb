<?php
/**
 * Router
 * จัดการระบบ Route ของเว็บไซต์ กำหนด path การเรียกใช้ฟังก์ชันของ controller
 * และจัดการ Clean URL เพื่อให้เว็บไม่ต้องแสดงไฟล์แบบ .php
 */
class Router
{
    /** @var array เก็บรายการ Route ที่ลงทะเบียนไว้ แยกตาม HTTP method */
    protected static array $routes = [];

    /** @var array Named routes สำหรับอ้างอิงชื่อ Route */
    protected static array $namedRoutes = [];

    /** @var string Prefix ปัจจุบันสำหรับ Group */
    protected static string $groupPrefix = '';

    /** @var array Options ปัจจุบันสำหรับ Group */
    protected static array $groupOptions = [];

    /**
     * กำหนด Route สำหรับ HTTP GET
     */
    public static function get(string $path, $handler, array $options = []): void
    {
        self::addRoute(['GET'], $path, $handler, $options);
    }

    /**
     * กำหนด Route สำหรับ HTTP POST
     */
    public static function post(string $path, $handler, array $options = []): void
    {
        self::addRoute(['POST'], $path, $handler, $options);
    }

    /**
     * กำหนด Route สำหรับ HTTP PUT
     */
    public static function put(string $path, $handler, array $options = []): void
    {
        self::addRoute(['PUT'], $path, $handler, $options);
    }

    /**
     * กำหนด Route สำหรับ HTTP DELETE
     */
    public static function delete(string $path, $handler, array $options = []): void
    {
        self::addRoute(['DELETE'], $path, $handler, $options);
    }

    /**
     * กำหนด Route สำหรับ HTTP PATCH
     */
    public static function patch(string $path, $handler, array $options = []): void
    {
        self::addRoute(['PATCH'], $path, $handler, $options);
    }

    /**
     * กำหนด Route ที่รองรับหลาย HTTP Methods
     */
    public static function match(array $methods, string $path, $handler, array $options = []): void
    {
        self::addRoute(array_map('strtoupper', $methods), $path, $handler, $options);
    }

    /**
     * กำหนด Route ที่รองรับทุก HTTP Methods
     */
    public static function any(string $path, $handler, array $options = []): void
    {
        self::addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $path, $handler, $options);
    }

    /**
     * จัดกลุ่ม Route (Route Grouping)
     */
    public static function group(array $attributes, callable $callback): void
    {
        $previousPrefix = self::$groupPrefix;
        $previousOptions = self::$groupOptions;

        if (isset($attributes['prefix'])) {
            self::$groupPrefix = $previousPrefix . '/' . trim($attributes['prefix'], '/');
        }
        self::$groupOptions = array_merge($previousOptions, $attributes);

        $callback();

        self::$groupPrefix = $previousPrefix;
        self::$groupOptions = $previousOptions;
    }

    /**
     * บันทึก Route ลงในระบบ
     */
    protected static function addRoute(array $methods, string $path, $handler, array $options = []): void
    {
        // ต่อ Prefix หากมีการใช้ Group
        $fullPath = self::$groupPrefix . '/' . trim($path, '/');
        $fullPath = '/' . trim($fullPath, '/');
        if ($fullPath === '//') {
            $fullPath = '/';
        }

        $mergedOptions = array_merge(self::$groupOptions, $options);

        // ดึง Controller และ Action ออกมาหากส่งเป็น string เช่น 'SalesController@create'
        $controller = null;
        $action = null;

        if (is_string($handler)) {
            if (str_contains($handler, '@')) {
                [$controller, $action] = explode('@', $handler, 2);
            } elseif (str_contains($handler, '::')) {
                [$controller, $action] = explode('::', $handler, 2);
            } else {
                $controller = $handler;
                $action = 'index';
            }
        } elseif (is_array($handler) && count($handler) === 2) {
            $controller = is_object($handler[0]) ? get_class($handler[0]) : $handler[0];
            $action = $handler[1];
        }

        // แปลง path pattern ให้เป็น Regex
        // รองรับ {id}, {id:[0-9]+}, {name:[a-zA-Z0-9_-]+}
        $paramNames = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+)(?::([^}]+))?\}/', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            $customRegex = !empty($matches[2]) ? $matches[2] : '[^/]+';
            return "(?P<{$matches[1]}>{$customRegex})";
        }, $fullPath);

        $regex = '#^' . ($pattern === '/' ? '/' : rtrim($pattern, '/')) . '$#i';

        $routeData = [
            'methods'     => $methods,
            'path'        => $fullPath,
            'regex'       => $regex,
            'handler'     => $handler,
            'controller'  => $controller ? self::normalizeControllerName($controller) : null,
            'action'      => $action ?: 'index',
            'paramNames'  => $paramNames,
            'options'     => $mergedOptions,
        ];

        foreach ($methods as $method) {
            self::$routes[$method][] = $routeData;
        }

        if (!empty($mergedOptions['name'])) {
            self::$namedRoutes[$mergedOptions['name']] = $fullPath;
        }
    }

    /**
     * ปรับชื่อ Controller ให้ลงท้ายด้วย Controller เสมอ
     */
    protected static function normalizeControllerName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }
        return ucfirst($name);
    }

    /**
     * ดึง URL Path จาก Request ปัจจุบัน พร้อมตัดนามสกุล .php ออก
     */
    public static function getRequestPath(): string
    {
        // 1. ถ้ามี $_GET['url'] จาก mod_rewrite (เช่น index.php?url=stock/bundle)
        if (isset($_GET['url']) && trim($_GET['url']) !== '') {
            $rawUrl = trim($_GET['url'], '/');
            // ตัด .php ออกถ้ามีคนแนบมา เช่น stock.php
            $rawUrl = preg_replace('/\.php$/i', '', $rawUrl);
            return '/' . $rawUrl;
        }

        // 2. ถ้าไม่มี $_GET['url'] ให้ดึงจาก REQUEST_URI
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // ตัด Query String ออก
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // ตัด Directory ของ script (BASE_URL) ออก
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir !== '/' && $scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        // ตัด index.php ออก
        if (str_starts_with($uri, '/index.php')) {
            $uri = substr($uri, strlen('/index.php'));
        }

        // ตัด .php ออก เช่น /stock.php -> /stock
        $uri = preg_replace('/\.php$/i', '', $uri);

        $uri = '/' . ltrim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }

    /**
     * ฟังก์ชันสำหรับแก้ไขให้เว็บไม่ต้องแสดงไฟล์แบบ .php (ตัด .php ออกจาก string/path)
     */
    public static function removePhpExtension(string $path): string
    {
        $query = '';
        if (($pos = strpos($path, '?')) !== false) {
            $query = substr($path, $pos);
            $path = substr($path, 0, $pos);
        }

        if (preg_match('/index\.php\?url=([^&]+)/', $path . $query, $m)) {
            $clean = urldecode($m[1]);
            return trim($clean, '/');
        }

        $clean = preg_replace('/\.php$/i', '', $path);
        $clean = preg_replace('#(^|/)index\.php$#i', '', $clean);

        return $clean . $query;
    }

    /**
     * ตรวจสอบว่า Request มีการส่ง .php หรือ index.php?url= เข้ามาโดยตรงหรือไม่
     * หากมี จะทำ 301 Permanent Redirect ไปยัง Clean URL ทันที
     * เพื่อให้ Address Bar ในบราวเซอร์ไม่ต้องแสดง .php
     */
    public static function redirectIfPhp(): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($requestMethod !== 'GET' && $requestMethod !== 'HEAD') {
            return;
        }

        $theRequest = $_SERVER['THE_REQUEST'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        // กรณี Apache/Nginx ที่ส่ง THE_REQUEST มา
        if ($theRequest !== '') {
            if (preg_match('#^[A-Z]+\s+([^?\s]+)\.php(?:\?([^\s]*))?\s+HTTP#i', $theRequest, $matches)) {
                $filePath = $matches[1];
                $rawQuery = $matches[2] ?? '';

                // กรณี index.php?url=... ให้ redirect ไปยัง clean URL
                if (str_ends_with($filePath, '/index') || $filePath === 'index' || str_ends_with($filePath, 'index.php')) {
                    if (isset($_GET['url'])) {
                        $targetUrl = url($_GET['url']);
                        $extraParams = $_GET;
                        unset($extraParams['url']);
                        if (!empty($extraParams)) {
                            $targetUrl .= (str_contains($targetUrl, '?') ? '&' : '?') . http_build_query($extraParams);
                        }
                        header('Location: ' . $targetUrl, true, 301);
                        exit;
                    }
                    if ($rawQuery === '') {
                        $root = defined('BASE_URL') && BASE_URL !== '' ? BASE_URL : '/';
                        header('Location: ' . $root, true, 301);
                        exit;
                    }
                } else {
                    // กรณีเรียกไฟล์ .php อื่นๆ เช่น /stock.php -> redirect ไป /stock
                    $cleanPath = $filePath;
                    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
                    if ($scriptDir !== '/' && $scriptDir !== '' && str_starts_with($cleanPath, $scriptDir)) {
                        $cleanPath = substr($cleanPath, strlen($scriptDir));
                    }
                    $cleanTarget = url($cleanPath);
                    if ($rawQuery !== '') {
                        $cleanTarget .= '?' . $rawQuery;
                    }
                    header('Location: ' . $cleanTarget, true, 301);
                    exit;
                }
            }
        } elseif (str_contains($requestUri, '.php')) {
            // สำรองสำหรับสภาพแวดล้อมที่ไม่มี THE_REQUEST
            $parsed = parse_url($requestUri);
            $path = $parsed['path'] ?? '';
            $query = $parsed['query'] ?? '';

            if (str_ends_with($path, '.php')) {
                if (str_ends_with($path, '/index.php') && isset($_GET['url'])) {
                    $targetUrl = url($_GET['url']);
                    $extraParams = $_GET;
                    unset($extraParams['url']);
                    if (!empty($extraParams)) {
                        $targetUrl .= (str_contains($targetUrl, '?') ? '&' : '?') . http_build_query($extraParams);
                    }
                    header('Location: ' . $targetUrl, true, 301);
                    exit;
                }

                $clean = preg_replace('/\.php$/i', '', $path);
                $target = $clean . ($query !== '' ? '?' . $query : '');
                header('Location: ' . $target, true, 301);
                exit;
            }
        }
    }

    /**
     * ค้นหา Route ที่ตรงกับ Request
     */
    public static function matchRoute(?string $method = null, ?string $path = null): ?array
    {
        $method = $method ? strtoupper($method) : strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path   = $path ?? self::getRequestPath();

        // 1. ค้นหาใน Route ที่ลงทะเบียนไว้ใน routes/web.php
        $methodRoutes = self::$routes[$method] ?? [];
        foreach ($methodRoutes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($route['paramNames'] as $name) {
                    if (isset($matches[$name])) {
                        $params[$name] = $matches[$name];
                    }
                }

                return [
                    'type'       => 'registered',
                    'route'      => $route,
                    'controller' => $route['controller'],
                    'action'     => $route['action'],
                    'handler'    => $route['handler'],
                    'params'     => array_values($params),
                    'namedParams'=> $params,
                    'options'    => $route['options'],
                ];
            }
        }

        // 2. Fallback: รองรับการเรียกแบบเดิม /controller/action/param1/param2 (เพื่อความเข้ากันได้)
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        if (!empty($segments)) {
            $controllerName = ucfirst($segments[0]) . 'Controller';
            $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';

            if (file_exists($controllerFile)) {
                $actionName = $segments[1] ?? 'index';
                $params = array_slice($segments, 2);

                return [
                    'type'       => 'fallback',
                    'route'      => null,
                    'controller' => $controllerName,
                    'action'     => $actionName,
                    'handler'    => "{$controllerName}@{$actionName}",
                    'params'     => $params,
                    'namedParams'=> [],
                    'options'    => [],
                ];
            }
        } elseif ($path === '/' || $path === '') {
            return [
                'type'       => 'fallback',
                'route'      => null,
                'controller' => 'DashboardController',
                'action'     => 'index',
                'handler'    => 'DashboardController@index',
                'params'     => [],
                'namedParams'=> [],
                'options'    => [],
            ];
        }

        return null;
    }

    /**
     * ดำเนินการเรียก Controller / Action หรือ Callable
     */
    public static function execute(array $matchedRoute): void
    {
        $handler = $matchedRoute['handler'];
        $params  = $matchedRoute['params'];

        // กรณีเป็น Callable / Closure
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        $controllerName = $matchedRoute['controller'];
        $actionName     = $matchedRoute['action'];

        $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            http_response_code(500);
            die("Controller file not found: {$controllerName}.php");
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            http_response_code(500);
            die("Controller class '{$controllerName}' not found");
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $actionName)) {
            http_response_code(404);
            die("Action method '{$actionName}' not found in controller '{$controllerName}'");
        }

        call_user_func_array([$controller, $actionName], $params);
    }

    /**
     * ดึงรายการ Routes ทั้งหมดที่ลงทะเบียนไว้ (ใช้สำหรับ Debug หรือดูภาพรวม)
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }
}
