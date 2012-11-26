<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seb
 * Date: 11/23/12
 * Time: 10:37 AM
 * To change this template use File | Settings | File Templates.
 */
namespace Processus\Kindergarten\Exception;

use Processus\Kindergarten\Exception\PlaygroundException;
use Exception;

class PlaygroundFatalErrorException extends PlaygroundException
{

    /**
     * @var array|null
     */
    protected $error;

    /**
     * @var \Exception|null
     */
    protected $reason;

    /**
     * @param array $error
     * @return self
     */
    public function applyError(array $error)
    {
        $this->error = $error;

        $message = '';
        if (array_key_exists('message', $error)) {
            $message = (string)$error['message'];
        }
        $this->message = $message;

        $code = -1;
        if (array_key_exists('type', $error)) {
            $code = (int)$error['type'];
        }
        $this->code = $code;

        $file = '';
        if (array_key_exists('file', $error)) {
            $file = (string)$error['file'];
        }
        $this->file = $file;

        $line = -1;
        if (array_key_exists('line', $error)) {
            $line = (int)$error['line'];
        }
        $this->line = $line;

        return $this;
    }

    /**
     * @return self
     */
    public function unsetError()
    {
        $this->error = null;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param \Exception $exception
     * @return self
     */
    public function setReason(\Exception $exception)
    {
        $this->reason = $exception;

        return $this;
    }

    /**
     *
     * @return \Exception|null
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return bool
     */
    public function hasReason()
    {
        return ($this->reason instanceof Exception);
    }


}