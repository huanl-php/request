<?php


namespace HuanL\Request;


class Request {

    /**
     * 处理过的get参数
     * @var array
     */
    private $get = [];
    /**
     * 处理过的post参数
     * @var array
     */
    private $post = [];

    /**
     * 处理过的file参数
     * @var array
     */
    private $files = [];
    /**
     * 处理过的cookie参数
     * @var array
     */
    private $cookie = [];

    /**
     * 路径信息
     * @var string
     */
    private $pathInfo = '';
    /**
     * 协议头
     * @var array|false
     */
    private $header = [];

    /**
     * 因为php7以后只能读取一次,所以存起来
     * @var bool|string
     */
    private $postSource = '';

    /**
     * 请求类型
     * @var string
     */
    private $contentType = '';

    /**
     * 主页URL
     * @var string
     */
    private $home = '';

    /**
     * 请求编码
     * @var string
     */
    private $contentTypeParam = ['charset' => 'UTF-8', 'boundary' => ''];

    /**
     * Request constructor.
     */
    public function __construct() {
        $this->get = $_GET;
        $this->cookie = $_COOKIE;
        $this->postSource = file_get_contents('php://input');
        $this->header = $this->dealRequestHeader();
        $this->files = $_FILES;
        $this->post = $this->dealPostParam();
    }

