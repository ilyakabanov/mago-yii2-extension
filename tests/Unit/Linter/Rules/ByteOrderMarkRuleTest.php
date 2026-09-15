<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\ByteOrderMarkRule;
use Ilyakabanov\MagoYii2\Tests\Linter\RuleTestCase;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;
use PHPUnit\Framework\Attributes\TestWith;

final class ByteOrderMarkRuleTest extends RuleTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/ByteOrderMark';

    protected function getRuleCode(): string
    {
        return 'yii2/byte-order-mark';
    }

    public function testDefinition(): void
    {
        $definition = (new ByteOrderMarkRule())->getDefinition();

        self::assertSame('yii2/byte-order-mark', $definition->code);
        self::assertSame('Byte order mark', $definition->name);
        self::assertSame(Level::Error, $definition->defaultLevel);
        self::assertTrue($definition->defaultEnabled);
        self::assertSame([NodeKind::Program], $definition->targets);
    }

    public function testAcceptsFileWithoutByteOrderMark(): void
    {
        $this->assertValidFixtureFile(self::FIXTURES_DIR . '/Valid/ValidByteOrderMark.php');
    }

    #[TestWith(['InvalidUtf8ByteOrderMark.php', 'UTF-8'])]
    #[TestWith(['InvalidUtf16BigEndianByteOrderMark.php', 'UTF-16 (BE)'])]
    #[TestWith(['InvalidUtf16LittleEndianByteOrderMark.php', 'UTF-16 (LE)'])]
    public function testDetectsByteOrderMark(string $fixture, string $encoding): void
    {
        $this->assertInvalidFixtureFile(self::FIXTURES_DIR . '/Invalid/' . $fixture, [
            [
                'message' => "File contains {$encoding} byte order mark, which may corrupt your application.",
                'line' => 1,
                'column' => 1,
            ],
        ]);
    }

    #[TestWith(['InvalidUtf8ByteOrderMark.php'])]
    #[TestWith(['InvalidUtf16BigEndianByteOrderMark.php'])]
    #[TestWith(['InvalidUtf16LittleEndianByteOrderMark.php'])]
    public function testDoesNotApplyAutoFix(string $fixture): void
    {
        $this->assertFixtureIsNotModifiedByFix(self::FIXTURES_DIR . '/Invalid/' . $fixture);
    }
}
