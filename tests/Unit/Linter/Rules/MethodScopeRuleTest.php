<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\MethodScopeRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class MethodScopeRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/MethodScope';

    protected function getRuleCode(): string
    {
        return 'yii2/method-scope';
    }

    public function testDefinition(): void
    {
        $definition = (new MethodScopeRule())->getDefinition();

        self::assertSame('yii2/method-scope', $definition->code);
        self::assertSame('Method scope', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::Method], $definition->targets);
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidMethodScope.php');
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidMethodScope.php', [
            [
                'message' => 'Visibility must be declared on method "missingVisibility".',
                'line' => 9,
                'column' => 5,
            ],
            [
                'message' => 'Visibility must be declared on method "missingStaticVisibility".',
                'line' => 13,
                'column' => 12,
            ],
            [
                'message' => 'Visibility must be declared on method "__construct".',
                'line' => 17,
                'column' => 5,
            ],
        ]);
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidMethodScope.php');
    }
}
