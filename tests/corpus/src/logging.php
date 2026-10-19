<?php

declare(strict_types=1);

namespace Corpus;

use Throwable;
use yii\log\FileTarget;
use yii\log\Logger;
use yii\log\Target;

function acceptsFloat(float $value): void {}
function acceptsPayload(string|array|Throwable $value): void {}

function messageBuffers(Logger $logger, FileTarget $target): void
{
    foreach ($logger->messages as $message) {
        acceptsInt($message[1]);
        acceptsString($message[2]);
        acceptsFloat($message[3]);
        foreach ($message[4] as $frame) {
            acceptsString($frame['file']);
            acceptsInt($frame['line']);
        }
        // @mago-expect analysis:invalid-argument
        acceptsInt($message[2]);
    }
    foreach (Target::filterMessages($target->messages) as $message) {
        acceptsString($message[2]);
        acceptsFloat($message[3]);
        // @mago-expect analysis:invalid-argument
        acceptsString($message[1]);
    }
    \Mago\confirm(Target::filterMessages([]), 'array{}');
}
