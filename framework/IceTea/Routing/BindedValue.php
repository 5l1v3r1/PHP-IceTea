<?php

namespace IceTea\Routing;

use ArrayAccess;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @license MIT
 */
class BindedValue implements ArrayAccess
{
   
    /**
     * @var array
     */
    private $container = [];

    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct($data)
    {
        $this->container = $data;
    }

    /**
     * Return data into array.
     *
     * @return array
     */
    public function toArray()
    {
        return (array) $this->container;
    }

    /**
     * @param string|int $offset
     * @param any        $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * @param string|int $offset 
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * @param string|int $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * @param string|int $offset
     * @return bool|null
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * When instance throw in var_dump.
     *
     * @return mixed
     */
    public function __debugInfo()
    {
        return $this->container;
    }
}
