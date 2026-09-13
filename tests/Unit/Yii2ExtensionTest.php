<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests;

use Ilyakabanov\MagoYii2\Linter\Rules\ClassInstantiationRule;
use Ilyakabanov\MagoYii2\Linter\Rules\ConstantVisibilityRule;
use Ilyakabanov\MagoYii2\Linter\Rules\ElseIfDeclarationRule;
use Ilyakabanov\MagoYii2\Linter\Rules\ImportStatementRule;
use Ilyakabanov\MagoYii2\Linter\Rules\MethodScopeRule;
use Ilyakabanov\MagoYii2\Linter\Rules\PrivatePropertyUnderscoreRule;
use Ilyakabanov\MagoYii2\Linter\Rules\ShortFormTypeKeywordsRule;
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
        self::assertCount(7, $extension->linterRules);
        self::assertInstanceOf(PrivatePropertyUnderscoreRule::class, $extension->linterRules[0]);
        self::assertInstanceOf(ElseIfDeclarationRule::class, $extension->linterRules[1]);
        self::assertInstanceOf(ShortFormTypeKeywordsRule::class, $extension->linterRules[2]);
        self::assertInstanceOf(MethodScopeRule::class, $extension->linterRules[3]);
        self::assertInstanceOf(ConstantVisibilityRule::class, $extension->linterRules[4]);
        self::assertInstanceOf(ImportStatementRule::class, $extension->linterRules[5]);
        self::assertInstanceOf(ClassInstantiationRule::class, $extension->linterRules[6]);
        self::assertCount(1, $extension->analyzerPlugins);
        self::assertNull($extension->workerReducer);
    }
}
