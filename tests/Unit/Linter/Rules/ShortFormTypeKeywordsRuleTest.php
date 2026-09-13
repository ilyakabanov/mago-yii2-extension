<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\ShortFormTypeKeywordsRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class ShortFormTypeKeywordsRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/ShortFormTypeKeywords';

    protected function getRuleCode(): string
    {
        return 'yii2/short-form-type-keywords';
    }

    public function testDefinition(): void
    {
        $definition = (new ShortFormTypeKeywordsRule())->getDefinition();

        self::assertSame('yii2/short-form-type-keywords', $definition->code);
        self::assertSame('Short-form type keywords', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::UnaryPrefixOperator], $definition->targets);
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidShortFormTypeKeywords.php');
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidShortFormTypeKeywords.php', [
            [
                'message' => 'Long-form cast "(boolean)" must use "(bool)".',
                'line' => 11,
                'column' => 17,
            ],
            [
                'message' => 'Long-form cast "(boolean)" must use "(bool)".',
                'line' => 12,
                'column' => 19,
            ],
            [
                'message' => 'Long-form cast "(integer)" must use "(int)".',
                'line' => 13,
                'column' => 20,
            ],
        ]);
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidShortFormTypeKeywords.php');
    }
}
