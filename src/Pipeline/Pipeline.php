<?php

namespace Makuen\ThinkUtil\Pipeline;

use Swoole\Coroutine;
use think\swoole\Sandbox;

/**
 * send 发送一个初始参数 依次调用管道方法
 * 上一个管道方法果作为下一个管道的参数
 * 所有管道都执行完成之后 返回最终结果
 *
 * 同时 如果有支持swoole 可以调用current() 方法 对闭包参数进行并发执行 提升效率
 */
class PipelineService
{
    // 管道执行方法
    protected array $through = [];
    // 初始数据
    protected $data;
    // 并行执行
    protected bool $current = false;

    /**
     * 发送初始参数
     * @param $data
     * @return $this
     */
    public function send($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * 并行执行 -- 方法参数不依赖于上一个方法的返回值才能使用
     * @return $this
     */
    public function current(): self
    {
        $this->current = true;

        return $this;
    }

    /**
     * 清空方法
     * @return $this
     */
    public function clearThrough(): self
    {
        $this->through = [];

        return $this;
    }

    /**
     * 增加管道方法
     * @param callable $handler
     * @return $this
     */
    public function through(callable $handler): self
    {
        $this->through[] = $handler;

        return $this;
    }

    /**
     * 顺序执行所有管道方法
     * @return $this
     */
    protected function do(): self
    {
        foreach ($this->through as $through) {
            $this->data = call_user_func($through, $this->data);
        }

        return $this;
    }

    /**
     * 并行执行所有管道方法
     * @return self
     */
    protected function currentDo(): self
    {
        if (!is_array($this->data) && !is_numeric($this->data)) {
            throw new \InvalidArgumentException("使用current执行只接受 数字和数组作为初始参数");
        }

        $wg = new Coroutine\WaitGroup();
        $channel = new Coroutine\Channel(count($this->through));
        $sandbox = app(Sandbox::class);
        $sandbox->init();

        foreach ($this->through as $through) {
            $wg->add();
            Coroutine::create(function () use ($through, $wg, $channel, $sandbox) {
                $sandbox->init();

                $res = call_user_func($through, $this->data);

                $channel->push($res);

                $wg->done();
            });
        }

        foreach ($this->through as $_through) {
            $res = $channel->pop();

            if (gettype($this->data) != gettype($res)) {
                throw new \BadFunctionCallException("current执行时 方法返回类型必须和参数一致");
            }

            if (is_numeric($this->data)) {
                $this->data += $res;
            }

            if (is_array($this->data)) {
                $this->data = array_merge($this->data, $res);
            }
        }

        $wg->wait();
        $channel->close();

        return $this;
    }

    /**
     * 获取最终结果
     * @return mixed
     */
    public function getResult()
    {
        if (!$this->current) {
            $this->do();
        } else {
            $this->currentDo();
        }

        return $this->data;
    }
}