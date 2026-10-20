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
    private function map(string $applicationType, array $params = []): ServiceMap
    {
        $GLOBALS['yii2MagoTestConfig'] = ['mago' => ['application_type' => $applicationType], 'params' => $params];
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

    public static function ordinaryKeys(): iterable
    {
        yield 'apostrophe' => ["it's", '"it\'s"'];
        yield 'double quote' => ['quote"d', "'quote\"d'"];
        yield 'backslash' => ['back\\slash', "'back\\slash'"];
        yield 'literal backslash n' => ['new\\nline', "'new\\nline'"];
    }

    #[DataProvider('ordinaryKeys')]
    public function testOrdinaryKeysUseLiteralPhpDocBytes(string $key, string $literal): void
    {
        $source = StubGenerator::generate($this->map('yii\\web\\Application', ['nested' => [$key => true]]));
        self::assertStringContainsString("'nested': array{" . $literal . ': bool}', $source);
        self::assertNotEmpty(token_get_all($source, TOKEN_PARSE));
    }

    public static function unrepresentableKeys(): iterable
    {
        foreach (["both'\"quotes", 'ending\\', "line\nbreak", "null\0key", '*/'] as $key) {
            yield bin2hex($key) => [$key];
        }
    }

    #[DataProvider('unrepresentableKeys')]
    public function testSingleFileOmitsUnrepresentableNestedKeys(string $key): void
    {
        $source = StubGenerator::generate($this->map('yii\\web\\Application', ['nested' => [$key => true]]));
        self::assertStringNotContainsString('class Module', $source);
        self::assertNotEmpty(token_get_all($source, TOKEN_PARSE));
    }

    public function testBundleKeepsAllKeyBytesOutsideTheSparsePatch(): void
    {
        $params = (require __DIR__ . '/../regressions/params-keys/config.php')['params'];
        $params["both'\"quotes"] = ['ending\\' => []];
        $params['09'] = 1;
        $params["*/'; throw new \\RuntimeException('injected'); //"] = false;
        $app = \Yii::$app;
        $bundle = StubGenerator::generateWithParamsKeys($this->map('yii\\web\\Application', $params));
        self::assertSame(['patch', 'keys'], array_keys($bundle));
        foreach ($bundle as $source) {
            self::assertNotEmpty(token_get_all($source, TOKEN_PARSE));
        }
        self::assertMatchesRegularExpression('/namespace yii2\\\\extensions\\\\mago\\\\generated;/', $bundle['keys']);
        self::assertMatchesRegularExpression('/final class ParamsKeys_[a-f0-9]{64}/', $bundle['keys']);
        self::assertStringContainsString('class Module', $bundle['patch']);
        self::assertStringNotContainsString('public const', $bundle['patch']);
        self::assertStringNotContainsString('final class', $bundle['patch']);
        self::assertStringNotContainsString('x*/y', $bundle['patch']);
        self::assertStringNotContainsString("new\nline", $bundle['patch']);
        self::assertStringNotContainsString("null\0key", $bundle['patch']);
        self::assertStringContainsString('-5: bool', $bundle['patch']);
        self::assertStringContainsString('123: float', $bundle['patch']);
        self::assertStringContainsString('0: string, 1: string', $bundle['patch']);
        self::assertStringContainsString('array<array-key, mixed>', $bundle['patch']);
        foreach (['x*/y', "new\nline", 'new\\nline', "null\0key", "both'\"quotes", 'ending\\', '09'] as $key) {
            self::assertStringContainsString(' = ' . var_export($key, true) . ';', $bundle['keys']);
        }
        self::assertSame($app, \Yii::$app);
        self::assertStringNotContainsString('function ', $bundle['keys']);
        $tokens = array_filter(token_get_all($bundle['keys'], TOKEN_PARSE), is_array(...));
        self::assertSame(
            [],
            array_values(array_filter($tokens, static fn(array $token): bool => in_array(
                $token[0],
                [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE, T_THROW, T_FUNCTION, T_NEW],
                true,
            ))),
        );
    }

    public function testHelperIdentityDependsOnlyOnKeysNotConfiguredValues(): void
    {
        $resource = fopen('php://memory', 'r');
        try {
            $object = new class {
                public function __serialize(): array
                {
                    throw new \LogicException('Configured values must not be serialized.');
                }
            };
            $before = StubGenerator::generateWithParamsKeys($this->map('yii\\web\\Application', [
                'a' => ['b' => $object],
                'c' => $resource,
                'd' => static fn() => throw new \LogicException('Not called'),
            ]));
            $after = StubGenerator::generateWithParamsKeys($this->map('yii\\web\\Application', [
                'd' => false,
                'c' => null,
                'a' => ['b' => 1.5],
            ]));
            self::assertSame($before['keys'], $after['keys']);
            self::assertSame(3, substr_count($before['patch'], ': mixed'));
            self::assertStringContainsString(': float', $after['patch']);
            $different = StubGenerator::generateWithParamsKeys($this->map('yii\\web\\Application', ['different' => 1]));
            self::assertNotSame($before['keys'], $different['keys']);
        } finally {
            fclose($resource);
        }
    }

    public function testBundlePreservesEmptyParamsSemantics(): void
    {
        $services = $this->map('yii\\web\\Application');
        $bundle = StubGenerator::generateWithParamsKeys($services);
        self::assertSame(StubGenerator::generate($services), $bundle['patch']);
        self::assertStringNotContainsString('public const', $bundle['keys']);
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
