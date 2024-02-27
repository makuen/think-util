<?php
declare (strict_types=1);

namespace Makuen\ThinkUtil\Command;

use Makuen\ThinkUtil\CliProgressBar;
use Makuen\ThinkUtil\Table;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\helper\Str;

/**
 * 数据表名、字段名、注释名 等查询
 * 一个辅助命令 比我我才入职 对业务和数据库不熟 我需要找到那张表是用户表 或者找到哪里有用户手机号这个字段
 * 我可以使用命令 `php think table_search 用户` `php think table_search 用户手机号` 查找
 * 默认会匹配表名和字段  -T -C 选项可以限制查询表名或字段名
 */
class TableSearch extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('table_search')
            ->setDescription('根据输入查找数据表')
            ->addArgument("table_or_column", null, "表名或者字段名称")
            ->addOption("table", "T", null, "只查表名")
            ->addOption("column", "C", null, "只查字段");
    }

    protected function execute(Input $input, Output $output)
    {
        $tableOrColumn = $this->input->getArgument("table_or_column");
        $onlyTable = $this->input->getOption("table");
        $onlyColumn = $this->input->getOption("column");

        if ($onlyTable && $onlyColumn) {
            $this->output->error("--table --column 最多只能传一个");
            return;
        }

        // 所有表数据
        $tables = Db::query("show table status");

        // 进度条
        $progress = new CliProgressBar(count($tables));

        // 异步输出表格
        $tableHandlers = [];

        // 获取表字段
        foreach ($tables as $tableInfo) {
            $progress->progress();
            // 要对比表
            if (!$onlyColumn) {
                if (Str::contains($tableInfo["Name"], $tableOrColumn) || Str::contains($tableInfo["Comment"], $tableOrColumn)) {
                    $columns = Db::getFields($tableInfo["Name"]);
                    // 添加表格
                    $tableHandlers[] = $this->genTableHandle($tableOrColumn, $tableInfo["Name"], $tableInfo["Comment"], $columns);

                    continue;
                }
            }

            // 要对比字段
            if (!$onlyTable) {
                $columns = Db::getFields($tableInfo["Name"]);
                foreach ($columns as $columnName => $columnInfo) {
                    // 搜索字段名或注释
                    if (Str::contains($columnName, $tableOrColumn) || Str::contains($columnInfo["comment"], $tableOrColumn)) {
                        // 添加表格
                        $tableHandlers[] = $this->genTableHandle($tableOrColumn, $tableInfo["Name"], $tableInfo["Comment"], $columns);

                        break;
                    }
                }
            }
        }

        $progress->end();

        // 查找完毕 统一输出表格
        array_map("call_user_func", $tableHandlers);
    }

    /**
     * 输出表格的回调方法
     * @param string $tableOrColumn
     * @param string $tableName
     * @param string $tableComment
     * @param array $columns
     * @return \Closure
     */
    protected function genTableHandle(string $tableOrColumn, string $tableName, string $tableComment, array $columns): \Closure
    {
        return function () use ($tableOrColumn, $tableName, $tableComment, $columns) {
            $this->output->newLine();
            if (Str::contains($tableName, $tableOrColumn)) {
                $this->output->write("<info>$tableName</info>");
            } else {
                $this->output->write($tableName);
            }

            if (Str::contains($tableComment, $tableOrColumn)) {
                $this->output->info("($tableComment)");
            } else {
                $this->output->writeln("($tableComment)");
            }

            $table = new Table();

            $table->setHeader(["字段", "类型", "默认值", "说明"]);

            foreach ($columns as $columnInfo) {
                $columnRaw = [$columnInfo["name"], $columnInfo["type"], $columnInfo["default"], $columnInfo["comment"]];

                // 对应字段高亮
                if (Str::contains($columnInfo["name"], $tableOrColumn)) {
                    $columnRaw[0] = "<info>" . $columnInfo["name"] . "</info>";
                }

                // 对应字段高亮
                if (Str::contains($columnInfo["comment"], $tableOrColumn)) {
                    $columnRaw[3] = "<info>" . $columnInfo["comment"] . "</info>";
                }

                $table->addRow($columnRaw);
            }

            $this->table($table);
        };
    }
}
