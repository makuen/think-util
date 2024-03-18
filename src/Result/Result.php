<?php

namespace Makuen\ThinkUtil\Result;

use think\contract\Arrayable;

class Result implements Arrayable
{
    private int $state;
    private array $data;

    /**
     * 成功返回
     * @param array $data
     * @return Result
     */
    public static function Ok(array $data = []): Result
    {
        $r = new static();

        $r->setState(1);
        $r->setData($data);

        return $r;
    }

    /**
     * 失败返回
     * @param array $data
     * @return Result
     */
    public static function Err(array $data = []): Result
    {
        $r = new static();

        $r->setState(0);
        $r->setData($data);

        return $r;
    }

    /**
     * 获取数据
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 是否成功
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->state == 1;
    }

    /**
     * @internal set
     */
    protected function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @set
     */
    protected function setData(array $data)
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}