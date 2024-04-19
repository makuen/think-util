<?php

namespace Makuen\ThinkUtil\Formatter;

use Co\WaitGroup;
use Swoole\Coroutine;
use think\contract\Arrayable;
use think\log\driver\File;
use think\Model;
use think\swoole\Sandbox;

/**
 * @property array|Model $model
 */
abstract class BaseFormatter
{
    protected File $log;
    protected WaitGroup $wg;
    protected bool $current;
    protected array $fields = [];
    protected array $hidden = [];
    protected array $only = [];
    protected array $param = [];
    protected $model;

    public function __construct(bool $current = false)
    {
        $this->current = $current;

        if ($current) {
            $this->wg = new WaitGroup();
        }

        $this->log = app(File::class);
    }

    /**
     * 记录日志
     * @param string $message
     * @param array $context
     * @param string $level
     * @return void
     */
    protected function log(string $message, array $context = [], string $level = "error"): void
    {
        if (!empty($context)) {
            $replace = [];
            foreach ($context as $key => $val) {
                if (is_array($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                } else if ($val instanceof Arrayable) {
                    $val = json_encode($val->toArray(), JSON_UNESCAPED_UNICODE);
                } else {
                    $val = (string)$val;
                }
                $replace['{' . $key . '}'] = $val;
            }

            $message = strtr($message, $replace);
        }

        $this->log->save([
            $level => [$message]
        ]);
    }

    /**
     * 传递参数 在格式器中使用
     * @param $param
     * @return self
     */
    public function setParam($param): self
    {
        if (is_callable($param)) {
            $param = $param();
        }

        $this->param = $param;

        return $this;
    }

    /**
     * 指定需要格式的字段
     * @param $fields
     * @return $this
     */
    public function fields($fields): self
    {
        if (is_callable($fields)) {
            $fields = $fields();
        }

        $this->fields = $fields;

        return $this;
    }

    /**
     * 指定需要隐藏的字段
     * @param $fields
     * @return $this
     */
    public function hidden($fields): self
    {
        if (is_callable($fields)) {
            $fields = $fields();
        }

        $this->hidden = $fields;

        return $this;
    }

    /**
     * 指定只展示的字段
     * @param $only
     * @return $this
     */
    public function only($only): self
    {
        if (is_callable($only)) {
            $only = $only();
        }

        $this->only = $only;

        return $this;
    }

    /**
     * 生成格式化处理器
     * @return \Closure
     */
    public function formatter(): \Closure
    {
        return function ($model) {

            $this->model = $model;

            foreach ($this->fields as $key => $field) {
                $method = $field;
                if (!is_numeric($key)) {
                    $field = $key;
                }

                if (!method_exists(static::class, $method)) {
                    throw new \InvalidArgumentException("method: $method is not exists in " . static::class);
                }

                if (in_array($field, $this->hidden)) {
                    continue;
                }

                if (is_array($model) || $model instanceof Arrayable) {
                    $model[$field] = call_user_func([$this, $method]);
                } else {
                    $model->{$field} = call_user_func([$this, $method]);
                }
            }

            foreach ($this->hidden as $field) {
                if (is_array($model || $model instanceof Arrayable)) {
                    unset($model[$field]);
                } else {
                    unset($model->{$field});
                }
            }

            // 只对数据和模型进行处理
            if ($this->only) {
                if ($model instanceof Arrayable) {
                    // 取缔掉模型里配置的append
                    if ($model instanceof Model) {
                        $model->append([]);
                    }

                    foreach ($model->toArray() as $k => $v) {
                        if (!in_array($k, $this->only)) {
                            unset($model[$k]);
                        }
                    }
                }
                if (is_array($model)) {
                    foreach ($model as $k => $v) {
                        if (!in_array($k, $this->only)) {
                            unset($model[$k]);
                        }
                    }
                }
            }

            return $model;
        };
    }

    /**
     * 格式化处理器 协程版本
     * @return \Closure
     */
    public function currentFormatter(): \Closure
    {
        return function (&$model) {
            $this->wg->add();
            $sandbox = app(Sandbox::class);

            Coroutine::create(function () use (&$model, $sandbox) {
                $sandbox->init();
                try {
                    $model = (new static)->setParam($this->param)->fields($this->fields)->hidden($this->hidden)->only($this->only)->formatter()->__invoke($model);
                } catch (\Throwable $exception) {
                    $this->log("currentFormatter错误, exception: {exception}", ["exception" => (string)$exception]);
                }

                $this->wg->done();
            });
        };
    }

    /**
     * 等待携程执行完成
     * @return void
     * @throws \Throwable
     */
    public function wait()
    {
        $this->wg->wait();
    }
}