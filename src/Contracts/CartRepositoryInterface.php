<?php

declare(strict_types=1);

namespace Rariteth\LaravelCart\Contracts\Repositories;

use Closure;
use Rariteth\LaravelCart\CartItem;
use Rariteth\LaravelCart\Contracts\CartInstanceInterface;
use Rariteth\LaravelCart\Contracts\CartInterface;
use Illuminate\Support\Collection;
use Rariteth\LaravelCart\Contracts\BuyableInterface;

/**
 * Interface CartRepositoryInterface
 *
 * @package Rariteth\LaravelCart\Contracts\Repositories
 */
interface CartRepositoryInterface
{
    /**
     * @return Collection
     */
    public function getItems(): Collection;
    
    /**
     * @return Collection
     */
    public function getExpiredItems(): Collection;
    
    /**
     * @return Collection
     */
    public function getGuestItems(): Collection;
    
    /**
     * @return Collection
     */
    public function getAuthorizedItems(): Collection;
    
    /**
     * @return float
     */
    public function getTotal(): float;
    
    /**
     * Get the number of items in the cart.
     *
     * @return int
     */
    public function getCount(): int;
    
    /**
     * @return bool
     */
    public function isEmpty(): bool;
    
    /**
     * @inheritdoc
     */
    public function hasItem(CartInterface $cart, CartItem $cartItem): bool;
    
    /**
     * Clear the cart
     */
    public function clear(): void;
    
    /**
     * Store all items
     */
    public function storeItems(): void;
    
    /**
     * @param BuyableInterface $buyable
     * @param int              $qty
     * @param array            $options
     *
     * @return CartItem
     */
    public function add(BuyableInterface $buyable, int $qty, array $options = []): CartItem;
    
    /**
     * @param CartItem $cartItem
     */
    public function remove(CartItem $cartItem): void;
    
    /**
     * @param CartItem $cartItem
     */
    public function update(CartItem $cartItem): void;
    
    /**
     * @param int                   $identifier
     *
     * @param CartInstanceInterface $cartInstance
     *
     * @return Collection
     */
    public function storedItemsByIdentifier(int $identifier, CartInstanceInterface $cartInstance): Collection;
    
    /**
     * @param CartInstanceInterface $cartInstance
     *
     * @return CartRepositoryInterface
     */
    public function instance(CartInstanceInterface $cartInstance): CartRepositoryInterface;
    
    /**
     * @param string $rowId
     *
     * @return CartItem|null
     */
    public function get(string $rowId): ?CartItem;
    
    /**
     * Search the cart content for a cart item matching the given search closure.
     *
     * @param Closure $search
     *
     * @return Collection
     */
    public function search(Closure $search): Collection;
}