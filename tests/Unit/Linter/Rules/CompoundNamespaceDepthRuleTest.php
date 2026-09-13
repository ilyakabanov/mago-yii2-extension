<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\CompoundNamespaceDepthRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class CompoundNamespaceDepthRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/CompoundNamespaceDepth';

    protected function getRuleCode(): string
    {
        return 'yii2/compound-namespace-depth';
    }

    public function testDefinition(): void
    {
        $definition = (new CompoundNamespaceDepthRule())->getDefinition();

        self::assertSame('yii2/compound-namespace-depth', $definition->code);
        self::assertSame('Compound namespace depth', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::MixedUseItemList, NodeKind::TypedUseItemList], $definition->targets);
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidCompoundNamespaceDepth.php');
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidCompoundNamespaceDepth.php', [
            [
                'message' => 'Names inside grouped imports must not exceed two namespace segments.',
                'line' => 7,
                'column' => 21,
            ],
            [
                'message' => 'Names inside grouped imports must not exceed two namespace segments.',
                'line' => 8,
                'column' => 30,
            ],
        ]);
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidCompoundNamespaceDepth.php');
    }
}
