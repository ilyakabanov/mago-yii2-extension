<?php

declare(strict_types=1);

namespace Parity;

/** @template T */
final class ExpectedType {}

/** @template T of object */
final class StaticType {}

/** The test hook expands this marker using the installed Yii logger contract. */
final class LogPayload {}

function assertType(mixed $actual, mixed $expected): void {}
function assertSuperType(mixed $actual, mixed $expected): void {}
