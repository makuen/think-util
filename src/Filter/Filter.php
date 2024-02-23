<?php

namespace Makuen\ThinkUtil\Filter;

use think\db\Query;
use think\helper\Str;

/**
 * 模型筛选Trait
 *
 * @method Query paramFilter(array $params)
 * @method static Query paramFilter(array $params)
 */
trait Filter
{
    public function scopeParamFilter(Query $query, array $filters): Query
    {
        $className = class_basename(static::class);

        // filter 筛选类
        $filterClass = __NAMESPACE__ . "\\" . $className;

        if (!class_exists($filterClass)) {
            return $query;
        }

        foreach ($filters as $field => $value) {
            // filter 筛选方法 对应参数名
            $method = Str::camel($field);
            if (!method_exists($filterClass, $method)) {
                continue;
            }

            $query = app($filterClass)->$method($query, $value);
        }

        return $query;
    }
}