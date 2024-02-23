<?php
declare (strict_types = 1);

namespace Makuen\ThinkUtil\Scommand;

use Swoole\Constant;
use Swoole\Process;
use Swoole\Process\Pool;
use Swoole\Runtime;
use think\swoole\Manager;

/**
 * 覆盖框架代码 在console中可使用连接池
 */
class CommandManager extends Manager
{
    /**
     * Initialize.
     */
    protected function initialize(): void
    {
        $this->preparePools();
        $this->prepareTracing();
    }

    public function start(string $envName): void
    {
        $this->setProcessName('manager process');

        $this->initialize();
        $this->triggerEvent('init');

        $workerNum = count($this->startFuncMap);

        //启动消息监听
        $this->prepareIpc($workerNum);

        $pool = new Pool($workerNum, $this->ipc->getType(), 0, true);

        $pool->on(Constant::EVENT_WORKER_START, function ($pool, $workerId) use ($envName) {
            Runtime::enableCoroutine();
            $this->pool     = $pool;
            $this->workerId = $workerId;

            [$func, $name] = $this->startFuncMap[$workerId];

            if ($name) {
                $this->setProcessName($name);
            }

            Process::signal(SIGTERM, function () {
                $this->pool->getProcess()->exit();
            });

            $this->clearCache();
            $this->prepareApplication($envName);

            $this->ipc->listenMessage($workerId);

            $this->triggerEvent(Constant::EVENT_WORKER_START);

            $func($pool, $workerId);
        });

        $pool->start();
    }

    protected function prepareIpc($workerNum)
    {
        $this->ipc = $this->container->make(Ipc::class);
        $this->ipc->prepare($workerNum);
    }
}