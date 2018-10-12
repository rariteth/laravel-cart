<?php

declare(strict_types=1);

namespace Rariteth\LaravelCart\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Rariteth\LaravelCart\Contracts\CartInstanceInterface;

class CartClearedEvent
{
    use Dispatchable, SerializesModels;
    
    /**
     * @var CartInstanceInterface
     */
    private $cartInstance;
    
    /**
     * Create a new event instance.
     *
     * @param CartInstanceInterface $cartInstance
     */
    public function __construct(CartInstanceInterface $cartInstance)
    {
        $this->cartInstance = $cartInstance;
    }
}
