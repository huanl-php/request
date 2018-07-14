<?php


namespace HuanL\Request;


use foo\bar;

class Response {

    /**
     * content-type  参照:http://tool.oschina.net/commons
     * @var array
     */
    public $http_content_type_list = [
        'html' => 'text/html',
        'json' => 'application/json',
        'xml' => 'text/xml',
        'file' => 'application/octet-stream',
        'jpg' => 'image/jpeg',
        'png' => 'image/png'
    ];

    /**
     * http status code 参照:http://tool.oschina.net/commons?type=5
     * @var array
     */
    public $http_status_code = [
        200 => 'HTTP/1.1 200 OK',
        301 => 'HTTP/1.1 301 Moved Permanently',
        302 => 'HTTP/1.1 302 Found',
        400 => 'HTTP/1.1 400 Bad Request',
        401 => 'HTTP/1.1 401 Unauthorized',
        402 => 'HTTP/1.1 402 Payment Required',
        403 => 'HTTP/1.1 403 Forbidden',
        404 => 'HTTP/1.1 404 Not Found'
    ];

    /**
     * 资源存放
     * @var mixed
     */
    private $response;

    /**
     * 资源类型
     * @var string
     */
    private $content_type = 'html';

    /**
     * 页面编码
     * @var string
     */
    private $charset = 'utf-8';


    public function __construct($code = 200, string $type = 'html', $response = null) {
        //根据参数的个数来判断参数的含义
        switch (func_num_args()) {
            case 1:
                //一个参数认为只输入了资源
                $response = $code;
                $code = 200;
                $type = 'html';
                break;
            case 2:
                $response = $type;
                $type = $code;
                $code = 200;
                break;
        }
        $this->statusCode($code)->contentType($type)->setResponse($response);
    }

    /**
     * 设置资源
     * @param $response
     * @return Response
     */
    public function setResponse($response): Response {
        $this->response = $response;
        return $this;
    }

    /**
     * 获取资源
     * @return string
     */
    public function getResponse(): string {
        //判断资源的类型,如果是对象类型,调用tostring方法
        //如果是数组就根据当前的内容类型编码成相对应的数据类型
        //如果是字符串,直接返回了,如果都不是,返回空的字符串
        if (is_object($this->response)) {
            //对象类型,根据当前类型判断
            switch ($this->content_type) {
                case 'xml':
                    return $this->object2xmlstr($this->response);
                case 'json':
                    return json_decode($this->response, JSON_UNESCAPED_UNICODE);
                default:
                    if (method_exists($this->response, '__toString')) {
                        return $this->response->__toString();
                    }
                    break;
            }
        } else if (is_array($this->response)) {
            if ($this->content_type == 'xml') {
                return $this->object2xmlstr($this->response);
            } else {
                return json_encode($this->response, JSON_UNESCAPED_UNICODE);
            }
        } else if (is_string($this->response)) {
            return $this->response;
        }
        return '';
    }


    /**
     * 对象转成xml字符串
     * @param $array
     * @param string $label
     * @param bool $frist
     * @return string
     */
    private function object2xmlstr($array, $label = 'node', $frist = true): string {
        if (is_object($array)) {
            $array = json_decode(json_encode($array), true);
        }
        if (!is_array($array) || sizeof($array) <= 0) return '';
        $xml = ($frist ? '<?xml version="1.0" encoding="' . $this->charset . '"?>' : '') . "<$label>";
        foreach ($array as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<$key>$val</$key>";
            } else if (is_array($val)) {
                $xml .= $this->object2xmlstr($val, $key, false);
            } else {
                $xml .= "<$key><![CDATA[$val]]></$key>";
            }
        }
        $xml .= "</$label>";
        return $xml;
    }

    /**
     * 设置编码
     * @param $charset
     * @return Response
     */
    public function setCharset($charset): Response {
        $this->charset = $charset;
    }

    /**
     * 设置返回的文件类型
     * @param string $type
     * @param string $charset
     * @return Response
     */
    public function contentType(string $type, string $charset = 'utf-8'): Response {
        $this->content_type = $type;
        if (func_get_args() == 1) {
            $charset = $this->charset;
        } else {
            $this->charset = $charset;
        }
        $this->header('Content-Type: ' . $this->type2content($type) .
            ($charset != false ? ('; charset=' . $charset) : ''));
        return $this;
    }

    /**
     * type转content-type
     * @param string $type
     * @return string
     */
    private function type2content(string $type): string {
        return $this->http_content_type_list[$type] ?? $type;
    }

    /**
     * 设置http状态码
     * @param $code
     * @return Response
     */
    public function statusCode($code): Response {
        $this->header($this->http_status_code[$code] ?? $code);
        return $this;
    }

    /**
     * 网站重定向
     * @param string $url
     * @param int $code
     * @return Response
     */
    public function redirection(string $url, int $code = 302): Response {
        $this->header('Location: ' . $url, true, $code);
        return $this;
    }

    /**
     * 设置返回的http协议头
     * @param string $string
     * @param bool $replace
     * @param int $code
     * @return Response
     */
    public function header(string $string, bool $replace = true, int $code = null): Response {
        header($string, $replace, $code);
        return $this;
    }

    /**
     * 内容长度
     * @param $length
     * @return Response
     */
    public function contentLength($length): Response {
        return $this->header('Content-Length: ' . $length);
    }

    /**
     * 禁用缓存
     * @return $this
     */
    public function banCache(): Response {
        $this->header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        $this->header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        return $this;
    }

    /**
     * 下载文件
     * @param $filename
     * @param $filesize
     * @return $this
     */
    public function download($filename, $filesize): Response {
        $this->contentType('file', false);
        $this->header('Accept-Ranges:bytes');
        $this->header('Accept-Length:' . $filesize);
        $this->header('Content-Disposition: attachment; filename=' . $filename);
        return $this;
    }


    /**
     * 设置和删除cookie,只有一个name参数时删除cookie
     * 到期时间为秒,不是unix时间戳,path默认值带/
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return bool
     */
    public function cookie(string $name, string $value = '',
                           int $expire = 0, string $path = '/',
                           string $domain = '', bool $secure = false,
                           bool $httponly = false
    ): bool {
        if (func_num_args() == 1) {
            //到期时间是10,是过去是时间
            return setcookie($name, '', 10);
        }
        if ($expire) {
            $expire += time();
        }
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

}
