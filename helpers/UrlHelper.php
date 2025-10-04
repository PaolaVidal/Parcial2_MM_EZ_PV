<?php
/**
 * UrlHelper: funciones para construir URLs absolutas y relativas.
 */
class UrlHelper {
    public static function base_url(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        // Asumimos que /public es el document root del front controller
        $scriptDir = rtrim(str_replace('index.php','', $_SERVER['SCRIPT_NAME']), '/');
        return $protocol . '://' . $host . $scriptDir;
    }

    public static function url(string $controller, string $action='index', array $params=[]): string {
        $query = http_build_query(array_merge(['controller'=>$controller,'action'=>$action], $params));
        return self::base_url() . 'index.php?' . $query;
    }
}

if(!function_exists('base_url')){
    function base_url(){ return UrlHelper::base_url(); }
}
if(!function_exists('url')){
    function url($controller, $action='index', $params=[]){ return UrlHelper::url($controller,$action,$params); }
}
