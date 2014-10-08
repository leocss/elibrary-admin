<?php

namespace Elibrary\Lib\Exception;

/**
 * @author Laju Morrison <morrelinko@gmail.com>
 * @author Tosin Akomolafe <gettosin4me@gmail.com>
 */
class AppException extends \RuntimeException
{
    /**
     * @var string
     */
    protected $errorCode;

    /**
     * Constructor
     *
     * @param string $message
     * @param string $errorCode
     */
    public function __construct($message = null, $errorCode = 'unknown_error')
    {
        $this->errorCode = $errorCode;

        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
