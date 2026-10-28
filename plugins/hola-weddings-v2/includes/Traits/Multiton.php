<?php

namespace HW\Traits;

trait Multiton{

    protected static $instances = null;

    /**
     * return Object instance
     *
     * @param integer|string $key
     * @return self
     */
    public static function getInstance(int $key): self
    {
        if (!array_key_exists($key, self::$instances)) {

            self::$instances[$key] = new static($key);
        }

        return self::$instances[$key];
    }
}
