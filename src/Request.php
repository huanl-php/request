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
        return $_SERVER['PATH_INFO'] ?? '';
    }

    /**
     * 返回请求域名
     * @return string
     */
    public function domain() {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * 获取请求主页
     * @return string
     */
    public function home() {
        $home = $_SERVER['REQUEST_URI'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $home = substr($_SERVER['REQUEST_URI'], 0, strpos($home, '?'));
        }
        if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
            $home = str_replace($_SERVER['PATH_INFO'], '', $home);
        } else {
            $home = substr($home, 0, strrpos($home, '/'));
        }
        if (!isset($_SERVER['REQUEST_SCHEME'])) {
            $_SERVER['REQUEST_SCHEME'] = 'http';
        }
        return '//' . $_SERVER['HTTP_HOST'] . $home;
    }
}