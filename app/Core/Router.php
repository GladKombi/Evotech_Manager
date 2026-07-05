<?php
namespace App\Core;
class Router {
    private array $routes=[];
    public function get(string $path,string $action): void { $this->routes['GET'][trim($path,'/') ?: '/']=$action; }
    public function post(string $path,string $action): void { $this->routes['POST'][trim($path,'/') ?: '/']=$action; }
    public function dispatch(string $method,string $path): void {
        $path=trim($path,'/') ?: '/'; $action=$this->routes[$method][$path] ?? null;
        if(!$action){ http_response_code(404); require dirname(__DIR__).'/Views/errors/404.php'; return; }
        if (!Auth::can($path)) {
            if (!Auth::check()) {
                Helpers::redirect('login');
            }

            Auth::deny();
        }
        [$controller,$methodName]=explode('@',$action); $class='App\\Controllers\\'.$controller;
        (new $class())->$methodName();
    }
}
