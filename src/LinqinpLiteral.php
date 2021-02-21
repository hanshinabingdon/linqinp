<?php

namespace Linqinp;

/**
 * Class LinqinpLiteral
 * @package Linqinp
 */
class LinqinpLiteral
{
    /** @var string */
    public static string $errorKeyDuplicate = 'The key is duplicated.';

    /** @var string */
    public static string $errorNoValue = 'No value meets criteria.';

    /** @var string */
    public static string $errorTooMuchValues ='Too much values meet criteria';

    /** @var string */
    private const errorCallableReturnTypeBase = 'The callable return value type must be ';

    /** @var string */
    public static string $errorCallableReturnTypeInt = self::errorCallableReturnTypeBase . 'int,';

    /** @var string */
    public static string $errorCallableReturnTypeBool = self::errorCallableReturnTypeBase . 'bool,';
}
