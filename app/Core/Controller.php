<?php

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        ob_start();
        require dirname(__DIR__) . "/Views/$view.php";
        $content = ob_get_clean();
        require dirname(__DIR__) . '/Views/layouts/app.php';
    }
    protected function authView(string $view, array $data = []): void
    {
        extract($data);
        require dirname(__DIR__) . "/Views/$view.php";
    }
}
