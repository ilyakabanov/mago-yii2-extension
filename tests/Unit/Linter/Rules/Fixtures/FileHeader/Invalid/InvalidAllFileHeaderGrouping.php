<?php

declare(ticks=1);

namespace First;

declare(ticks=2);

namespace Second;

use Vendor\Alpha;

use function Vendor\first;

use Vendor\Beta;

use const Vendor\FIRST;

use function Vendor\second;

use const Vendor\SECOND;

final class InvalidAllFileHeaderGrouping
{
}
