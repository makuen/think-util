<?php

use Makuen\ThinkUtil\Concurrency\ConcurrencyDo;
use think\Db;

/**
 * 并发操作示例
 */
class Test
{
    public function main()
    {
        // 使用场景 从A表查出一堆数据 再插入到B表中
        // 注意: 在命令行模式下由于没有启动swoole服务，所以没有连接池
        // 要使用连接池需要该command继承SwooleCommand类
        // 在runInSwoole方法中就可以使用连接池 并发操作数据库

        $lists = Db::table("A")->cursor();

        $funGenerator = (function () use ($lists) {
            foreach ($lists as $a) {
                yield function () use ($a) {
                    Db::table("B")->insert($a);
                    echo $a["id"] . "插入成功";
                };
            }
        })->__invoke();

        // 10个并发
        (new ConcurrencyDo)->addBatch($funGenerator)->batchNum(10)->start();

    }
}