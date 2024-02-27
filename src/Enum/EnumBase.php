<?php

namespace Makuen\ThinkUtil\Enum;

/**
 * 仿照php8.1枚举类写的一个基类 方便在php7 php8.0中使用
 *
 * 可以在参数中做类型限制
 */
abstract class EnumBase
{
    static array $constants = [];

    protected int $value;

    public abstract function message(): string;

    public function value(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param int $value
     * @return static
     */
    public static function fromValue(int $value): EnumBase
    {
        $enum = new static();

        $enum->setValue($value);

        return $enum;
    }

    public static function __callStatic($name, $arguments): self
    {
        //存入静态变量 不用每次都反射获取
        if (!isset(static::$constants[static::class])) {
            $refClass = new \ReflectionClass(static::class);
            static::$constants[static::class] = $refClass->getConstants();
        }

        if (!array_key_exists($name, static::$constants[static::class])) {
            throw new \InvalidArgumentException(static::class . "::class 不存在枚举值:" . $name);
        }

        $value = static::$constants[static::class][$name];

        return (new static())->setValue((int)$value);
    }
}