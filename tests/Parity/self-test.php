<?php

declare(strict_types=1);

/** @return \Parity\ExpectedType<int> */
function parity_expected_int(): mixed { throw new LogicException(); }
/** @return \Parity\ExpectedType<string> */
function parity_expected_string(): mixed { throw new LogicException(); }

function parity_self_test(int $number, string $text): void
{
    \Parity\assertType($number, parity_expected_int());
    \Parity\assertSuperType($number, parity_expected_int());
    \Parity\assertType($text, parity_expected_int());
    \Parity\assertType(1, parity_expected_int());
    \Parity\assertSuperType(1, parity_expected_int());
}
