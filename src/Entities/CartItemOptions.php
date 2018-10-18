<?php

namespace Rariteth\LaravelCart\Entities;

use Illuminate\Support\Collection;

class CartItemOptions extends Collection
{
    /**
     * Get the option by the given key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }
    
    /**
     * Set the option by the given key and value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return self
     */
    public function __set($key, $value)
    {
        return $this->put($key, $value);
    }
    
    /**
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }
    
}