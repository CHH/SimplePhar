<?php
namespace SimplePhar;

require_once __DIR__ . "/../vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php";

use Symfony\Component\ClassLoader\UniversalClassLoader;

$classLoader = new UniversalClassLoader;
$classLoader->registerNamespaces(array(
    "SimplePhar" => __DIR__,
    "Symfony"    => __DIR__ . "/../vendor"
));
$classLoader->register();

function is_absolute($path) 
{
    if (strtoupper(substr(PHP_OS, 0, 3) == "WIN")) {
        return (bool) preg_match("/^[a-zA-Z]\:\\/", $path);
    } else {
        return $path[0] == '/';
    }
}

function set_config(array $config, Compiler $compiler)
{
    foreach ($config as $option => $value) {
        $setter = "set" . str_replace(' ', '', ucwords(str_replace('_', ' ', $option)));
        if (!is_callable(array($compiler, $setter))) {
            die("Option $option is undefined");
        }
        
        $compiler->{$setter}($value);
    }
}

$compiler = new Compiler;
$target = getcwd();

if (isset($argv[1])) {
    $target .= DIRECTORY_SEPARATOR . $argv[1];
}

$target = realpath($target);

if (!file_exists($pharfile = $target . "/Pharfile")) {
    die("No Pharfile found in " . $target . "\r\n");
}

$config = parse_ini_file($pharfile);

if (!$config) {
    die("Error reading Pharfile\r\n");
}

if (empty($config["base_path"])) {
    $config["base_path"] = realpath(dirname($pharfile));
}

set_config($config, $compiler);

$compiler->compile();

print ">>> Success!\r\n";
print ">>> Generated Phar File at {$config["dist_path"]}\r\n";

