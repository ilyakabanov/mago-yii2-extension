<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\MethodDeclarationRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class MethodDeclarationRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/MethodDeclaration';

    protected function getRuleCode(): string
    {
        return 'yii2/method-declaration';
    }

    public function testDefinition(): void
    {
        $definition = (new MethodDeclarationRule())->getDefinition();

        self::assertSame('yii2/method-declaration', $definition->code);
        self::assertSame('Method declaration', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::Method], $definition->targets);
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidMethodDeclaration.php');
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidMethodDeclaration.php', [
            [
                'message' => 'Method name "_legacy" must not use an underscore to indicate visibility.',
                'line' => 9,
                'column' => 21,
            ],
        ]);
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidMethodDeclaration.php');
    }
}
