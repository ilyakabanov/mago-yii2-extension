<?php

declare(strict_types=1);

namespace Fixture;

use Vendor\Package\Deep\Namespace\Service;
use Vendor\Package\{Service as AliasedService, Http\Client};
use function Vendor\Package\{run, Http\request};
use const Vendor\Package\{VERSION, Config\NAME};

final class ValidCompoundNamespaceDepth
{
}
