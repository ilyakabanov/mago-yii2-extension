<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\ImportStatementRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class ImportStatementRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/ImportStatement';

    protected function getRuleCode(): string
    {
        return 'yii2/import-statement';
    }

    public function testDefinition(): void
    {
        $definition = (new ImportStatementRule())->getDefinition();

        self::assertSame('yii2/import-statement', $definition->code);
        self::assertSame('Import statement', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::Use], $definition->targets);
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidImportStatement.php');
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidImportStatement.php', [
            [
                'message' => 'Import statements must not begin with a leading backslash.',
                'line' => 7,
                'column' => 5,
            ],
            [
                'message' => 'Import statements must not begin with a leading backslash.',
                'line' => 8,
                'column' => 14,
            ],
            [
                'message' => 'Import statements must not begin with a leading backslash.',
                'line' => 9,
                'column' => 11,
            ],
            [
                'message' => 'Import statements must not begin with a leading backslash.',
                'line' => 10,
                'column' => 5,
            ],
        ]);
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidImportStatement.php');
    }
}
