<?php

declare(strict_types=1);

namespace Rariteth\LaravelCart\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Rariteth\LaravelCart\Entities\CartItem;
use Rariteth\LaravelCart\Contracts\CartInstanceInterface;

class CartAddedItemEvent
{
    use Dispatchable, SerializesModels;
    
    /**
     * @var CartItem
     */
    private $cartItem;
    
    /**
     * @var CartInstanceInterface
     */
    private $cartInstance;
    
    /**
     * Create a new event instance.
     *
     * @param CartItem              $cartItem
     * @param CartInstanceInterface $cartInstance
     */
    public function __construct(CartItem $cartItem, CartInstanceInterface $cartInstance)
    {
        $this->cartItem     = $cartItem;
        $this->cartInstance = $cartInstance;
    }
}
