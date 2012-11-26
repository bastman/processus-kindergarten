<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seb
 * Date: 11/26/12
 * Time: 3:30 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Processus\Kindergarten\Proxy;

use InvalidArgumentException;

class ShutdownHandler
{
    /**
     * @var bool
     */
    protected $enabled = false;
    /**
     * @var callable|null
     */
    protected $callable;

    /**
     * @param bool $enabled
     * @return self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = ($enabled === true);

        return $this;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return ($this->enabled === true);
    }

    /**
     * @return self
     */
    public function execute()
    {
        $result = false;
        if (!$this->getEnabled()) {
            return $result;
        }

        $callable = $this->callable;

        if (!is_callable($callable)) {

            return $result;
        }

        $params = array();
        $result = call_user_func_array($callable, $params);

        return $result;
    }

    /**
     * @param $callable|null
     * @return self
     */
    public function setCallable($callable)
    {
        if ($callable !== null) {
            if (!is_callable($callable)) {
                throw new InvalidArgumentException(
                    'Parameter "callable" must be callable or null'
                );
            }
        }

        $this->callable = $callable;

        return $this;
    }

    /**
     * @return self
     */
    public function register()
    {
        register_shutdown_function(
            array(
                $this,
                'execute'
            )
        );

        return $this;
    }

}
