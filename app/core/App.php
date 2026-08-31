<?php
/**
 * App (Router)
 * รูปแบบ URL: index.php?url=controller/action/param1/param2
 * ตัวอย่าง: index.php?url=purchase/create
 */
class App
{
    protected string $controllerName = 'Dashboard';
    protected string $action = 'index';
    protected array $params = [];

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
}
