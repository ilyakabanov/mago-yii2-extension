<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

final class AuditPaths
{
    public readonly string $phpcsDirectory;
    public readonly string $phpcsBinary;
    public readonly string $phpcsStandard;
    public readonly string $phpcsStandardsSource;
    public readonly string $magoDirectory;
    public readonly string $magoBinary;
    public readonly string $magoConfiguration;
    public readonly string $casesDirectory;

    public function __construct(
        public readonly string $projectRoot,
        public readonly string $cacheDirectory,
    ) {
        $this->phpcsDirectory = $cacheDirectory . '/phpcs';
        $this->phpcsBinary = $this->phpcsDirectory . '/vendor/bin/phpcs';
        $this->phpcsStandard = $this->phpcsDirectory . '/vendor/yiisoft/yii2-coding-standards/Yii2/ruleset.xml';
        $this->phpcsStandardsSource = $this->phpcsDirectory . '/vendor/squizlabs/php_codesniffer/src/Standards';
        $this->magoDirectory = $cacheDirectory . '/mago';
        $this->magoBinary = $this->magoDirectory . '/vendor/bin/mago';
        $this->magoConfiguration = $this->magoDirectory . '/mago.toml';
        $this->casesDirectory = $this->magoDirectory . '/cases';
    }
}
