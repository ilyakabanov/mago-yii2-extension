<?php

declare(strict_types=1);

namespace Corpus\Properties;

/**
 * Demonstrates valid private property declarations according to Yii2 coding standard.
 */
class ValidPrivateProperties
{
    private string $_typedProperty = 'valid';
    private static ?self $_singletonInstance = null;
    private readonly string $_readonlySecret;
    private int $_firstMulti = 1, $_secondMulti = 2;

    public function __construct(string $secret)
    {
        $this->_readonlySecret = $secret;
    }

    public static function getInstance(): self
    {
        if (self::$_singletonInstance === null) {
            self::$_singletonInstance = new self('secret');
        }

        return self::$_singletonInstance;
    }

    public function getTypedProperty(): string
    {
        return $this->_typedProperty;
    }

    public function getFirstMulti(): int
    {
        return $this->_firstMulti;
    }

    public function getSecondMulti(): int
    {
        return $this->_secondMulti;
    }
}
