<?php


namespace HuanL\Request;


class Request {

    /**
     * 处理过的get参数
     * @var array
     */
    private $getParam = [];
    /**
     * 处理过的post参数
     * @var array
     */
    private $postParam = [];
    /**
     * 处理过的cookie参数
     * @var array
     */
    private $cookieParam = [];

    public function __construct() {
        $this->getParam = $_GET;
        $this->cookieParam = $_COOKIE;
        $this->dealPostParam();
    }

    /**
     * 根据请求的content-type处理数据
     */
    private function dealPostParam() {

    }

    public function header($key) {

    }

    public function input($key) {

    }
}