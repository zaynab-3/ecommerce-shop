<?php

// HTTPS tunnel origin for the temporary demonstration only; public/ is the document root.
$host = getenv('MESH_PREVIEW_HOST');
if (! $host || ! preg_match('/^[a-z0-9-]+\.trycloudflare\.com$/D', $host)) {
    http_response_code(503);
    exit('Preview is not configured.');
}
$requestHost = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
if (! in_array($requestHost, [$host, 'localhost', '127.0.0.1'], true)) {
    http_response_code(400);
    exit;
}
// The only external entry point is the pinned HTTPS tunnel, on a loopback-only origin.
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['HTTP_HOST'] = $host;
header('X-Robots-Tag: noindex, nofollow, noarchive');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$routePath = preg_replace('~^/index\.php(?=/|$)~i', '', preg_replace('~/+~', '/', $path));
if (preg_match('~(?:^|/)\.~', $path) || preg_match('~^/manage-store(?:/|$)~i', $routePath)) {
    http_response_code(404);
    exit;
}
$public = realpath(__DIR__.'/../public');
$file = realpath($public.$path);
if ($file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') {
    return false;
}
require $public.'/index.php';
