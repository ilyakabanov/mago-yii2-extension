<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\ServiceMap;
use yii2\extensions\mago\StubGenerator;

final class StubGeneratorTest extends TestCase
{
    private function map(string $applicationType): ServiceMap
    {
        $GLOBALS['yii2MagoTestConfig'] = ['mago' => ['application_type' => $applicationType]];
        try {
            return new ServiceMap(__DIR__ . '/../fixtures/config/inline.php');
        } finally {
            unset($GLOBALS['yii2MagoTestConfig']);
        }
    }

    public static function applications(): iterable
    {
        yield 'web' => ['yii\\web\\Application'];
        yield 'console' => ['yii\\console\\Application'];
        yield 'custom not loaded in runtime' => ['app\\MyApplication'];
        yield 'leading backslash' => ['\\app\\MyApplication'];
    }

    #[DataProvider('applications')]
    public function testGeneratesSparseAnalysisOnlyBaseYiiPatch(string $application): void
    {
        $services = $this->map($application);
        $realApplication = \Yii::$app;
        $source = StubGenerator::generate($services);
        self::assertStringContainsString('namespace yii;', $source);
        self::assertStringContainsString('class BaseYii', $source);
        self::assertStringContainsString('/** @var \\' . ltrim($application, '\\') . ' */', $source);
        self::assertStringContainsString('public static $app;', $source);
        self::assertStringNotContainsString('require', substr($source, strpos($source, 'class BaseYii')));
        self::assertStringNotContainsString('function', $source);
        self::assertSame($realApplication, \Yii::$app);
        self::assertNotEmpty(token_get_all($source, TOKEN_PARSE));
    }

    public static function invalidApplications(): iterable
    {
        foreach ([
            '\\',
            'invalid-name',
            "App\nInjected",
            'App */ public static $injected; /*',
            'App\\',
            'self',
            'parent',
            'static',
        ] as $class) {
            yield $class => [$class];
        }
    }

    #[DataProvider('invalidApplications')]
    public function testRejectsInvalidClassIdentifiers(string $application): void
    {
        $this->expectException(InvalidArgumentException::class);
        StubGenerator::generate($this->map($application));
    }

    public function testCommittedStubMatchesGenerator(): void
    {
        $services = new ServiceMap(__DIR__ . '/../corpus/config.php');
        self::assertSame(StubGenerator::generate($services), file_get_contents(__DIR__ . '/../corpus/stubs/Yii.php'));
    }

    public function testModuleParamsPatchSupportsInheritedAccessWithoutExecutingValues(): void
    {
        $source = StubGenerator::generate(new ServiceMap(__DIR__ . '/../corpus/config.php'));
        self::assertStringContainsString('namespace yii\\base;', $source);
        self::assertStringContainsString("'adminEmail': string", $source);
        self::assertStringContainsString("'nested': array{'enabled': bool}", $source);
        self::assertStringContainsString('public $params;', $source);
    }

    public function testUnsafeCommentKeysStayInTheSemanticProvider(): void
    {
        $source = StubGenerator::generate(new ServiceMap(__DIR__
        . '/../reference/config/params-awkward-keys-config.php'));
        self::assertStringContainsString('class BaseYii', $source);
        self::assertStringNotContainsString('class Module', $source);
        self::assertNotEmpty(token_get_all($source, TOKEN_PARSE));
    }
}
