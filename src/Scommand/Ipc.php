<?php

namespace Makuen\ThinkUtil\Scommand;

use think\swoole\ipc\driver\UnixSocket;

/**
 * 覆盖框架代码
 */
class Ipc extends \think\swoole\Ipc
{
    protected function createDriver(string $name)
    {
        $params = $this->resolveParams($name);

        return new UnixSocket(app(CommandManager::class), $params);
    }
}