<?php

// 格式器示例

// 从A表中查询了10条数据 对这10数据进行格式化
// 时间戳转为字符串，钱转为两位小数等等

class AFormatter extends \Makuan\ThinkUtil\Formatter\BaseFormatter
{
    public function create_time(): string
    {
        return date("Y-m-d H:i:s", $this->model["create_time"]);
    }

    public function money(): string
    {
        return number_format($this->model["money"], 2);
    }
}

class Test
{
    public function main()
    {
        $formatter = (new AFormatter)->fields(["create_time", "money"])->formatter();

        // 对列表的create_time字段和money字段进行格式化
        $lists = \think\facade\Db::table("A")->limit(0, 10)
            ->select()
            ->each($formatter);
    }
}