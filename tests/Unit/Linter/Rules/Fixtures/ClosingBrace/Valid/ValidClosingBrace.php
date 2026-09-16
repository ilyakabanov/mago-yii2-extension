<?php

declare(strict_types=1);

namespace Fixture;

final class ValidClassClosingBrace
{
}
// comment on the following line

interface ValidInterfaceClosingBrace
{
    public function withoutBody(): void;
}

trait ValidTraitClosingBrace
{
}

enum ValidEnumClosingBrace
{
}

function validFunctionClosingBrace(): void
{
}

abstract class ValidMethodClosingBrace
{
    abstract public function abstractMethod(): void;

    public function concreteMethod(): void
    {
    }
}

$anonymousClass = new class {}; // anonymous class continuation
$closure = function (): void {}; // closure continuation
$arrow = fn(): bool => true;

class FormatterCoveredStatement
{
} echo 'formatter-covered'; // statement comment
