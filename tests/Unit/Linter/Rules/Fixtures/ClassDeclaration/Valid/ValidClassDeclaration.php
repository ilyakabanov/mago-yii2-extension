<?php

declare(strict_types=1);

namespace Fixture;

final class ValidClass extends BaseClass implements FirstContract, SecondContract
{
}

class ValidMultiLineClass implements
    FirstContract,
    SecondContract
{
} // End class.

class ValidQualifiedNames extends Vendor\BaseClass implements Vendor\FirstContract, Vendor\SecondContract
{
}

class ValidCommentedClass
// A comment containing { must not be mistaken for the opening brace.
{
}

interface ValidInterface extends
    FirstContract,
    SecondContract
{
}

trait ValidTrait
{
}

enum ValidEnum: string implements LabelContract
{
    case Ready = 'ready';
}

$anonymous = new class {};
