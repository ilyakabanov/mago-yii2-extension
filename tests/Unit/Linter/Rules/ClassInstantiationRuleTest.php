<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\ClassInstantiationRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class ClassInstantiationRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/ClassInstantiation';

    protected function getRuleCode(): string
    {
        return 'yii2/class-instantiation';
    }

    public function testDefinition(): void
    {
        $definition = (new ClassInstantiationRule())->getDefinition();

        self::assertSame('yii2/class-instantiation', $definition->code);
        self::assertSame('Class instantiation', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::Instantiation], $definition->targets);
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidClassInstantiation.php');
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidClassInstantiation.php', [
            [
                'message' => 'Parentheses must be used when instantiating a new class.',
                'line' => 11,
                'column' => 18,
            ],
            [
                'message' => 'Parentheses must be used when instantiating a new class.',
                'line' => 12,
                'column' => 20,
            ],
        ]);
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidClassInstantiation.php');
    }
}
