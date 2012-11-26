<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seb
 * Date: 11/23/12
 * Time: 10:36 AM
 * To change this template use File | Settings | File Templates.
 */
namespace Processus\Kindergarten;

use Processus\Kindergarten\Exception\PlaygroundException;
use Processus\Kindergarten\Proxy\ShutdownHandler;
use InvalidArgumentException;
use Processus\Kindergarten\Exception\PlaygroundFatalErrorException;
use Processus\Kindergarten\Sandbox\SimpleSandbox;
use Processus\Kindergarten\Sandbox\ExpertSandbox;
use Exception;


class Playground
{

    /**
     * @var callable|null
     */
    protected $exceptionHandler;
    /**
     * @var callable|null
     */
    protected $errorHandler;
    /**
     * @var callable|null
     */
    protected $shutdownHandler;
    /**
     * @var ShutdownHandler|null
     */
    protected $shutdownHandlerProxy;

    /**
     * @var callable|null
     */
    protected $onException;
    /**
     * @var callable|null
     */
    protected $onError;
    /**
     * @var callable|null
     */
    protected $onShutdownSuccess;
    /**
     * @var callable|null
     */
    protected $onShutdownFailed;

    /**
     * @var int|null
     */
    protected $errorReportingCaptureLevel;
    /**
     * @var string
     */
    protected $errorMessageShutdownFailed
        = 'ERROR_PLAYGROUND_SHUTDOWN_FAILED';

    /**
     * @var bool
     */
    protected $isShutdown = false;

    /**
     * @var array
     */
    protected $shutdownCallbackList;


    // ======== singleton pattern, if used as the bootstrap =====
    /**
     * @var self
     */
    private static $instance;

    /**
     * @return self
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // ======== init / run =====
    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * @return self
     */
    public function play()
    {
        $this->resetToDefaults();

        return $this;
    }

    /**
     * @return self
     */
    public function resetToDefaults()
    {
        $this
            ->resetReportingCaptureLevel()
            ->resetErrorHandler()
            ->resetExceptionHandler()
            ->resetShutdownHandler();

        return $this;
    }

    // ======== Exception Handling ====================
    /**
     * @return self
     */
    public function resetExceptionHandler()
    {
        $this->setExceptionHandler(null);

        return $this;
    }

