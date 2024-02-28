<?php

namespace Makuen\ThinkUtil\Scommand;

use Swoole\Process\Pool;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * command 基类, 子类可在协程中使用连接池
 * think-swoole 在命令行模式没有启动swoole服务 所以没有初始化连接池
 * 导致在command模式下无法并发操作数据库
 * 变更command继承此基类 会在调用命令时启动连接池 可以并发操作数据库
 * 但是也会对这个命令的进程进行守护 错误后会重启 完成后会自动退出
 */
abstract class SwooleCommand extends Command
{
    protected function configure()
    {
        $this->setName('swoole command')
            ->addOption(
                'env',
                'E',
                Option::VALUE_REQUIRED,
                'Environment name',
                ''
            )
            ->setDescription('Swoole Command for ThinkPHP');
    }

    protected function execute(Input $input, Output $output)
    {
        $manager = app(CommandManager::class);
        $manager->addWorker(function (Pool $pool, $workId) use ($input, $output) {
            $this->runInSwoole();

            // 逻辑执行完毕后 停止程序
            $pool->shutdown();
        }, $this->getName());
        $envName = $this->input->getOption('env');
        $manager->start($envName);
    }

    protected abstract function runInSwoole();
}