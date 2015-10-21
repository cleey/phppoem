<?php
namespace Poem;
/**
 * ThinkPHP Behavior基础类
 */
abstract class Behavior {
    /**
     * 执行行为 run方法是Behavior唯一的接口
     */
    abstract public function run(&$params);

}