    /**
     * 处理请求headers
     * @return mixed
     */
    private function dealRequestHeader() {
        //处理server数组中的HTTP_
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $header[substr($key, 5)] = $value;
            }
        }

        //但是也有例外
        $header['CONTENT_LENGTH'] = $_SERVER['CONTENT_LENGTH'] ?? false;
        $header['CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'] ?? false;
        $header['AUTH_USER'] = $_SERVER['PHP_AUTH_USER'] ?? false;
        $header['AUTH_PW'] = $_SERVER['PHP_AUTH_PW'] ?? false;
        $header['PHP_AUTH_DIGEST'] = $_SERVER['PHP_AUTH_DIGEST'] ?? false;

        return $header;
    }

    /**
     * 获取文件
     * @param $key
     * @return bool|mixed
     */
    public function files($key) {
        return $this->files[$key] ?? false;
    }

    /**
     * 根据请求的content-type处理post字段的数据
     * @return array
     */
    private function dealPostParam() {
        //如果post不是空的就不处理了
        if (!empty($_POST)) {
            return $_POST;
        }

        //如果是空根据content-type处理
        $contentType = $this->contentType($charset, $boundary);

        //处理post源数据
        switch ($contentType) {
            case 'form':
                parse_str($this->postSource, $post);
                break;
            case 'form-data':
                $post = $this->dealFormData($this->postSource, $boundary);
                break;
            case 'json':
                $post = json_decode($this->postSource, true);
                break;
            case 'xml':
                $post = simplexml_load_string($this->postSource);
                break;
            default:
                $post = $this->postSource;
                break;
        }

        return $post;
    }

    /**
     * 获取POST参数
     * @param string $key
     * @return array
     */
    public function post(string $key = ''): array {
        if (empty($key)) {
            //为空返回post数组
            return empty($this->post) ? [] : $this->post;
        }
        return $this->post[$key];
    }

    /**
     * 处理form-data数据
     * @param $data
     * @param $boundary
     * @return mixed
     */
    private function dealFormData($data, $boundary) {
//        preg_match_all("/$boundary\nContent-Disposition:(.+?)\n(Content-Type:.+?\n|)([\s\S]+?)\n(|$boundary--)/",
//            $data,$matches);
//        print_r($matches);
        return [];
    }

    private function dealContentType($contentType, &$charset = 'UTF-8', &$boundary = '') {
        if ($strPos = strpos($contentType, ';')) {
            $contentType = substr($contentType, 0, $strPos);
        }

        //获取编码
        if ($strPos = strpos($contentType, 'charset=')) {
            $strPos += 8;
            $this->contentTypeParam['charset'] = strtoupper(
                substr($contentType, $strPos,
                    strpos($contentType, ';', $strPos) ?? null
                )
            );
            $charset = $this->contentTypeParam['charset'];
        }

        //处理boundary
        if ($strPos = strpos($contentType, 'boundary=')) {
            $strPos += 9;
            $this->contentTypeParam['boundary'] = strtoupper(
                substr($contentType, $strPos,
                    strpos($contentType, ';', $strPos) ?? null
                )
            );
            $charset = $this->contentTypeParam['boundary'];
        }

        return $contentType;
    }

    /**
     * 处理类型,返回类型,编码,分解符
     * @param string $charset
     * @param string $boundary
     * @return string
     */
    public function contentType(&$charset = 'UTF-8', &$boundary = '') {
        if (!empty($this->contentType)) {
            $charset = $this->contentTypeParam['charset'];
            $boundary = $this->contentTypeParam['boundary'];
            return $this->contentType;
        }
        $contentType = $this->dealContentType($this->header('CONTENT_TYPE'), $charset, $boundary);
        $this->contentType = $this->selectDataType($contentType);
        return $this->contentType;
    }

    /**
     * 选择contenttype对应的数据类型
     * @param $contentType
     * @return string
     */
    private function selectDataType($contentType) {
        switch ($contentType) {
            case 'application/json':
                $contentType = 'json';
                break;
            case 'application/xml':
            case 'text/xml':
                $contentType = 'xml';
                break;
            case 'application/x-www-form-urlencoded':
                $contentType = 'form';
                break;
            case 'multipart/form-data':
                $contentType = 'form-data';
                break;
            case 'application/javascript':
                $contentType = 'javascript';
                break;
            case 'text/plain':
                $contentType = 'plain';
                break;
            case 'text/html':
                $contentType = 'html';
                break;
            default:
                $contentType = 'text';
                break;
        }
        return $contentType;
    }

    /**
     * 通过协议头键获取值
     * @param $key
     * @return bool|mixed
     */
    public function header($key) {
        return $this->header[strtoupper(str_replace('-', '_', $key))] ?? false;
    }

    /**
     * 从get/post/cookie中获取数据,也可以指定
     * @param $key
     * @param null $type
     * @return bool|mixed
     */
    public function input($key, $type = null) {
        if (!is_null($type)) {
            return !in_array($type, ['post', 'get', 'cookie']) ? false :
                $this->$type[$key] ?? false;
        }
        return $this->post[$key] ??
            $this->get[$key] ??
            $this->cookie[$key] ?? false;
    }

    /**
     * 请求方法
     * @return string
     */
    public function method() {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * 请求路径信息
     * @return string
     */
    public function path_info() {
        if (!empty($this->pathInfo)) {
            return $this->pathInfo;
        }
        if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
            return $this->pathInfo = $_SERVER['PATH_INFO'];
        }
        //如果没有pathinfo,自己处理通过请求的url处理
        $pathInfo = $_SERVER['REQUEST_URI'] ?? '/';
        //删除的url中的脚本路径和脚本名字
        $scriptPath = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
        $scriptName = substr($_SERVER['SCRIPT_NAME'], strrpos($_SERVER['SCRIPT_NAME'], '/') + 1);
        $pathInfo = substr($pathInfo, strlen($scriptPath));
        if (($pos = strpos($pathInfo, $scriptName)) === 1) {
            $pathInfo = '/' . substr($pathInfo, $pos + strlen($scriptName));
        }
        //删除get参数?
        if ($pos = strrpos($pathInfo, '?')) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }
        return $this->pathInfo = $pathInfo;
    }

    /**
     * 返回请求域名
     * @return string
     */
    public function domain() {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    /**
     * 获取请求主页
     * @return string
     */
    public function home($http = false) {
        if ($this->home != '') {
            return $this->home;
        }
        $this->home = $_SERVER['REQUEST_URI'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $this->home = substr($_SERVER['REQUEST_URI'], 0, strpos($this->home, '?'));
        }
        if (!empty($this->path_info())) {
            $this->home = str_replace($this->path_info(), '', $this->home);
        } else {
            $this->home = substr($this->home, 0, strrpos($this->home, '/'));
        }
        if (!isset($_SERVER['REQUEST_SCHEME'])) {
            $_SERVER['REQUEST_SCHEME'] = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

        }
        return ($http ? $_SERVER['REQUEST_SCHEME'] . ':' : '') . '//' . $_SERVER['HTTP_HOST'] . $this->home;
    }

    /**
     * 获取客户ip
     * @return string
     */
    public function getip() {
        $arr_ip_header = array(
            'HTTP_CDN_SRC_IP',
            'HTTP_PROXY_CLIENT_IP',
            'HTTP_WL_PROXY_CLIENT_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        );
        $client_ip = 'unknown';
        foreach ($arr_ip_header as $key) {
            if (!empty($_SERVER[$key]) && strtolower($_SERVER[$key]) != 'unknown') {
                $client_ip = $_SERVER[$key];
                break;
            }
        }
        return $client_ip;
    }
}