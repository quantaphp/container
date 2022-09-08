<?php

declare(strict_types=1);

namespace Quanta\Container;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \Exception implements ContainerExceptionInterface
{
    const FACTORY = 'Cannot get \'%s\' from the container: factory has thrown an uncaught exception';
    const ALIAS = 'Cannot get \'%s\' from the container: getting \'%s\' value has thrown an uncaught exception';
    const ABSTRACT = 'Container cannot instantiate abstract class %s';
    const PRIVATE = 'Container cannot instantiate class with protected/private constructor %s';
    const TYPE_UNION = 'Container cannot instantiate %s: parameter $%s has union type';
    const TYPE_INTERSECTION = 'Container cannot instantiate %s: parameter $%s has intersection type';
    const TYPE_BUILTIN = 'Container cannot instantiate %s: parameter $%s type is not a class name';
    const TYPE_RECURSIVE = 'Container cannot instantiate %s: parameter $%s value has the same type, this would trigger infinite recursion';
    const TYPE_ERROR = 'Container cannot instantiate %s: getting parameter $%s value has thrown an uncaught exception (type: %s)';

    public static function factory(string $id): string
    {
        return sprintf(self::FACTORY, $id);
    }

    public static function alias(string $id, string $alias): string
    {
        return sprintf(self::ALIAS, $id, $alias);
    }

    public static function abstract(string $id): string
    {
        return sprintf(self::ABSTRACT, $id);
    }

    public static function private(string $id): string
    {
        return sprintf(self::PRIVATE, $id);
    }

    public static function typeUnion(string $id, string $param): string
    {
        return sprintf(self::TYPE_UNION, $id, $param);
    }

    public static function typeIntersection(string $id, string $param): string
    {
        return sprintf(self::TYPE_INTERSECTION, $id, $param);
    }

    public static function typeBuiltin(string $id, string $param): string
    {
        return sprintf(self::TYPE_BUILTIN, $id, $param);
    }

    public static function typeRecursive(string $id, string $param): string
    {
        return sprintf(self::TYPE_RECURSIVE, $id, $param);
    }

    public static function typeError(string $id, string $type, string $param): string
    {
        return sprintf(self::TYPE_ERROR, $id, $param, $type);
    }

    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
