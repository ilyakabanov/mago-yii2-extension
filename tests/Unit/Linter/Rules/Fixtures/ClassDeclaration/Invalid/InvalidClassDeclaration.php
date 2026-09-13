<?php

declare(strict_types=1);

namespace Fixture;

final  class BadModifierSpacing
{
}

interface  BadKeywordSpacing
{
}

class BadInheritance
    extends BaseClass implements FirstContract,SecondContract
{
}

class BadMultiLineList implements
    FirstContract, SecondContract,
      ThirdContract
{
}

class BadFirstItemLine implements FirstContract,
    SecondContract
{
}

class BadOpenBrace {
}

class BadBraceContent
{ public const VALUE = 1;
}

class BadBraceIndent
  {
}

class BadClosingBrace
{
    public const VALUE = 1;


}

class BadClosingBraceContent
{
} echo 'invalid';
