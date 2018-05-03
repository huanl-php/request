<?php


namespace HuanL\Request;


class Response {

    /**
     * content-type  参照:http://tool.oschina.net/commons
     * @var array
     */
    public $http_content_type_list = [
        'html' => 'text/html',
        'json' => 'application/json',
        'xml' => 'text/xml',
        'file' => 'application/octet-stream'
    ];

    /**
     * http status code 参照:http://tool.oschina.net/commons?type=5
     * @var array
     */
    public $http_status_code = [
        200 => 'HTTP/1.1 200 OK',
        404 => 'HTTP/1.1 404 Not Found'
    ];

    /**
     * 设置返回的文件类型
     * @param string $type
     * @param string $charset
     * @return Response
     */
    public function contentType(string $type, string $charset = 'UTF-8'): Response {
        header('Content-Type: ' . $this->type2content($type) . '; charset=' . $charset);
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
        header($this->http_status_code[$code] ?? $code);
        return $this;
    }

    /**
     * 网站重定向
     * @param string $url
     * @param int $code
     * @return Response
     */
    public function redirection(string $url, int $code = 302): Response {
        header('Location: ' . $url, true, $code);
        return $this;
    }

    /**
     * 设置返回的http协议头
     * @param string $string
     * @param bool $replace
     * @param int $code
     * @return Response
     */
    public function header(string $string, bool $replace, int $code): Response {
        header($string, $replace, $code);
        return $this;
    }
}
