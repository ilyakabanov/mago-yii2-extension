<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use PHPUnit\Framework\Attributes\TestWith;
use yii\web\HeaderCollection;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see HeaderCollection::get()} return types in PHPStan analysis.
 *
 * Verifies type inference across positional and named argument combinations, returning `string` when the `$first`
 * parameter is `true`, an array when it is `false`, and a union when it is indeterminate, with `null` removed when the
 * default value cannot be `null`.
 */
final class HeaderCollectionDynamicMethodReturnType
{
    public function testReturnArrayWhenFirstIsFalse(): void
    {
        $headers = new HeaderCollection();

        \Parity\assertType($headers->get('Accept', null, false), \parity_expected_8eea9ea970df0c7c4a37());
        \Parity\assertType($headers->get('Accept', [], false), \parity_expected_164236dd58ccef737b17());
        \Parity\assertType($headers->get('Accept', ['default'], false), \parity_expected_164236dd58ccef737b17());
    }

    /**
     * @param array{0: string|null} $arguments
     */
    #[TestWith([['fallback']])]
    #[TestWith([[null]])]
    public function testReturnDeclaredTypeWhenArgumentIsUnpacked(array $arguments): void
    {
        $headers = new HeaderCollection();

        \Parity\assertType($headers->get('Accept', ...[null]), \parity_expected_25a45dc86aae4028edf8());
        \Parity\assertType($headers->get('Accept', ...$arguments), \parity_expected_25a45dc86aae4028edf8());
    }

    public function testReturnStringWhenFirstIsTrue(): void
    {
        $headers = new HeaderCollection();

        \Parity\assertType($headers->get('Content-Type'), \parity_expected_25a45dc86aae4028edf8());
        \Parity\assertType($headers->get('Content-Type', null), \parity_expected_25a45dc86aae4028edf8());
        \Parity\assertType($headers->get('Content-Type', null, true), \parity_expected_25a45dc86aae4028edf8());
        \Parity\assertType($headers->get('Content-Type', 'default'), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($headers->get('Content-Type', 'default', true), \parity_expected_473287f8298dba7163a8());
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testReturnUnionWhenFirstIsIndeterminate(bool $first): void
    {
        $headers = new HeaderCollection();

        \Parity\assertType($headers->get('User-Agent', null, $first), \parity_expected_d7aff6102606d00c63f3());
        \Parity\assertType($headers->get('User-Agent', 'default', $first), \parity_expected_a514b1d7033363e75344());
    }

    public function testReturnWithArrayDefaultAndFirstTrue(): void
    {
        $headers = new HeaderCollection();

        // when default is array but first is `true`, still returns `string`
        \Parity\assertType($headers->get('X-Forwarded-For', '127.0.0.1', true), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($headers->get('X-Forwarded-For', ['127.0.0.1'], false), \parity_expected_164236dd58ccef737b17());
    }

    public function testReturnWithBooleanFirstParameter(): void
    {
        $headers = new HeaderCollection();

        // explicit `true`
        \Parity\assertType($headers->get('Content-Length', null, true), \parity_expected_25a45dc86aae4028edf8());
        \Parity\assertType($headers->get('Content-Length', '0', true), \parity_expected_473287f8298dba7163a8());

        // explicit `false`
        \Parity\assertType($headers->get('Accept-Encoding', null, false), \parity_expected_8eea9ea970df0c7c4a37());
        \Parity\assertType($headers->get('Accept-Encoding', [], false), \parity_expected_164236dd58ccef737b17());
    }

    public function testReturnWithComplexScenarios(): void
    {
        $headers = new HeaderCollection();

        // no arguments beyond name - should default to first=`true`
        \Parity\assertType($headers->get('Host'), \parity_expected_25a45dc86aae4028edf8());

        // only name and default
        \Parity\assertType($headers->get('Referer', 'http://example.com'), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($headers->get('Origin', null), \parity_expected_25a45dc86aae4028edf8());

        // all three arguments with various combinations
        \Parity\assertType($headers->get('Accept-Language', 'en-US', true), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($headers->get('Accept-Charset', ['utf-8'], false), \parity_expected_164236dd58ccef737b17());
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testReturnWithDynamicBooleanFirstParameter(bool $first): void
    {
        $headers = new HeaderCollection();

        \Parity\assertType($headers->get('Connection', null, $first), \parity_expected_d7aff6102606d00c63f3());
        \Parity\assertType($headers->get('Connection', 'keep-alive', $first), \parity_expected_a514b1d7033363e75344());
    }

    public function testReturnWithExplicitNullDefault(): void
    {
        $headers = new HeaderCollection();

        \Parity\assertType($headers->get('Expires', null), \parity_expected_25a45dc86aae4028edf8());
        \Parity\assertType($headers->get('Expires', null, true), \parity_expected_25a45dc86aae4028edf8());
        \Parity\assertType($headers->get('Expires', null, false), \parity_expected_8eea9ea970df0c7c4a37());
    }

    #[TestWith(['string-default'])]
    #[TestWith([null])]
    public function testReturnWithMixedTypeDefault(string|null $default): void
    {
        $headers = new HeaderCollection();

        \Parity\assertType($headers->get('X-Request-ID', $default, true), \parity_expected_25a45dc86aae4028edf8());
    }

    public function testReturnWithNamedArguments(): void
    {
        $headers = new HeaderCollection();

        \Parity\assertType($headers->get('Accept', first: false), \parity_expected_8eea9ea970df0c7c4a37());
        \Parity\assertType($headers->get(first: false, name: 'Accept'), \parity_expected_8eea9ea970df0c7c4a37());
        \Parity\assertType($headers->get('Accept', default: 'text/html'), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($headers->get(name: 'Accept', first: true, default: 'text/html'), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($headers->get('Accept', default: ['text/html'], first: false), \parity_expected_164236dd58ccef737b17());
    }

    public function testReturnWithNonNullDefault(): void
    {
        $headers = new HeaderCollection();

        \Parity\assertType($headers->get('Authorization', 'Bearer token'), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($headers->get('Authorization', 'Bearer token', true), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($headers->get('Authorization', ['Bearer token'], false), \parity_expected_164236dd58ccef737b17());
    }

    #[TestWith(['fallback'])]
    #[TestWith([null])]
    public function testReturnWithNullableDefault(string|null $default): void
    {
        $headers = new HeaderCollection();

        \Parity\assertType($headers->get('X-Custom-Header', $default), \parity_expected_25a45dc86aae4028edf8());
        \Parity\assertType($headers->get('X-Custom-Header', $default, true), \parity_expected_25a45dc86aae4028edf8());
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testReturnWithNullableDefaultVariable(bool $useFallback): void
    {
        $headers = new HeaderCollection();

        $default = $useFallback ? 'fallback' : null;
        $defaults = $useFallback ? ['fallback'] : null;

        \Parity\assertType($headers->get('X-Custom-Header', $default), \parity_expected_25a45dc86aae4028edf8());
        \Parity\assertType($headers->get('X-Custom-Header', default: $default), \parity_expected_25a45dc86aae4028edf8());
        \Parity\assertType($headers->get('X-Custom-Header', $defaults, false), \parity_expected_8eea9ea970df0c7c4a37());
    }

    public function testReturnWithVariableDefault(): void
    {
        $headers = new HeaderCollection();

        $defaultValue = 'fallback-value';

        \Parity\assertType($headers->get('Cache-Control', $defaultValue), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($headers->get('Cache-Control', $defaultValue, true), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($headers->get('Cache-Control', [$defaultValue], false), \parity_expected_164236dd58ccef737b17());
    }
}
