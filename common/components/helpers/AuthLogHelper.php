<?php

namespace common\components\helpers;


class AuthLogHelper
{
    /**
     * @param string $userAgent
     * @return string
     * Creates hash for user agent field to store it in DB
     */
    public static function genHash(string $userAgent): string
    {
        return hash('sha1', $userAgent);

    }

    public static function dismantleHash(string $hash)
    {
        return sha1($hash);
    }
}