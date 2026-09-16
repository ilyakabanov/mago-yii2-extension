<?php

declare(strict_types=1);

namespace Fixture;

final class InvalidClassClosingBrace
{
}// class comment

interface InvalidInterfaceClosingBrace
{
} # interface comment

trait InvalidTraitClosingBrace
{
} /* trait comment */

enum InvalidEnumClosingBrace
{
} /** enum docblock comment */

function invalidFunctionClosingBrace(): void
{
} // function comment

final class InvalidMethodClosingBrace
{
    public function invalidMethodClosingBrace(): void
    {
    } # method comment
}
