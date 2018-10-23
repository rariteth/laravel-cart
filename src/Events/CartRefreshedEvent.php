<?php

declare(strict_types=1);

namespace Rariteth\LaravelCart\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Collection;
use Rariteth\LaravelCart\Contracts\CartInstanceInterface;

class CartRefreshedEvent
{
    use Dispatchable, SerializesModels;
    
    /**
     * @var CartInstanceInterface
     */
    private $cartInstance;
    
    /**
     * @var Collection
     */
    private $refreshItems;
    
    /**
     * Create a new event instance.
     *
     * @param Collection            $refreshItems
     * @param CartInstanceInterface $cartInstance
     */
    public function __construct(Collection $refreshItems, CartInstanceInterface $cartInstance)
    {
        $this->cartInstance = $cartInstance;
        $this->refreshItems = $refreshItems;
    }
}
