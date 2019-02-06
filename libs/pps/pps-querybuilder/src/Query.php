<?php

namespace pps\querybuilder\src;

use pps\querybuilder\QueryBuilder;

/**
 * Class Query
 * @package pps\querybuilder\src
 */
class Query implements IQuery
{
    /**
     * @var array|bool|string
     */
    protected $_response;
    /**
     * @var array
     */
    protected $_info;
    /**
     * @var string|null
     */
    protected $_error;
    /**
     * @var string|null
     */
    protected $_errno;


    /**
     * Query constructor.
     * @param QueryBuilder $builder
     */
    public function __construct(QueryBuilder $builder)
    {
        $this->_response = $builder->response;
        $this->_info = $builder->info;
        $this->_error = $builder->error;
    }

    /**
     * @param bool $decode
     * @param bool $assoc
     * @return mixed
     */
    public function getResponse(bool $decode = false, bool $assoc = true)
    {
        if (!empty($this->_response) && $decode) {
            $response =  json_decode($this->_response, $assoc);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $response;
            } else {
                try {
                    $xml = new \SimpleXMLElement($response);

                    if ($assoc) {
                        return json_decode(json_encode($xml), $assoc);
                    } else {
                        return $xml;
                    }

                } catch (\Exception $e) {
                    return false;
                }
            }
        }

        return $this->_response;
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return $this->_info;
    }

    /**
     * @return null|string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @return null|int
     */
    public function getErrno()
    {
        return $this->_errno;
    }

    /**
     * @return int
     */
    public function getHttpCode()
    {
        return $this->_info['http_code'] ?? 0;
    }
}