<?php

use Rariteth\LaravelCart\Contracts\CartInstanceInterface;
use Rariteth\LaravelCart\Contracts\Repositories\CartRepositoryInterface;

if ( ! function_exists('cart_repository')) {
    /**
     * @param CartInstanceInterface|null $cartInstance
     *
     * @return CartRepositoryInterface $cartRepository
     */
    function cart_repository(CartInstanceInterface $cartInstance = null)
    {
        /** @var CartRepositoryInterface $cartRepository */
        $cartRepository = app()->make(CartRepositoryInterface::class);
        
        return $cartInstance ? $cartRepository->instance($cartInstance) : $cartRepository;
    }
}

if ( ! function_exists('cart_number_format')) {
    /**
     * @param float $value
     *
     * @return string
     */
    function cart_number_format(float $value)
    {
        $decimals          = config('cart.format.decimals', 2);
        $decimalPoint      = config('cart.format.decimal_point', '.');
        $thousandSeparator = config('cart.format.thousand_separator', ',');
        
        return number_format($value, $decimals, $decimalPoint, $thousandSeparator);
    }
}
