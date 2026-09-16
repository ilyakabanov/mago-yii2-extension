<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\DisallowAlternativePhpTagsRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;
use PHPUnit\Framework\Attributes\TestWith;

final class DisallowAlternativePhpTagsRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/DisallowAlternativePhpTags';

    protected function getRuleCode(): string
    {
        return 'yii2/disallow-alternative-php-tags';
    }

    public function testDefinition(): void
    {
        $definition = (new DisallowAlternativePhpTagsRule())->getDefinition();

        self::assertSame('yii2/disallow-alternative-php-tags', $definition->code);
        self::assertSame('Disallow alternative PHP tags', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::Inline], $definition->targets);
    }

    public function testAcceptsFileWithoutAlternativePhpTags(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidAlternativePhpTags.php');
    }

    #[TestWith([
        'InvalidScriptStyleTag.php',
        "Script style opening tag used; expected \"<?php\" but found \"<script language='PHP'>echo \$value;</script>\n\"",
    ])]
    #[TestWith([
        'InvalidAspStyleShortTag.php',
        "Possible use of ASP style short opening tags detected; found: <%= \$value . ' and some more text to make s...",
    ])]
    #[TestWith([
        'InvalidAspStyleTag.php',
        "Possible use of ASP style opening tags detected; found: <% echo \$value; %>\n",
    ])]
    public function testDetectsAlternativePhpTag(string $fixture, string $message): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/' . $fixture, [
            ['message' => $message, 'line' => 1, 'column' => 1],
        ]);
    }

    public function testUsesUpstreamPrecedencePerInlineNode(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidTagPrecedence.php', [
            [
                'message' => "Script style opening tag used; expected \"<?php\" but found \"<script language=php>second</script><% third %>\n\"",
                'line' => 1,
                'column' => 13,
            ],
            [
                'message' => "Possible use of ASP style short opening tags detected; found: <%= preferred even though later %>\n",
                'line' => 3,
                'column' => 13,
            ],
        ]);
    }

    #[TestWith(['InvalidScriptStyleTag.php'])]
    #[TestWith(['InvalidAspStyleShortTag.php'])]
    #[TestWith(['InvalidAspStyleTag.php'])]
    #[TestWith(['InvalidTagPrecedence.php'])]
    public function testDoesNotApplyAutoFix(string $fixture): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/' . $fixture);
    }
}
