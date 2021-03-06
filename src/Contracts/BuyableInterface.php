<?php

namespace Rariteth\LaravelCart\Contracts;

use Rariteth\LaravelCart\Entities\CartItemOptions;

/**
 * BuyableInterface
 */
interface BuyableInterface
{
    /**
     * Get the identifier of the Buyable item.
     *
     * @param CartItemOptions $options
     *
     * @return int
     */
    public function getBuyableIdentifier(CartItemOptions $options): int;
    
    /**
     * Get the description or title of the Buyable item.
     *
     * @param CartItemOptions $options
     *
     * @return string
     */
    public function getBuyableName(CartItemOptions $options): string;
    
    /**
     * Get the price of the Buyable item.
     *
     * @param CartItemOptions $options
     *
     * @return float
     */
    public function getBuyablePrice(CartItemOptions $options): float;
}