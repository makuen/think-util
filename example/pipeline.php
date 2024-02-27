<?php

// 管道示例

class Test
{
    public function main()
    {
        $data = [
            "result" => 0
        ];

        // through方法接收一个闭包做为参数
        // 闭包需要接收一个参数 这个参数就是send方法传入的参数
        // 上一个闭包的返回值将作为下一个闭包的参数

        $pipeline = (new Makuen\ThinkUtil\Pipeline\PipelineService)->send($data);

        $result = $pipeline
            // 对result + 1
            ->through(function ($data) {
                $data["result"] += 1;

                return $data;
            })
            // 对result + 2
            ->through(function ($data) {
                $data["result"] += 2;

                return $data;
            })
            ->getResult();
    }
}

class Test2
{
    public function main()
    {
        // 管道类的另一个用法 并发执行
        // 统计周一~周五每天做了什么工作

        $data = [
            "周一" => "",
            "周二" => "",
            "周三" => "",
            "周四" => "",
            "周五" => ""
        ];

        // 5个闭包并发执行 大幅减少io时间
        // 因为是并发执行 无法控制执行顺序 所以就不能把上一个闭包的结果当做下一个闭包的参数使用
        // 数组的话需要使用不同的下标 防止冲突

        $pipeline = (new Makuen\ThinkUtil\Pipeline\PipelineService)->current()->send($data);

        $result = $pipeline
            // 统计周一的数据
            ->through(function ($data) {
                $data["周一"] = "干了xxx件事";

                return $data;
            })
            // 统计周二的数据
            ->through(function ($data) {
                $data["周二"] = "干了xxx件事";

                return $data;
            })
            // 统计周三的数据
            ->through(function ($data) {
                $data["周三"] = "干了xxx件事";

                return $data;
            })
            // 统计周四的数据
            ->through(function ($data) {
                $data["周四"] = "干了xxx件事";

                return $data;
            })
            // 统计周五的数据
            ->through(function ($data) {
                $data["周五"] = "干了xxx件事";

                return $data;
            })
            ->getResult();
    }
 }