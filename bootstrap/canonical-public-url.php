<?php

return static function (array $server): ?string {
    $scriptName = str_replace('\\', '/', (string) ($server['SCRIPT_NAME'] ?? ''));
    $requestUri = (string) ($server['REQUEST_URI'] ?? '/');
    $requestPath = parse_url($requestUri, PHP_URL_PATH);

    if (! is_string($requestPath) || ! str_ends_with(strtolower($scriptName), '/public/index.php')) {
        return null;
    }

    $publicMount = substr($scriptName, 0, -strlen('/index.php'));

    if ($requestPath !== $publicMount && ! str_starts_with($requestPath, $publicMount.'/')) {
        return null;
    }

    $applicationMount = substr($publicMount, 0, -strlen('/public'));
    $pathAfterPublic = substr($requestPath, strlen($publicMount));
    $canonicalPath = $applicationMount.($pathAfterPublic !== '' ? $pathAfterPublic : '/');
    $queryString = (string) ($server['QUERY_STRING'] ?? '');

    return $canonicalPath.($queryString !== '' ? '?'.$queryString : '');
};
