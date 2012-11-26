<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seb
 * Date: 11/26/12
 * Time: 6:18 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Processus\Kindergarten\Sandbox;

use Processus\Kindergarten\Playground;
use InvalidArgumentException;
use Exception;

class SimpleSandbox
{

    /**
     * @var self|null
     */
    protected $playground;

    /**
     * @var callable
     */
    protected $toy;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @var Exception|null
     */
    protected $exception;
    /**
     * @var
     */
    protected $delegateExceptionEnabled;

    /**
     * @var int|null
     */
    protected $errorReportingCaptureLevel;

    /**
     * @var callable|null
     */
    protected $onError;

    /**
     * @return int
     */
    public function getErrorReportingCaptureLevelAllStrict()
    {
        return (E_ALL | E_NOTICE);
    }

    /**
     * @return int
     */
    public function getErrorReportingCaptureLevelAllStrictNoNotice()
    {
        return ((E_ALL | E_STRICT) ^ E_NOTICE);
    }


    /**
     * @param bool $enabled
     * @return self
     */
    public function setDelegateExceptionEnabled($enabled)
    {
        $this->delegateExceptionEnabled = ($enabled === true);

        return $this;
    }

    /**
     * @return bool
     */
    public function getDelegateExceptionEnabled()
    {
        return ($this->delegateExceptionEnabled === true);
    }

    /**
     * @param Playground $playground
     * @return self
     */
    public function setPlayground(Playground $playground)
    {
        $this->playground = $playground;

        return $this;
    }

    /**
     * @return null|Playground
     */
    public function getPlayground()
    {
        return $this->playground;
    }

    /**
     * @return bool
     */
    public function hasPlayground()
    {
        return ($this->playground instanceof Playground);
    }

    /**
     * @param callable $untrustedCallable
     * @return self
     * @throws Exception
     */
    public function setToy($untrustedCallable)
    {
        if (!is_callable($untrustedCallable)) {

            throw new InvalidArgumentException(
                'Parameter untrustedCallable must be callable.'
            );
        }

        $this->toy = $untrustedCallable;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getToy()
    {
        return $this->toy;
    }

    /**
     * @param array $params
     * @return self
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        if (!is_array($this->params)) {
            $this->params = array();
        }

        return $this->params;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return Exception|null
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return bool
     */
    public function hasException()
    {
        return ($this->exception instanceof Exception);
    }

    /**
     * @param int|null $level
     */
    public function setErrorReportingCaptureLevel($level)
    {
        $result = $this;

        if ($level === null) {
            $this->errorReportingCaptureLevel = $level;

            return $result;
        }

        $isValid = (is_int($level));
        if (!$isValid) {
            throw new InvalidArgumentException(
                'Parameter level must be int or null'
            );
        }
        $this->errorReportingCaptureLevel = $level;

        return $result;
    }

    /**
     * @return int|null
     */
    public function getErrorReportingCaptureLevel()
    {
        return $this->errorReportingCaptureLevel;
    }

    /**
     * @param callable|null $callable
     * @return self
     * @throws InvalidArgumentException
     */
    public function setOnError($callable)
    {
        if ($callable !== null) {
            if (!is_callable($callable)) {

                throw new InvalidArgumentException(
                    'Parameter "callable" must be callable, closure or null'
                );
            }
        }
        $this->onError = $callable;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getOnError()
    {
        return $this->onError;
    }

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

        // - errorReportingCaptureLevel
        $errorReportingCaptureLevel = $this->getErrorReportingCaptureLevel();
        if ($errorReportingCaptureLevel !== null) {
            $playground->setErrorReportingCaptureLevel(
                $errorReportingCaptureLevel
            );
        }
        // - onError callback
        $onError = $this->getOnError();
        if ($onError !== null) {
            $playground->setOnError(
                $onError
            );

        }

        return $this;
    }
}
