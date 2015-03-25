<?php

use JP\Redis\Client;
use JP\Redis\ReplicaSetClient;
use JP\Redis\ReadPreference;

date_default_timezone_set('America/New_York');

ini_set('zlib.output_compression', 1024);
ini_set('zlib.output_compression_level', 6);
error_reporting(E_ALL & ~E_NOTICE);

//header('Content-Type: text/plain; charset=utf-8');

header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');

/**
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 */
spl_autoload_register(function ($class) {

    // project-specific namespace prefix
    $prefix = 'JP\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/src/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (is_file($file)) {
        require_once $file;
    }
});

$rsc = new ReplicaSetClient(
    [
        [ Client::OPT_HOST => '127.0.0.1', Client::OPT_PORT => 6379, ],
        [ Client::OPT_HOST => '127.0.0.1', Client::OPT_PORT => 16379, ],
    ],
    ReadPreference::RP_SECONDARY_PREFERRED
);

var_dump($rsc->getWriteAdapter());
var_dump($rsc->getReadAdapter());
