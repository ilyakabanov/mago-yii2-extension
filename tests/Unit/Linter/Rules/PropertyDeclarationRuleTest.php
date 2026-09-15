<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\PropertyDeclarationRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class PropertyDeclarationRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/PropertyDeclaration';

    protected function getRuleCode(): string
    {
        return 'yii2/property-declaration';
    }

    public function testDefinition(): void
    {
        $definition = (new PropertyDeclarationRule())->getDefinition();

        self::assertSame('yii2/property-declaration', $definition->code);
        self::assertSame('Property declaration', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::PlainProperty, NodeKind::HookedProperty], $definition->targets);
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidPropertyDeclaration.php');
    }

    public function testAcceptsValidPhp84Fixture(): void
    {
        $this->assertValidFixtureFile(
            self::FIXTURES_DIR . '/Valid/ValidPhp84PropertyDeclaration.php',
            phpVersion: '8.4',
        );
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidPropertyDeclaration.php', [
            [
                'message' => 'The `var` keyword must not be used to declare a property.',
                'line' => 9,
                'column' => 5,
            ],
            [
                'message' => 'Visibility must be declared on property "$legacy".',
                'line' => 9,
                'column' => 9,
            ],
            [
                'message' => 'Only one property may be declared per statement.',
                'line' => 10,
                'column' => 27,
            ],
            [
                'message' => 'Visibility must be declared on property "$missingVisibility".',
                'line' => 11,
                'column' => 12,
            ],
        ]);
    }

    public function testDetectsInvalidPhp84Fixture(): void
    {
        $this->assertInvalidFixtureFile(
            self::FIXTURES_DIR . '/Invalid/InvalidPhp84PropertyDeclaration.php',
            [
                [
                    'message' => 'Visibility must be declared on property "$missingVisibility".',
                    'line' => 9,
                    'column' => 21,
                ],
            ],
            phpVersion: '8.4',
        );
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidPropertyDeclaration.php');
    }
}
