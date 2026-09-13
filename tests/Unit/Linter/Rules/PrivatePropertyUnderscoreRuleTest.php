<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\PrivatePropertyUnderscoreRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;
use PHPUnit\Framework\Attributes\DataProvider;

final class PrivatePropertyUnderscoreRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/PrivatePropertyUnderscore';

    protected function getRuleCode(): string
    {
        return 'yii2/private-property-underscore';
    }

    public function testRuleImplementsRuleInterface(): void
    {
        $rule = new PrivatePropertyUnderscoreRule();

        self::assertInstanceOf(Rule::class, $rule);
    }

    public function testDefinition(): void
    {
        $rule = new PrivatePropertyUnderscoreRule();
        $definition = $rule->getDefinition();

        self::assertSame('yii2/private-property-underscore', $definition->code);
        self::assertSame('Private property underscore', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertContains(NodeKind::PlainProperty, $definition->targets);
        self::assertContains(NodeKind::HookedProperty, $definition->targets);
        self::assertContains(NodeKind::FunctionLikeParameter, $definition->targets);
    }

    #[DataProvider('provideValidFixtures')]
    public function testAcceptsValidFixtures(string $filePath, string $phpVersion): void
    {
        $this->assertValidFixtureFile($filePath, $phpVersion);
    }

    /**
     * @param list<array{message: string, line: int, column: int}> $expectedIssues
     */
    #[DataProvider('provideInvalidFixtures')]
    public function testDetectsInvalidFixtures(string $filePath, array $expectedIssues, string $phpVersion): void
    {
        $this->assertInvalidFixtureFile($filePath, $expectedIssues, $phpVersion);
    }

    public function testDoesNotApplyUnsafeAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidPropertyWithUsage.php');
    }

    /**
     * @return iterable<string, array{filePath: string, phpVersion: string}>
     */
    public static function provideValidFixtures(): iterable
    {
        yield 'valid private properties' => [
            'filePath' => self::FIXTURES_DIR . '/Valid/ValidProperties.php',
            'phpVersion' => '8.1',
        ];

        yield 'valid promoted constructor properties' => [
            'filePath' => self::FIXTURES_DIR . '/Valid/ValidPromotedProperties.php',
            'phpVersion' => '8.1',
        ];

        yield 'valid public and protected properties' => [
            'filePath' => self::FIXTURES_DIR . '/Valid/ValidPublicProtected.php',
            'phpVersion' => '8.1',
        ];

        yield 'valid PHP 8.4 properties' => [
            'filePath' => self::FIXTURES_DIR . '/Valid/ValidPhp84Properties.php',
            'phpVersion' => '8.4',
        ];
    }

    /**
     * @return iterable<string, array{
     *     filePath: string,
     *     expectedIssues: list<array{message: string, line: int, column: int}>,
     *     phpVersion: string,
     * }>
     */
    public static function provideInvalidFixtures(): iterable
    {
        yield 'invalid plain property' => [
            'filePath' => self::FIXTURES_DIR . '/Invalid/InvalidPlainProperty.php',
            'expectedIssues' => [[
                'message' => 'Private property $unprefixedName must start with an underscore prefix ($_unprefixedName).',
                'line' => 9,
                'column' => 20,
            ]],
            'phpVersion' => '8.1',
        ];

        yield 'invalid multiple properties in one declaration' => [
            'filePath' => self::FIXTURES_DIR . '/Invalid/InvalidMultiProperty.php',
            'expectedIssues' => [
                [
                    'message' => 'Private property $firstCoord must start with an underscore prefix ($_firstCoord).',
                    'line' => 9,
                    'column' => 17,
                ],
                [
                    'message' => 'Private property $secondCoord must start with an underscore prefix ($_secondCoord).',
                    'line' => 9,
                    'column' => 34,
                ],
            ],
            'phpVersion' => '8.1',
        ];

        yield 'invalid promoted constructor property' => [
            'filePath' => self::FIXTURES_DIR . '/Invalid/InvalidPromotedProperty.php',
            'expectedIssues' => [
                [
                    'message' => 'Private promoted property $promotedParam must start with an underscore prefix ($_promotedParam).',
                    'line' => 10,
                    'column' => 24,
                ],
                [
                    'message' => 'Private promoted property $readonlyPromotedParam must start with an underscore prefix ($_readonlyPromotedParam).',
                    'line' => 11,
                    'column' => 33,
                ],
            ],
            'phpVersion' => '8.1',
        ];

        yield 'invalid static property' => [
            'filePath' => self::FIXTURES_DIR . '/Invalid/InvalidStaticProperty.php',
            'expectedIssues' => [[
                'message' => 'Private property $singletonInstance must start with an underscore prefix ($_singletonInstance).',
                'line' => 9,
                'column' => 26,
            ]],
            'phpVersion' => '8.1',
        ];

        yield 'invalid readonly property' => [
            'filePath' => self::FIXTURES_DIR . '/Invalid/InvalidReadonlyProperty.php',
            'expectedIssues' => [[
                'message' => 'Private property $readonlyProperty must start with an underscore prefix ($_readonlyProperty).',
                'line' => 9,
                'column' => 29,
            ]],
            'phpVersion' => '8.1',
        ];

        yield 'invalid PHP 8.4 hooked property' => [
            'filePath' => self::FIXTURES_DIR . '/Invalid/InvalidHookedProperty.php',
            'expectedIssues' => [[
                'message' => 'Private property $hookedProperty must start with an underscore prefix ($_hookedProperty).',
                'line' => 9,
                'column' => 20,
            ]],
            'phpVersion' => '8.4',
        ];
    }
}
