<?php

declare(strict_types=1);

namespace Rariteth\LaravelCart\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Collection;
use Rariteth\LaravelCart\Contracts\CartInstanceInterface;

class CartRemovedBatchItemsEvent
{
    use Dispatchable, SerializesModels;
    
    /**
     * @var Collection
     */
    private $cartItems;
    
    /**
     * @var CartInstanceInterface
     */
    private $cartInstance;
    
    /**
     * Create a new event instance.
     *
     * @param Collection            $cartItems
     * @param CartInstanceInterface $cartInstance
     */
    public function __construct(Collection $cartItems, CartInstanceInterface $cartInstance)
    {
        $this->cartItems    = $cartItems;
        $this->cartInstance = $cartInstance;
    }
}
