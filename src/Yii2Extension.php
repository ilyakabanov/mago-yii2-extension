<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2;

use Ilyakabanov\MagoYii2\Analyzer\Yii2Plugin;
use Mago\Sdk\Extension;

/**
 * Constructs the complete Yii2 extension advertised by each worker process.
 *
 * @api
 */
final class Yii2Extension
{
    private const VERSION = '0.1.0';

    private function __construct() {}

    public static function create(): Extension
    {
        return new Extension(
            identifier: 'yii2/mago-extension',
            name: 'Mago Yii2 Extension',
            version: self::VERSION,
            linterRules: [],
            analyzerPlugins: [new Yii2Plugin()],
        );
    }
}
