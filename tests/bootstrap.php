<?php

require dirname(__DIR__).'/vendor/autoload.php';

$compiledViewPath = dirname(__DIR__).'/storage/framework/testing-views/'.getmypid().'-'.bin2hex(random_bytes(8));

if (! mkdir($compiledViewPath, 0777, true) && ! is_dir($compiledViewPath)) {
    throw new RuntimeException('Unable to create isolated Blade cache for tests.');
}

$_ENV['VIEW_COMPILED_PATH'] = $compiledViewPath;
$_SERVER['VIEW_COMPILED_PATH'] = $compiledViewPath;
putenv('VIEW_COMPILED_PATH='.$compiledViewPath);
