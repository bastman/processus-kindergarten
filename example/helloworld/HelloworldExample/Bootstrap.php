<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seb
 * Date: 11/23/12
 * Time: 4:19 PM
 * To change this template use File | Settings | File Templates.
 */
namespace HelloworldExample;

use Processus\Kindergarten\Playground;
use Exception;

class Bootstrap
{
    /**
     * @var bool
     */
    protected $initialized = false;
    /**
     * @var Playground
     */
    protected $playground;

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

    /**
     * @return Playground
     */
    public function getPlayground()
    {
        if (!$this->playground) {
            $this->playground = Playground::getInstance();
        }

        return $this->playground;
    }

    // ======== init / run =====
    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * @return self
     */
    public function init()
    {
        $result = $this;
        if ($this->initialized === true) {

            return $result;
        }

        ini_set('display_errors', false);

        $playground = $this->getPlayground();
        $playground
            ->play()
            ->setErrorReportingCaptureLevel((E_ALL | E_STRICT))
            ->setOnException(
            array($this, 'handleException')
        );

        // setup locale
        setlocale(LC_ALL, 'C');
        date_default_timezone_set('Europe/Berlin');

        $this->initialized = true;

        return $result;
    }

    /**
     * @param self
     * @param Exception $exception
     */
    public function handleException(
        Playground $playground,
        Exception $exception
    ) {
        // log ...

        // delegate ...
        throw $exception;
    }

}
