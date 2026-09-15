<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\TraitUseDeclarationRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class TraitUseDeclarationRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/TraitUseDeclaration';

    protected function getRuleCode(): string
    {
        return 'yii2/trait-use-declaration';
    }

    public function testDefinition(): void
    {
        $definition = (new TraitUseDeclarationRule())->getDefinition();

        self::assertSame('yii2/trait-use-declaration', $definition->code);
        self::assertSame('Trait use declaration', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame(
            [NodeKind::Class_, NodeKind::Trait, NodeKind::Enum, NodeKind::AnonymousClass],
            $definition->targets,
        );
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidTraitUseDeclaration.php');
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidTraitUseDeclaration.php', [
            [
                'message' => 'Trait imports must be declared before other class members.',
                'line' => 11,
                'column' => 5,
            ],
            [
                'message' => 'Trait imports must be grouped together before other class members.',
                'line' => 17,
                'column' => 5,
            ],
        ]);
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidTraitUseDeclaration.php');
    }
}
