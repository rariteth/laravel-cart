<?php

declare(strict_types=1);

namespace Rariteth\LaravelCart;

use Rariteth\LaravelCart\Contracts\CartInstanceInterface;

class CartInstance implements CartInstanceInterface
{
    public const DEFAULT_INSTANCE = 'default';
    public const DEFAULT_GUARD    = 'web';
    
    /**
     * Holds the current cart instance
     *
     * @var string
     */
    private $instance;
    
    /**
     * @var null|string
     */
    private $guard;
    
    /**
     * Cart constructor
     *
     * @param string      $instance
     * @param string|null $guard
     */
    public function __construct(string $instance = self::DEFAULT_INSTANCE, string $guard = null)
    {
        $this->instance = $instance;
        $this->guard    = $guard ?: config('cart.auth_guard', self::DEFAULT_GUARD);
    }
    
    /**
     * Get the cart instance
     *
     * @return string
     */
    public function getInstance(): string
    {
        return $this->instance;
    }
    
    /**
     * Get cart guard name
     *
     * @return string
     */
    public function getGuard(): string
    {
        return $this->guard;
    }
}