    /**
     * @param callable|null $callable
     * @return self
     * @throws PlaygroundException
     */
    public function setExceptionHandler($callable)
    {
        $handler = $callable;
        if ($callable === null) {
            $handler = $this->createExceptionHandlerDefault();
        }

        $isCallable = is_callable($handler);
        if (!$isCallable) {

            throw new PlaygroundException(
                'Parameter "callable" must be callable, closure or null'
            );
        }

        $this->exceptionHandler = $handler;
        set_exception_handler($handler);

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
     * @throws PlaygroundException
     */
    public function setOnException($callable)
    {
        $result = $this;

        $handler = $callable;
        if ($callable === null) {
            $this->onException = null;

            return $result;
        }

        $isCallable = is_callable($handler);
        if (!$isCallable) {

            throw new PlaygroundException(
                'Parameter "callable" must be callable, closure or null'
            );
        }

        $this->onException = $handler;

        return $result;
    }

    /**
     * @return callable|null
     */
    public function getOnException()
    {
        return $this->onException;
    }

    /**
     * @return callable
     */
    private function createExceptionHandlerDefault()
    {
        $handler = array(
            $this,
            'exceptionHandlerDefault'
        );

        return $handler;
    }

    /**
     * @param \Exception $exception
     * @return bool
     */
    public function exceptionHandlerDefault(\Exception $exception)
    {
        $customHandler = $this->onException;
        if (is_callable($customHandler)) {

            $isHandled = call_user_func_array(
                $customHandler,
                array(
                    $this,
                    $exception
                )
            );

            $isHandled = ($isHandled === true);
            if ($isHandled) {

                return true;
            }
        }

        return true;
    }

    // ======== Error Handling ====================
    /**
     * @param int|null $captureLevel
     * @return self
     */
    public function setErrorReportingCaptureLevel($captureLevel)
    {

        if ($captureLevel === null) {
            $captureLevel = $this->createErrorReportingCaptureLevelDefault();
        }

        $this->errorReportingCaptureLevel = $captureLevel;
        error_reporting($captureLevel);

        // register error handler for that new capture level
        $this->setErrorHandler($this->getErrorHandler());

        return $this;
    }

    /**
     * @return int|null
     */
    public function getErrorReportingCaptureLevel()
    {
        return $this->errorReportingCaptureLevel;
    }

    /**
     * @return self
     */
    public function resetReportingCaptureLevel()
    {
        $this->setErrorReportingCaptureLevel(null);

        return $this;
    }

    /**
     * @param callable|null $callable
     * @return self
     * @throws PlaygroundException
     */
    public function setOnError($callable)
    {
        $result = $this;
        if ($callable === null) {
            $this->onError = null;

            return $result;
        }

        $handler = $callable;
        $isCallable = is_callable($handler);
        if (!$isCallable) {

            throw new PlaygroundException(
                'Parameter "callable" must be callable, closure or null'
            );
        }

        $this->onError = $callable;

        return $result;
    }

    /**
     * @return callable|null
     */
    public function getOnError()
    {
        return $this->onError;
    }

    /**
     * @return int
     */
    private function createErrorReportingCaptureLevelDefault()
    {
        return ((E_ALL | E_STRICT));
    }

    /**
     * @return callable
     * @throws \ErrorException
     */
    private function createErrorHandlerDefault()
    {
        $handler = array(
            $this,
            'errorhandlerDefault'
        );

        return $handler;
    }

    /**
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool
     * @throws \ErrorException
     */
    public function errorHandlerDefault($errno, $errstr, $errfile, $errline)
    {
        $customHandler = $this->onError;
        if (is_callable($customHandler)) {
            $error = array(
                'message' => $errstr,
                'type' => $errno,
                'code' => $errno,
                'file' => $errfile,
                'line' => $errline,
            );

            $isHandled = call_user_func_array(
                $customHandler,
                array(
                    $this,
                    $error
                )
            );

            $isHandled = ($isHandled === true);
            if ($isHandled) {

                return true;
            }
        }

        switch ($errno) {
            case null:
            case 0:
            case E_WARNING:
            {

                return true;
            }
            default:
                break;
        }

        throw new \ErrorException(
            $errstr, $errno, 0, $errfile, $errline
        );

    }

    /**
     * @return self
     */
    public function resetErrorHandler()
    {
        $this->setErrorHandler(null);

        return $this;
    }

    /**
     * @param callable|null $callable
     * @return self
     * @throws PlaygroundException
     */
    public function setErrorHandler($callable)
    {
        $handler = $callable;
        if ($callable === null) {
            $handler = $this->createErrorHandlerDefault();
        }

        $isCallable = is_callable($handler);
        if (!$isCallable) {

            throw new PlaygroundException(
                'Parameter "callable" must be callable, closure or null'
            );
        }


        $this->errorHandler = $handler;

        $errorCaptureLevel = error_reporting();

        set_error_handler($handler, $errorCaptureLevel);

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }

    // ========= shutdown handling ========
    /**
     * @return string
     */
    public function getErrorMessageShutDownFailed()
    {
        return (string)$this->errorMessageShutdownFailed;
    }

    /**
     * @return self
     */
    public function resetShutdownHandler()
    {
        $this->setShutdownHandler(null);

        return $this;
    }

    /**
     * @param callable|null $callable
     * @return self
     * @throws PlaygroundException
     */
    public function setShutdownHandler($callable)
    {
        $result = $this;


        if ($callable === null) {
            $callable = $this->createShutdownHandlerDefault();
        }
        if (!is_callable($callable)) {
            throw new InvalidArgumentException(
                'Parameter "callable" must be callable, closure or null'
            );
        }

        $shutdownCallbackList = $this->getShutdownCallbackList();
        foreach ($shutdownCallbackList as $key => $callback) {
            if (!($callback instanceof ShutdownHandler)) {
                continue;
            }
            /**
             * @var ShutdownHandler $callback
             */
            $callback->setEnabled(false);
            $callback->setCallable(null);
        }
        $shutdownCallback = new ShutdownHandler();
        $shutdownCallback->setEnabled(true);
        $shutdownCallback->setCallable($callable);
        $shutdownCallback->register();

        $shutdownCallbackList[] = $shutdownCallback;
        $this->shutdownCallbackList = $shutdownCallbackList;
        $this->shutdownHandlerProxy = $shutdownCallback;
        $this->shutdownHandler = $callable;

        return $result;
    }

    /**
     * @return callable
     */
    private function createShutdownHandlerDefault()
    {
        $handler = array(
            $this,
            'shutdownHandlerDefault'
        );

        return $handler;
    }

    /**
     * @return bool
     */
    public function shutdownHandlerDefault()
    {

        $result = true;
        $this->isShutdown = true;

        $lastError = error_get_last();
        $hasError = is_array($lastError);

        $shutdownException = null;
        try {
            if (!$hasError) {
                $customHandler = $this->onShutdownSuccess;
                if (is_callable($customHandler)) {
                    $isHandled = call_user_func_array(
                        $customHandler,
                        array(
                            $this
                        )
                    );

                    $isHandled = ($isHandled === true);
                    if ($isHandled) {

                        return $result;
                    }
                }
            }
        } catch (\Exception $e) {
            $shutdownException = $e;
        }

        $hasError = (
            ($hasError)
                ||
                ($shutdownException instanceof \Exception)
        );
        if ($hasError) {
            try {
                if (!($shutdownException instanceof Exception)) {
                    $shutdownException = new PlaygroundFatalErrorException(
                        'PLAYGROUND: Shutdown detected an unhandled error'
                    );
                    if (is_array($lastError)) {
                        $shutdownException->applyError($lastError);
                    }
                } else {
                    $previousException = $shutdownException;

                    $shutdownException = new PlaygroundFatalErrorException(
                        'PLAYGROUND: Shutdown detected an unhandled error'
                    );
                    $shutdownException->setReason($previousException);
                }

                $customHandler = $this->onShutdownFailed;
                if (is_callable($customHandler)) {

                    $isHandled = call_user_func_array(
                        $customHandler,
                        array(
                            $this,
                            $shutdownException,
                        )
                    );

                    $isHandled = ($isHandled === true);
                    if ($isHandled) {

                        return $result;
                    }
                }
            } catch (\Exception $e) {

                $errorMessage = $this->getErrorMessageShutdownFailed();
                if ((is_string($errorMessage)) && ($errorMessage !== '')) {
                    echo $errorMessage;

                    return false;
                }
            }
        }

        return $result;

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
     * @throws PlaygroundException
     */
    public function setOnShutdownSuccess($callable)
    {
        $result = $this;
        if ($callable === null) {
            $this->onShutdownSuccess = null;

            return $result;
        }

        $handler = $callable;
        $isCallable = is_callable($handler);
        if (!$isCallable) {

            throw new PlaygroundException(
                'Parameter "callable" must be callable, closure or null'
            );
        }

        $this->onShutdownSuccess = $handler;

        return $result;
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
     * @throws PlaygroundException
     */
    public function setOnShutdownFailed($callable)
    {
        $result = $this;
        if ($callable === null) {
            $this->onShutdownFailed = null;

            return $result;
        }

        $handler = $callable;
        $isCallable = is_callable($handler);
        if (!$isCallable) {

            throw new PlaygroundException(
                'Parameter "callable" must be callable, closure or null'
            );
        }

        $this->onShutdownFailed = $handler;

        return $result;
    }

    /**
     * @return callable|null
     */
    public function getOnShutdownFailed()
    {
        return $this->onShutdownFailed;
    }

    /**
     * @param bool $value
     * @return self
     */
    public function setIsShutdown($value)
    {
        $this->isShutdown = ($value === true);

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsShutdown()
    {
        return ($this->isShutdown === true);
    }

    /**
     * @return array
     */
    private function getShutdownCallbackList()
    {
        if (!is_array($this->shutdownCallbackList)) {
            $this->shutdownCallbackList = array();
        }

        return $this->shutdownCallbackList;
    }

    // ======== sandboxing untrusted legacy code =====
    /**
     * @return SimpleSandbox
     */
    public function createSimpleSandbox()
    {
        $sandbox = new SimpleSandbox();
        $sandbox->setPlayground($this);

        return $sandbox;
    }

    /**
     * @return ExpertSandbox
     */
    public function createExpertSandbox()
    {
        $sandbox = new ExpertSandbox();
        $sandbox->setPlayground($this);

        return $sandbox;
    }

    /**
     * @return array
     */
    public function createSettingsSnapshot()
    {
        $snapshot = array(
            'errorReportingCaptureLevel' =>
            $this->getErrorReportingCaptureLevel(),
            'errorHandler' => $this->getErrorHandler(),
            'exceptionHandler' => $this->getExceptionHandler(),
            'shutdownHandler' => $this->getShutdownHandler(),
            'onError' => $this->getOnError(),
            'onException' => $this->getOnException(),
            'onShutdownSuccess' => $this->getOnShutdownSuccess(),
            'onShutdownFailed' => $this->getOnShutdownFailed(),
        );

        return $snapshot;
    }

    /**
     * @param array $snapshot
     * @return self
     */
    public function restoreSettingsBySnapshot(array $snapshot)
    {
        $result = $this;

        foreach ($snapshot as $key => $value) {

            switch ($key) {
                case 'errorReportingCaptureLevel':
                {
                    $this->setErrorReportingCaptureLevel($value);

                    break;
                }
                case 'errorHandler':
                {
                    $this->setErrorHandler($value);

                    break;
                }
                case 'exceptionHandler':
                {
                    $this->setExceptionHandler($value);

                    break;
                }
                case 'shutdownHandler':
                {
                    $this->setShutdownHandler($value);

                    break;
                }
                case 'onError':
                {
                    $this->setOnError($value);

                    break;
                }
                case 'onException':
                {
                    $this->setOnException($value);

                    break;
                }
                case 'onShutdownSuccess':
                {
                    $this->setOnShutdownSuccess($value);

                    break;
                }
                case 'onShutdownFailed':
                {
                    $this->setOnShutdownFailed($value);

                    break;
                }

                default:
                    throw new Exception('Invalid key');
                    break;
            }

        }

        return $result;
    }

}