<?php


require __DIR__ . '/vendor/autoload.php';

(new \App\Application(new \App\Foundation\Base64Encode(), 100))->run();