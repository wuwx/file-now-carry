<?php


require __DIR__ . '/vendor/autoload.php';

\App\Models\User::createTable(1);
return;
(new \App\Application(100, '999'))->run();