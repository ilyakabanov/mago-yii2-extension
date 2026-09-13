<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\ClassDeclarationRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class ClassDeclarationRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/ClassDeclaration';

    protected function getRuleCode(): string
    {
        return 'yii2/class-declaration';
    }

    public function testDefinition(): void
    {
        $definition = (new ClassDeclarationRule())->getDefinition();

        self::assertSame('yii2/class-declaration', $definition->code);
        self::assertSame('Class declaration', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame(
            [NodeKind::Class_, NodeKind::Interface, NodeKind::Trait, NodeKind::Enum],
            $definition->targets,
        );
    }

    public function testAcceptsValidFixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidClassDeclaration.php');
    }

    public function testAcceptsValidPhp82Fixture(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidPhp82ClassDeclaration.php', phpVersion: '8.2');
    }

    public function testDetectsInvalidFixture(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidClassDeclaration.php', [
            [
                'message' => 'Exactly one space is required between the class modifier and keyword.',
                'line' => 7,
                'column' => 8,
            ],
            [
                'message' => 'Exactly one space is required after the class-like keyword.',
                'line' => 11,
                'column' => 12,
            ],
            [
                'message' => 'The `extends` keyword must be on the class-like declaration line.',
                'line' => 16,
                'column' => 5,
            ],
            [
                'message' => 'The `implements` keyword must be on the class-like declaration line.',
                'line' => 16,
                'column' => 23,
            ],
            [
                'message' => 'A single-line `implements` list requires one space after each comma.',
                'line' => 16,
                'column' => 48,
            ],
            [
                'message' => 'Each item in a multi-line `implements` list must be on its own line.',
                'line' => 21,
                'column' => 20,
            ],
            [
                'message' => 'Multi-line `implements` items must be indented four spaces from the declaration.',
                'line' => 22,
                'column' => 7,
            ],
            [
                'message' => 'The first item in a multi-line `implements` list must follow the keyword on the next line.',
                'line' => 26,
                'column' => 35,
            ],
            [
                'message' => 'The opening brace must be on the line after the class-like declaration.',
                'line' => 31,
                'column' => 20,
            ],
            [
                'message' => 'The opening brace must be the only content on its line.',
                'line' => 35,
                'column' => 1,
            ],
            [
                'message' => 'The opening brace must be aligned with the class-like declaration.',
                'line' => 39,
                'column' => 3,
            ],
            [
                'message' => 'The closing brace must be on the line immediately after the class-like body.',
                'line' => 47,
                'column' => 1,
            ],
            [
                'message' => 'The closing brace must be the only code on its line.',
                'line' => 51,
                'column' => 1,
            ],
        ]);
    }

    public function testDetectsInvalidClauseSpacing(): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/InvalidClassClauseSpacing.php', [
            [
                'message' => 'Exactly one space is required before `extends`.',
                'line' => 7,
                'column' => 34,
            ],
            [
                'message' => 'Exactly one space is required after `extends`.',
                'line' => 7,
                'column' => 43,
            ],
        ]);
    }

    public function testDetectsInvalidPhp82Fixture(): void
    {
        $this->assertInvalidFixtureFile(
            self::FIXTURES_DIR . '/Invalid/InvalidPhp82ClassDeclaration.php',
            [
                [
                    'message' => 'Exactly one space is required between the class modifier and keyword.',
                    'line' => 7,
                    'column' => 11,
                ],
            ],
            phpVersion: '8.2',
        );
    }

    public function testDoesNotApplyAutoFix(): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/InvalidClassDeclaration.php');
    }
}
