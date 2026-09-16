<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\ClosingBraceRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class ClosingBraceRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/ClosingBrace';

    protected function getRuleCode(): string
    {
        return 'yii2/closing-brace';
    }

    public function testDefinition(): void
    {
        $definition = (new ClosingBraceRule())->getDefinition();

        self::assertSame('yii2/closing-brace', $definition->code);
        self::assertSame('Closing brace', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame(
            [
                NodeKind::Class_,
                NodeKind::Interface,
                NodeKind::Trait,
                NodeKind::Enum,
                NodeKind::Function,
                NodeKind::Method,
            ],
            $definition->targets,
        );
    }

    public function testAcceptsValidAndFormatterCoveredFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidClosingBrace.php');
    }

    public function testDetectsTrailingComments(): void
    {
        $message = 'Closing brace must not be followed by any comment or statement on the same line';

        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidClosingBrace.php', [
            [
                'message' => $message,
                'line' => 9,
                'column' => 1,
            ],
            [
                'message' => $message,
                'line' => 13,
                'column' => 1,
            ],
            [
                'message' => $message,
                'line' => 17,
                'column' => 1,
            ],
            [
                'message' => $message,
                'line' => 21,
                'column' => 1,
            ],
            [
                'message' => $message,
                'line' => 25,
                'column' => 1,
            ],
            [
                'message' => $message,
                'line' => 31,
                'column' => 5,
            ],
        ]);
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidClosingBrace.php');
    }
}
