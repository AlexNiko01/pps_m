<?php

namespace pps\querybuilder;

use pps\querybuilder\src\{
    IQuery, Query
};
use yii\base\InvalidParamException;

/**
 * Class QueryBuilder
 * @package pps\querybuilder
 */
class QueryBuilder
{
    /**
     * @var array|string|bool
     */
    public $response;
    /**
     * Response information
     * @var array
     */
    public $info;
    /**
     * Response error message
     * @var string|null
     */
    public $error;
    /**
     * Code of error message
     * @var string|null
     */
    public $errno;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var bool
     */
    protected $_encodeJSON = false;
    /**
     * Params which be sent
     * @var array
     */
    protected $params = [];
    /**
     * Curl options
     * @var array
     */
    protected $_options;
    /**
     * @var array
     */
    protected $_headers = [];


    /**
     * QueryBuilder constructor.
     * @param string $url
     */
    public function __construct(string $url = null)
    {
        $this->url = $url;

        $this->_options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => false,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];
    }

    /**
     * @param array $params
     * @return QueryBuilder
     */
    public function setParams(array $params): QueryBuilder
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Set query method as POST
     * @return QueryBuilder
     */
    public function asPost(): QueryBuilder
    {
        $this->_options[CURLOPT_POST] = true;

        return $this;
    }

    /**
     * Set url for query
     * @param string $url
     * @return QueryBuilder
     */
    public function setUrl(string $url): QueryBuilder
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Default option will be replaced or added
     * @param $name
     * @param $value
     * @return QueryBuilder
     */
    public function setOption($name, $value): QueryBuilder
    {
        $this->_options[$name] = $value;

        return $this;
    }

    /**
     * Default options will be replaced
     * @param array $options
     * @return QueryBuilder
     */
    public function setOptions(array $options): QueryBuilder
    {
        $this->_options = array_replace($this->_options, $options);

        return $this;
    }

    /**
     * Set a header for request
     * @param $name
     * @param $value
     * @return QueryBuilder
     */
    public function setHeader($name, $value): QueryBuilder
    {
        $this->_headers[] = "{$name}: {$value}";

        return $this;
    }

    /**
     * Set headers for request
     * ['Content-Type' => 'application/json']
     * @param array $headers
     * @return QueryBuilder
     */
    public function setHeaders(array $headers): QueryBuilder
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    /**
     * Get all headers
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Set header 'Content-Type: application/json'
     * @param bool $encode
     * @param bool $setHeader
     * @return QueryBuilder
     */
    public function json($encode = false, $setHeader = true): QueryBuilder
    {
        if ($setHeader) {
            $this->setHeader('Content-Type', 'application/json');
        }

        if ($encode) {
            $this->_encodeJSON = true;
        }

        return $this;
    }

    /**
     * Set HTTP method for request
     * @param string $method
     * @return QueryBuilder
     */
    public function setMethod(string $method): QueryBuilder
    {
        if (!in_array(strtoupper($method), ['POST', 'GET', 'PUT', 'DELETE', 'HEAD', 'CONNECT', 'OPTIONS', 'PATCH'])) {
            trigger_error("Method {$method} wasn't set", E_USER_NOTICE);
            return $this;
        }

        $this->_options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);

        return $this;
    }

    /**
     * Send request
     * @return IQuery
     */
    public function send(): IQuery
    {
        if (empty($this->url)) {
            throw new InvalidParamException('"url" not found. Use setUrl(string $url) method or in __construct(string $url)');
        }

        $query = http_build_query($this->params);

        if ($this->_isPost()) {
            if ($this->_isJSON()) {
                $this->_options[CURLOPT_POSTFIELDS] = json_encode($this->params);
            } else {
                $this->_options[CURLOPT_POSTFIELDS] = $query;
            }
        } else {
            $this->url .= empty($query) ? '' : "?{$query}";
        }

        $ch = curl_init($this->url);

        if (!empty($this->_headers)) {
            $this->_options[CURLOPT_HTTPHEADER] = $this->_headers;
        }

        curl_setopt_array($ch, $this->_options);

        $this->response = curl_exec($ch);
        $this->info = curl_getinfo($ch);
        $this->error = curl_error($ch);
        $this->errno = curl_errno($ch);

        curl_close($ch);

        return new Query($this);
    }

    /**
     * Check query is a POST
     * @return bool
     */
    protected function _isPost(): bool
    {
        return isset($this->_options[CURLOPT_POST]) && $this->_options[CURLOPT_POST] === true;
    }

    /**
     * Check query should be as JSON
     * @return bool
     */
    protected function _isJSON(): bool
    {
        return in_array('Content-Type: application/json', $this->_headers) || $this->_encodeJSON;
    }
}