<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seb
 * Date: 11/26/12
 * Time: 6:19 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Processus\Kindergarten\Sandbox;

use Processus\Kindergarten\Playground;
use InvalidArgumentException;
use Exception;

class ExpertSandbox extends SimpleSandbox
{


    /**
     * @var callable|null
     */
    protected $errorHandler;
    /**
     * @var callable|null
     */
    protected $exceptionHandler;
    /**
     * @var callable|null
     */
    protected $shutdownHandler;


    /**
     * @var callable|null
     */
    protected $onException;
    /**
     * @var callable|null
     */
    protected $onShutdownSuccess;
    /**
     * @var callable|null
     */
    protected $onShutdownFailed;

    /**
     * @return self
     * @throws \Exception
     */
    public function play()
    {
        $this->exception = null;
        $this->result = null;

        if (!$this->hasPlayground()) {

            throw new Exception('Playground must be set.');
        }
        $callable = $this->getToy();
        if (!is_callable($callable)) {

            throw new Exception('A callable toy must be set to play with.');
        }

        $params = $this->getParams();
        if (!is_array($params)) {
            throw new Exception('Params must be an array.');
        }

        $playground = $this->getPlayground();
        // backup settings, to be restored lateron
        $playgroundSettingsSnapshot = $playground->createSettingsSnapshot();
        // override playground settings for now ...
        $this->tweakPlayground();
        // execute sandboxed function call
        try {
            $this->result = call_user_func_array($callable, $params);
        } catch (\Exception $e) {
            $this->exception = $e;
        }
        // restore playground settings ...
        $playground->restoreSettingsBySnapshot(
            $playgroundSettingsSnapshot
        );

        // delegate exception?
        if (
            ($this->hasException())
            && ($this->getDelegateExceptionEnabled())
        ) {

            $exception = $this->exception;

            throw $exception;
        }

        return $this;
    }


    /**
     * @return self
     */
    private function tweakPlayground()
    {
        $playground = $this->getPlayground();
        // tweak settings ...

        $errorReportingCaptureLevel = $this->getErrorReportingCaptureLevel();
        if ($errorReportingCaptureLevel !== null) {
            $playground->setErrorReportingCaptureLevel(
                $errorReportingCaptureLevel
            );
        }
        $errorHandler = $this->getErrorHandler();
        if ($errorHandler !== null) {
            $playground->setErrorHandler(
                $errorHandler
            );
        }
        $exceptionHandler = $this->getExceptionHandler();
        if ($exceptionHandler !== null) {
            $playground->setExceptionHandler(
                $exceptionHandler
            );
        }
        $shutdownHandler = $this->getShutdownHandler();
        if ($shutdownHandler !== null) {
            $playground->setShutdownHandler(
                $shutdownHandler
            );

        }

        $onError = $this->getOnError();
        if ($onError !== null) {
            $playground->setOnError(
                $onError
            );

        }
        $onException = $this->getOnException();
        if ($onException !== null) {
            $playground->setOnException(
                $onException
            );
        }

        $onShutdownSuccess = $this->getOnShutdownSuccess();
        if ($onShutdownSuccess !== null) {
            $playground->setOnShutdownSuccess(
                $onShutdownSuccess
            );
        }
        $onShutdownFailed = $this->getOnShutdownFailed();
        if ($onShutdownFailed !== null) {
            $playground->setOnShutdownFailed(
                $onShutdownFailed
            );
        }

        return $this;
    }


    /**
     * @param callable|null $callable
     * @return self
     * @throws InvalidArgumentException
     */
    public function setErrorHandler($callable)
    {
        if ($callable !== null) {
            if (!is_callable($callable)) {

                throw new InvalidArgumentException(
                    'Parameter "callable"must be callable, closure or null'
                );
            }
        }
        $this->errorHandler = $callable;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }

    /**
     * @param callable|null $callable
     * @return self
     * @throws InvalidArgumentException
     */
    public function setExceptionHandler($callable)
    {
        if ($callable !== null) {
            if (!is_callable($callable)) {

                throw new InvalidArgumentException(
                    'Parameter "callable"must be callable, closure or null'
                );
            }
        }
        $this->exceptionHandler = $callable;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getExceptionHandler()
    {
        return $this->exceptionHandler;
    }

    /**
     * @param callable|null $callable
     * @return self
     * @throws InvalidArgumentException
     */
    public function setShutdownHandler($callable)
    {
        if ($callable !== null) {
            if (!is_callable($callable)) {

                throw new InvalidArgumentException(
                    'Parameter "callable"must be callable, closure or null'
                );
            }
        }
        $this->shutdownHandler = $callable;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getShutdownHandler()
    {
        return $this->shutdownHandler;
    }


    /**
     * @param callable|null $callable
     * @return self
     * @throws InvalidArgumentException
     */
    public function setOnException($callable)
    {
        if ($callable !== null) {
            if (!is_callable($callable)) {

                throw new InvalidArgumentException(
                    'Parameter "callable" must be callable, closure or null'
                );
            }
        }
        $this->onException = $callable;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getOnException()
    {
        return $this->onException;
    }


    /**
     * @param callable|null $callable
     * @return self
     * @throws InvalidArgumentException
     */
    public function setOnShutdownSuccess($callable)
    {
        if ($callable !== null) {
            if (!is_callable($callable)) {

                throw new InvalidArgumentException(
                    'Parameter "callable" must be callable, closure or null'
                );
            }
        }
        $this->onShutdownSuccess = $callable;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getOnShutdownSuccess()
    {
        return $this->onShutdownSuccess;
    }

    /**
     * @param callable|null $callable
     * @return self
     * @throws InvalidArgumentException
     */
    public function setOnShutdownFailed($callable)
    {
        if ($callable !== null) {
            if (!is_callable($callable)) {

                throw new InvalidArgumentException(
                    'Parameter "callable" must be callable, closure or null'
                );
            }
        }
        $this->onShutdownFailed = $callable;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getOnShutdownFailed()
    {
        return $this->onShutdownFailed;
    }
}
