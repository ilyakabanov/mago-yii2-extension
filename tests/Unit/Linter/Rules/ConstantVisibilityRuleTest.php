<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\ConstantVisibilityRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class ConstantVisibilityRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/ConstantVisibility';

    protected function getRuleCode(): string
    {
        return 'yii2/constant-visibility';
    }

    public function testDefinition(): void
    {
        $definition = (new ConstantVisibilityRule())->getDefinition();

        self::assertSame('yii2/constant-visibility', $definition->code);
        self::assertSame('Constant visibility', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::ClassLikeConstant], $definition->targets);
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidConstantVisibility.php');
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidConstantVisibility.php', [
            [
                'message' => 'Visibility must be declared on class constants.',
                'line' => 9,
                'column' => 5,
            ],
            [
                'message' => 'Visibility must be declared on class constants.',
                'line' => 10,
                'column' => 11,
            ],
        ]);
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidConstantVisibility.php');
    }
}
