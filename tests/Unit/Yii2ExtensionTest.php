<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests;

use Ilyakabanov\MagoYii2\Yii2Extension;
use PHPUnit\Framework\TestCase;

final class Yii2ExtensionTest extends TestCase
{
    public function testFactoryOwnsStableRegistration(): void
    {
        $extension = Yii2Extension::create();

        self::assertSame('yii2/mago-extension', $extension->identifier);
        self::assertSame('Mago Yii2 Extension', $extension->name);
        self::assertSame('0.1.0', $extension->version);
        self::assertCount(0, $extension->linterRules);
        self::assertCount(1, $extension->analyzerPlugins);
        self::assertNull($extension->workerReducer);
    }
}
