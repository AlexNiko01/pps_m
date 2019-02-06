<?php

namespace pps\querybuilder\src;

/**
 * Interface IQuery
 * @package pps\querybuilder\src
 */
interface IQuery
{
    /**
     * @param bool $decode
     * @param bool $assoc
     * @return mixed
     */
    public function getResponse(bool $decode = false, bool $assoc = true);

    /**
     * @return array
     */
    public function getInfo();

    /**
     * @return null|string
     */
    public function getError();

    /**
     * @return null|int
     */
    public function getErrno();

    /**
     * @return int
     */
    public function getHttpCode();
}