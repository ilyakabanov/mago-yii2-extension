<?php

declare(strict_types=1);

use Mago\Sdk\Worker;
use yii2\extensions\mago\Yii2Extension;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

// Analysis inputs must remain metadata, not executable bootstrap or runtime autoload files.
$analysisFiles = array_map(realpath(...), array_slice($argv, 3));
register_shutdown_function(static function () use ($argv, $analysisFiles): void {
    $loaded = array_values(array_filter(
        get_declared_classes(),
        static fn(string $class): bool => (
            str_starts_with($class, 'yii2\\extensions\\mago\\generated\\')
            || str_starts_with($class, 'ParamsKeyRegression\\')
            || str_starts_with($class, 'ParamsKeyArtifacts\\')
        ),
    ));
    file_put_contents(
        $argv[2],
        json_encode([
            'loadedAnalysisFiles' => array_values(array_intersect($analysisFiles, get_included_files())),
            'loadedAnalysisClasses' => $loaded,
            'applicationStarted' => Yii::$app !== null,
        ], JSON_THROW_ON_ERROR)
            . "\n",
        FILE_APPEND | LOCK_EX,
    );
});

(new Worker(Yii2Extension::create($argv[1])))->run();
