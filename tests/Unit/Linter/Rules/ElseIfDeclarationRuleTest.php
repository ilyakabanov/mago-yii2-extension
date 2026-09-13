<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\ElseIfDeclarationRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class ElseIfDeclarationRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/ElseIfDeclaration';

    protected function getRuleCode(): string
    {
        return 'yii2/else-if-declaration';
    }

    public function testDefinition(): void
    {
        $definition = (new ElseIfDeclarationRule())->getDefinition();

        self::assertSame('yii2/else-if-declaration', $definition->code);
        self::assertSame('Else-if declaration', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::IfStatementBodyElseClause], $definition->targets);
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidElseIfDeclaration.php');
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidElseIfDeclaration.php', [
            [
                'message' => 'Usage of "else if" is discouraged; use "elseif" instead.',
                'line' => 12,
                'column' => 11,
            ],
        ]);
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidElseIfDeclaration.php');
    }
}
