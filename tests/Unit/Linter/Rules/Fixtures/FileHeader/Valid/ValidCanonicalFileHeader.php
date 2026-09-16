#!/usr/bin/env php
<?php

/** File header. */

declare(ticks=1);
declare(ticks=2);

namespace Fixture;

use Vendor\{Alpha, function first, const FIRST};
// Related import.
use Vendor\Beta;

use function Vendor\second;

use const Vendor\SECOND;

/** Documents the declaration. */
#[\Attribute]
final class ValidCanonicalFileHeader
{
}
