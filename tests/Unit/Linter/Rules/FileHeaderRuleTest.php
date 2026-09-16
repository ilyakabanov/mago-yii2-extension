<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\FileHeaderRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class FileHeaderRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/FileHeader';

    protected function getRuleCode(): string
    {
        return 'yii2/file-header';
    }

    public function testDefinition(): void
    {
        $definition = (new FileHeaderRule())->getDefinition();

        self::assertSame('yii2/file-header', $definition->code);
        self::assertSame('File header', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::Program], $definition->targets);
    }

    public function testAcceptsDeclarationDocblock(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidDeclarationDocblock.php');
    }

    public function testAcceptsValidHeaderVariants(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidCanonicalFileHeader.php');
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidMixedTemplate.php');
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidBracketedNamespace.php');
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidBracketedDeclare.php');
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidVarDocblock.php');
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidCodeDocblock.php');
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidEmptyPhpFile.php');
    }

    public function testDetectsHeaderAfterEarlierContent(): void
    {
        $message = 'The file header must be the first content in the file';

        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidFileHeaderPosition.php', [[
            'message' => $message,
            'line' => 5,
            'column' => 1,
        ]]);
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidFileHeaderPositionAfterFunction.php', [[
            'message' => $message,
            'line' => 7,
            'column' => 1,
        ]]);
    }

    public function testDetectsIncorrectBlockGrouping(): void
    {
        $message = 'Similar statements must be grouped together inside header blocks; ';

        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidFileHeaderGrouping.php', [[
            'message' => $message . 'the first "use" statement was found on line 5',
            'line' => 9,
            'column' => 1,
        ]]);
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidAllFileHeaderGrouping.php', [
            [
                'message' => $message . 'the first "declare" statement was found on line 3',
                'line' => 7,
                'column' => 1,
            ],
            [
                'message' => $message . 'the first "namespace" statement was found on line 5',
                'line' => 9,
                'column' => 1,
            ],
            [
                'message' => $message . 'the first "use" statement was found on line 11',
                'line' => 15,
                'column' => 1,
            ],
            [
                'message' => $message . 'the first "use function" statement was found on line 13',
                'line' => 19,
                'column' => 1,
            ],
            [
                'message' => $message . 'the first "use const" statement was found on line 17',
                'line' => 21,
                'column' => 1,
            ],
        ]);
    }

    public function testDetectsIncorrectBlockOrder(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidFileHeaderOrder.php', [
            [
                'message' => 'The declare statements must follow the opening PHP tag in the file header',
                'line' => 5,
                'column' => 1,
            ],
            [
                'message' => 'The class-based use imports must follow the namespace declaration in the file header',
                'line' => 9,
                'column' => 1,
            ],
        ]);
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidAllFileHeaderOrder.php', [
            [
                'message' => 'The file-level docblock must follow the opening PHP tag in the file header',
                'line' => 5,
                'column' => 1,
            ],
            [
                'message' => 'The function-based use imports must follow the class-based use imports in the file header',
                'line' => 11,
                'column' => 1,
            ],
            [
                'message' => 'The class-based use imports must follow the namespace declaration in the file header',
                'line' => 13,
                'column' => 1,
            ],
        ]);
    }

    public function testDetectsBlankLinesInsideRepeatedHeaderBlocks(): void
    {
        $message = 'Header blocks must not contain blank lines';

        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidRepeatedBlockSpacing.php', [
            [
                'message' => $message,
                'line' => 5,
                'column' => 17,
            ],
            [
                'message' => $message,
                'line' => 9,
                'column' => 16,
            ],
        ]);
    }

    public function testDetectsMissingBlankLineAfterFileDocblock(): void
    {
        $expectedIssue = [[
            'message' => 'Header blocks must be separated by a single blank line',
            'line' => 3,
            'column' => 18,
        ]];

        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidFileDocblockSpacing.php', $expectedIssue);
        $this->assertInvalidFixtureFile(
            self::FIXTURES_DIR . '/Invalid/InvalidFileDocblockBeforeCode.php',
            $expectedIssue,
        );
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidFileHeaderPosition.php');
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR
        . '/Invalid/InvalidFileHeaderPositionAfterFunction.php');
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidFileHeaderGrouping.php');
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidAllFileHeaderGrouping.php');
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidFileHeaderOrder.php');
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidAllFileHeaderOrder.php');
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidRepeatedBlockSpacing.php');
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidFileDocblockSpacing.php');
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidFileDocblockBeforeCode.php');
    }
}
