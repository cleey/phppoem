<?php
namespace poem;
class controller {
    protected $view; // view 类
    function __construct() {$this->view = load::instance('poem\view');}
    // 展示页面
    function display($tpl = '') {$this->view->display($tpl);}
    // 执行页面并返回执行结果
    function fetch($tpl = '') {return $this->view->fetch($tpl);}
    // 用户变量
    function assign($key, $value = '') {$this->view->assign($key, $value);}
    // 返回成功跳转
    function success($info, $url = '', $params = '', $second = false) {$this->view->autoJump($info, $url, $params, $second, 1);}
    // 返回失败跳转
    function error($info, $url = '', $params = '', $second = false) {$this->view->autoJump($info, $url, $params, $second, 0);}
}