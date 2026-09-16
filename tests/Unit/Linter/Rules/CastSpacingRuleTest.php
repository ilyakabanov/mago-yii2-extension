<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\CastSpacingRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class CastSpacingRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/CastSpacing';

    protected function getRuleCode(): string
    {
        return 'yii2/cast-spacing';
    }

    public function testDefinition(): void
    {
        $definition = (new CastSpacingRule())->getDefinition();

        self::assertSame('yii2/cast-spacing', $definition->code);
        self::assertSame('Cast spacing', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::UnaryPrefixOperator], $definition->targets);
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidCastSpacing.php');
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidCastSpacing.php', [
            [
                'message' => 'Cast statements must not contain whitespace; expected "(INT)" but found "( INT )"',
                'line' => 11,
                'column' => 17,
            ],
            [
                'message' => 'Cast statements must not contain whitespace; expected "(string)" but found "( string)"',
                'line' => 12,
                'column' => 22,
            ],
            [
                'message' => 'Cast statements must not contain whitespace; expected "(float)" but found "(float )"',
                'line' => 13,
                'column' => 24,
            ],
            [
                'message' => "Cast statements must not contain whitespace; expected \"(bool)\" but found \"(\tbool\t)\"",
                'line' => 14,
                'column' => 17,
            ],
            [
                'message' => 'Cast statements must not contain whitespace; expected "(binary)" but found "(   binary   )"',
                'line' => 15,
                'column' => 19,
            ],
        ]);
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidCastSpacing.php');
    }
}
