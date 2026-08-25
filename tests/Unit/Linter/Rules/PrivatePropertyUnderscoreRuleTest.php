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
    public function testAcceptsValidFixtures(string $filePath): void
    {
        $this->assertValidFixtureFile($filePath);
    }

    /**
     * @param list<string> $expectedMessages
     */
    #[DataProvider('provideInvalidFixtures')]
    public function testDetectsInvalidFixtures(string $filePath, array $expectedMessages): void
    {
        $this->assertInvalidFixtureFile($filePath, $expectedMessages);
    }

    #[DataProvider('provideAutoFixFixtures')]
    public function testAppliesAutoFix(string $invalidFilePath, string $expectedFixedFilePath): void
    {
        $this->assertAutoFixFixtureFile($invalidFilePath, $expectedFixedFilePath);
    }

    /**
     * @return iterable<string, array{filePath: string}>
     */
    public static function provideValidFixtures(): iterable
    {
        yield 'valid private properties' => [
            'filePath' => self::FIXTURES_DIR . '/Valid/ValidProperties.php',
        ];

        yield 'valid promoted constructor properties' => [
            'filePath' => self::FIXTURES_DIR . '/Valid/ValidPromotedProperties.php',
        ];

        yield 'valid public and protected properties' => [
            'filePath' => self::FIXTURES_DIR . '/Valid/ValidPublicProtected.php',
        ];
    }

    /**
     * @return iterable<string, array{filePath: string, expectedMessages: list<string>}>
     */
    public static function provideInvalidFixtures(): iterable
    {
        yield 'invalid plain property' => [
            'filePath' => self::FIXTURES_DIR . '/Invalid/InvalidPlainProperty.php',
            'expectedMessages' => [
                'Private property $unprefixedName must start with an underscore prefix ($_unprefixedName).',
            ],
        ];

        yield 'invalid multiple properties in one declaration' => [
            'filePath' => self::FIXTURES_DIR . '/Invalid/InvalidMultiProperty.php',
            'expectedMessages' => [
                'Private property $firstCoord must start with an underscore prefix ($_firstCoord).',
                'Private property $secondCoord must start with an underscore prefix ($_secondCoord).',
            ],
        ];

        yield 'invalid promoted constructor property' => [
            'filePath' => self::FIXTURES_DIR . '/Invalid/InvalidPromotedProperty.php',
            'expectedMessages' => [
                'Private promoted property $promotedParam must start with an underscore prefix ($_promotedParam).',
            ],
        ];

        yield 'invalid static property' => [
            'filePath' => self::FIXTURES_DIR . '/Invalid/InvalidStaticProperty.php',
            'expectedMessages' => [
                'Private property $singletonInstance must start with an underscore prefix ($_singletonInstance).',
            ],
        ];
    }

    /**
     * @return iterable<string, array{invalidFilePath: string, expectedFixedFilePath: string}>
     */
    public static function provideAutoFixFixtures(): iterable
    {
        yield 'auto-fixes plain property' => [
            'invalidFilePath' => self::FIXTURES_DIR . '/Invalid/InvalidPlainProperty.php',
            'expectedFixedFilePath' => self::FIXTURES_DIR . '/Fixed/FixedPlainProperty.php',
        ];

        yield 'auto-fixes multiple properties' => [
            'invalidFilePath' => self::FIXTURES_DIR . '/Invalid/InvalidMultiProperty.php',
            'expectedFixedFilePath' => self::FIXTURES_DIR . '/Fixed/FixedMultiProperty.php',
        ];

        yield 'auto-fixes promoted property' => [
            'invalidFilePath' => self::FIXTURES_DIR . '/Invalid/InvalidPromotedProperty.php',
            'expectedFixedFilePath' => self::FIXTURES_DIR . '/Fixed/FixedPromotedProperty.php',
        ];

        yield 'auto-fixes static property' => [
            'invalidFilePath' => self::FIXTURES_DIR . '/Invalid/InvalidStaticProperty.php',
            'expectedFixedFilePath' => self::FIXTURES_DIR . '/Fixed/FixedStaticProperty.php',
        ];
    }
}
