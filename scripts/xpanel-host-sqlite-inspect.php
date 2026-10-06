<?php

use App\Services\HostInstanceDatabaseReader;

require dirname(__DIR__).'/vendor/autoload.php';

if ($argc !== 5) {
    fwrite(STDERR, "Argumentos de inspección inválidos.\n");
    exit(1);
}

try {
    echo json_encode(HostInstanceDatabaseReader::inspectPath($argv[1], $argv[2], $argv[3], $argv[4]), JSON_THROW_ON_ERROR), "\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}
