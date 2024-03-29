<?php

namespace Makuen\ThinkUtil\Filter;

use think\db\Query;
use think\helper\Str;

/**
 * 模型筛选Trait 方便对参数筛选进行归档 逻辑更清晰
 * 比如 用户user表 我需要对用户名user_name进行筛选
 * 首先我要User模型中引入此trait
 *
 * 然后创建一个User筛选类 和模型同名
 * 在User 筛选类中添加user_name方法 和参数同名
 * 在user_name 中进行逻辑过滤
 *
 * 在业务层使用 User::ParamFilter(["user_name" => "帅逼") 就可以筛选出帅逼用户
 *
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