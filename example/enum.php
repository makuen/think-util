<?php

/**
 * 枚举基类使用示例
 */


/**
 * @method static Six Man()
 * @method static Six Woman()
 */
class Six extends \Makuen\ThinkUtil\Enum\EnumBase
{
    public const Man = 1;
    public const Woman = 2;

    public function message(): string
    {
        switch ($this->value) {
            case self::Man:
                return "男";
            case self::Woman:
                return "女";
        }

        return "";
    }

    public function like(): string
    {
        switch ($this->value) {
            case self::Man:
                return "喜欢女";
            case self::Woman:
                return "喜欢男";
        }

        return "";
    }
}

class Test
{
    public function main()
    {
        $this->do(Six::Man());
        $this->do(Six::Woman());
        $this->do(Six::fromValue(1));
        $this->do(Six::fromValue(2));
    }

    public function do(Six $six): void
    {
        if ($six == Six::Man()) {
            echo "男";
        }

        if ($six->value() == 1) {
            echo "男";
        }

        if ($six == Six::Woman()) {
            echo "女";
        }

        if ($six->value() == 2) {
            echo "女";
        }
    }
}