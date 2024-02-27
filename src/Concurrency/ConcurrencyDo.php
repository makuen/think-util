<?php

namespace Makuen\ThinkUtil\Concurrency;

use Makuen\ThinkUtil\Pipeline\PipelineService;

/**
 * 并发操作封装
 * 比如我需要统计用户表的所有用户的上个月的在线时长
 * 查询出用户列表后一条一条的处理比较慢 且大部分都是数据库io
 * 这个时候可以使用这个类进行并发统计 提高效率
 */
class ConcurrencyDo
{
    protected \Generator $func;
    protected int $batchNum = 1;

    /**
     * 批量新增要执行的方法 传入生成器
     * @param \Generator $generator
     * @return $this
     */
    public function addBatch(\Generator $generator): ConcurrencyDo
    {
        $this->func = $generator;

        return $this;
    }

    /**
     * 新增一个要执行的方法 传入闭包 加到生成器
     * @param \Closure $func
     * @return $this
     */
    public function addOne(\Closure $func): ConcurrencyDo
    {
        $this->func = (function () use ($func) {
            yield from $this->func;
            yield $func;
        })();

        return $this;
    }

    /**
     * 每次批量执行的数量
     * @param int $num
     * @return $this
     */
    public function batchNum(int $num): ConcurrencyDo
    {
        $this->batchNum = $num;

        return $this;
    }

    /**
     * 开始执行
     * @return void
     */
    public function start(): void
    {
        $i = 1;
        $pipeService = (new PipelineService())->send([])->current();

        foreach ($this->func as $fun) {
            if ($i <= $this->batchNum) {
                $pipeService->through($fun);
            }

            $i++;

            if ($i > $this->batchNum) {
                $pipeService->getResult();
                $pipeService->clearThrough();
                $i = 1;
            }
        }

        $pipeService->getResult();
    }
}