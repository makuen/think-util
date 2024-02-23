<?php

namespace Makuen\ThinkUtil\Scommand;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * command 基类, 子类可在协程中使用连接池
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
        $manager->addWorker(function ($pool, $workId) use ($input, $output) {
            $this->runInSwoole();
        }, $this->getName());
        $envName = $this->input->getOption('env');
        $manager->start($envName);
    }

    protected abstract function runInSwoole();
}