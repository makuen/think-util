<?php

namespace Muken\ThinkUtil;

use Makuen\ThinkUtil\command\TableSearch;
use think\Service;

class TableSearchService extends Service
{
    public function boot(): void
    {
        $this->commands(["table_search" => TableSearch::class]);
    }
}