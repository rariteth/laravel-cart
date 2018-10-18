<?php

namespace Rariteth\LaravelCart\Tests\Fixtures;

use Rariteth\LaravelCart\Entities\CartItemOptions;
use Rariteth\LaravelCart\Contracts\BuyableInterface;

class BuyableProduct implements BuyableInterface
{
    /**
     * @var int|string
     */
    private $id;
    
    /**
     * @var string
     */
    private $name;
    
    /**
     * @var float
     */
    private $price;
    
    /**
     * BuyableProduct constructor.
     *
     * @param int|string $id
     * @param string     $name
     * @param float      $price
     */
    public function __construct($id = 1, $name = 'Item name', $price = 10.00)
    {
        $this->id    = $id;
        $this->name  = $name;
        $this->price = $price;
    }
    
    /**
     * Get the identifier of the Buyable item.
     *
     * @param CartItemOptions $options
     *
     * @return int
     */
    public function getBuyableIdentifier(CartItemOptions $options): int
    {
        return $this->id;
    }
    
    /**
     * Get the description or title of the Buyable item.
     *
     * @param CartItemOptions $options
     *
     * @return string
     */
    public function getBuyableName(CartItemOptions $options): string
    {
        return $this->name;
    }
    
    /**
     * Get the price of the Buyable item.
     *
     * @param CartItemOptions $options
     *
     * @return float
     */
    public function getBuyablePrice(CartItemOptions $options): float
    {
        return $this->price;
    }
}