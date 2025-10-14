<?php
namespace App\Helpers;


class Response
{
public static function view(string $template, array $vars = []): void
{
extract($vars);
$viewPath = __DIR__ . '/../Views/' . $template;
$layout = __DIR__ . '/../Views/layout.php';
ob_start();
require $viewPath;
$content = ob_get_clean();
require $layout;
}


public static function json($data, int $code = 200): void
{
http_response_code($code);
header('Content-Type: application/json');
echo json_encode($data);
}
}