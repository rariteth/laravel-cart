<?php

declare(strict_types=1);

namespace Rariteth\LaravelCart\Repositories;

use Closure;
use Illuminate\Database\Connection;
use InvalidArgumentException;
use Rariteth\LaravelCart\Entities\CartItemOptions;
use Rariteth\LaravelCart\Contracts\CartInstanceInterface;
use Rariteth\LaravelCart\Contracts\Repositories\CartRepositoryInterface;
use Rariteth\LaravelCart\Entities\CartItem;
use Rariteth\LaravelCart\Contracts\BuyableInterface;
use Illuminate\Support\Collection;
use Rariteth\LaravelCart\Events\CartAddedItemEvent;
use Rariteth\LaravelCart\Events\CartClearedEvent;
use Rariteth\LaravelCart\Events\CartRefreshedEvent;
use Rariteth\LaravelCart\Events\CartRemovedBatchItemsEvent;
use Rariteth\LaravelCart\Events\CartRemovedItemEvent;
use Rariteth\LaravelCart\Events\CartUpdatedItemEvent;

/**
 * Class ProductRepository
 *
 * @package App\Repositories
 */
class CartRepository implements CartRepositoryInterface
{
    /** @var Collection */
    protected $items;
    
    /** @var bool */
    protected $shouldStoreInDatabase;
    
    /**
     * @var CartInstanceInterface
     */
    private $cartInstance;
    
    /**
     * ShoppingCartRepository constructor.
     *
     * @param CartInstanceInterface $cartInstance
     */
    public function __construct(CartInstanceInterface $cartInstance)
    {
        $shouldStoreInDatabase = config('cart.store_in_database');
        
        if ( ! \is_bool($shouldStoreInDatabase)) {
            throw new InvalidArgumentException('Config param `store_in_database` is not boolean');
        }
        
        $this->shouldStoreInDatabase = $shouldStoreInDatabase;
        $this->cartInstance          = $cartInstance;
    }
    
    /**
     * @param CartInstanceInterface $cartInstance
     *
     * @return CartRepositoryInterface
     */
    public function instance(CartInstanceInterface $cartInstance): CartRepositoryInterface
    {
        return new self($cartInstance);
    }
    
    /**
     * @param string $rowId
     *
     * @return null|CartItem
     */
    public function get(string $rowId): ?CartItem
    {
        return $this->getItems()->get($rowId);
    }
    
    /**
     * @inheritdoc
     */
    public function search(Closure $search): Collection
    {
        return $this->getItems()->filter($search);
    }
    
    /**
     * @param Collection $items
     */
    public function refresh(Collection $items): void
    {
        if ($items->isNotEmpty()) {
            $refreshItems = $items->map(function (CartItem $cartItem) {
                $cartItem->setAuthorized($this->isAuthorized());
                
                return $cartItem->update($cartItem->buyable);
            });
            
            $this->storeItems($this->getItems()->merge($refreshItems));
            
            event(new CartRefreshedEvent($refreshItems, $this->cartInstance));
        }
    }
    
    /**
     * @inheritdoc
     */
    public function add(BuyableInterface $buyable, int $qty = 1, array $options = []): CartItem
    {
        $items = $this->getItems();
        
        $cartItem = new CartItem($buyable, new CartItemOptions($options));
        
        $cartItem->setAuthorized($this->isAuthorized());
        
        if ($existItem = $items->get($cartItem->rowId)) {
            $qty += $existItem->qty;
        }
        
        $cartItem->setQty($qty);
        
        $this->storeItems($items->put($cartItem->rowId, $cartItem));
        
        event(new CartAddedItemEvent($cartItem, $this->cartInstance));
        
        return $cartItem;
    }
    
    /**
     * @inheritdoc
     */
    public function remove(CartItem $cartItem): void
    {
        $this->storeItems($this->getItems()->forget($cartItem->rowId));
        
        event(new CartRemovedItemEvent($cartItem, $this->cartInstance));
    }
    
    /**
     * @inheritdoc
     */
    public function removeBatch(Collection $items): void
    {
        if ($items->isNotEmpty()) {
            
            $rowIds = $items->pluck('rowId');
            
            $this->storeItems($this->getItems()->forget($rowIds));
            
            event(new CartRemovedBatchItemsEvent($items, $this->cartInstance));
        }
    }
    
    /**
     * @inheritdoc
     */
    public function update(CartItem $cartItem): void
    {
        if ($cartItem->qty === 0) {
            $this->remove($cartItem);
        } else {
            
            $this->storeItems($this->getItems()->put($cartItem->rowId, $cartItem));
            
            event(new CartUpdatedItemEvent($cartItem, $this->cartInstance));
        }
    }
    
    /**
     * @inheritdoc
     */
    public function getItems(): Collection
    {
        if ( ! $this->items) {
            $items      = $this->getSessionItems();
            $identifier = $this->getIdentifier();
            
            if ($identifier && $this->shouldStoreInDatabase) {
                $items = $items->merge($this->getDatabaseItems($identifier));
            }
            
            $this->items = $items;
        }
        
        return $this->items;
    }
    
    /**
     * @inheritdoc
     */
    public function getGuestItems(): Collection
    {
        return $this->getItems()->filter(function (CartItem $cartItem) {
            return ! $cartItem->authorized;
        });
    }
    
    /**
     * @inheritdoc
     */
    public function getAuthorizedItems(): Collection
    {
        return $this->getItems()->filter(function (CartItem $cartItem) {
            return $cartItem->authorized;
        });
    }
    
    /**
     * @inheritdoc
     */
    public function storeItems(Collection $items): void
    {
        // Session store
        $this->storeInSession($items);
        
        // Database store
        $identifier = $this->getIdentifier();
        
        if ($this->shouldStoreInDatabase && $identifier) {
            $this->storeInDatabase($identifier, $items);
        }
        
        $this->items = $items;
    }
    
    /**
     * @inheritdoc
     */
    public function getTotal(): float
    {
        return $this->getItems()->reduce(function ($total, CartItem $cartItem) {
            return $total + $cartItem->getTotal();
        }, 0.00);
    }
    
    /**
     * @inheritdoc
     */
    public function getCount(): int
    {
        return $this->getItems()->sum('qty');
    }
    
    /**
     * @inheritdoc
     */
    public function isEmpty(): bool
    {
        return $this->getItems()->count() === 0;
    }
    
    /**
     * @inheritdoc
     */
    public function clear(): void
    {
        // Destroy session
        $this->destroySessionItems();
        
        // Destroy database
        $identifier = $this->getIdentifier();
        
        if ($identifier && $this->shouldStoreInDatabase) {
            
            $this->destroyDatabaseItems($identifier);
        }
        
        event(new CartClearedEvent($this->cartInstance));
    }
    
    /**
     * @inheritdoc
     */
    public function hasItem(CartItem $cartItem): bool
    {
        return $this->getItems()->pluck('rowId')->contains($cartItem->rowId);
    }
    
    /**
     * @inheritdoc
     */
    public function getDatabaseItems(int $identifier): Collection
    {
        $storedCart = $this->getConnection()
                           ->table($this->getTableName())
                           ->select('content')
                           ->where('instance', $this->cartInstance->getInstance())
                           ->where('guard', $this->cartInstance->getGuard())
                           ->where('identifier', $identifier)
                           ->first();
        
        if ($storedCart && $storedCart->content) {
            return unserialize($storedCart->content, ['allowed_classes' => true]);
        }
        
        return collect();
    }
    
    /**
     * Store in database storage
     *
     * @param int        $identifier
     * @param Collection $items
     *
     * @return bool
     */
    public function storeInDatabase(int $identifier, Collection $items): bool
    {
        return $this->getConnection()
                    ->table($this->getTableName())
                    ->updateOrInsert(
                        [
                            'identifier' => $identifier,
                            'instance'   => $this->cartInstance->getInstance(),
                            'guard'      => $this->cartInstance->getGuard(),
                        ],
                        [
                            'content'    => serialize($items),
                            'updated_at' => now(),
                        ]
                    );
    }
    
    /**
     * @return Collection
     */
    private function getSessionItems(): Collection
    {
        return session()->get($this->sessionInstanceName()) ?: collect();
    }
    
    /**
     * Destroy cart items from session
     */
    private function destroySessionItems(): void
    {
        session()->forget($this->sessionInstanceName());
    }
    
    /**
     * Store in session storage
     */
    private function storeInSession(Collection $items): void
    {
        session()->put($this->sessionInstanceName(), $items);
    }
    
    /**
     * @param int $identifier
     */
    private function destroyDatabaseItems(int $identifier): void
    {
        $this->getConnection()
             ->table($this->getTableName())
             ->select('content')
             ->where('instance', $this->cartInstance->getInstance())
             ->where('guard', $this->cartInstance->getGuard())
             ->where('identifier', $identifier)
             ->delete();
    }
    
    /**
     * Get the database connection.
     *
     * @return Connection
     */
    private function getConnection(): Connection
    {
        return app('db')->connection($this->getConnectionName());
    }
    
    /**
     * Get the database table name.
     *
     * @return string
     */
    private function getTableName(): string
    {
        return (string)config('cart.database.table', 'shoppingcart');
    }
    
    /**
     * Get the database connection name.
     *
     * @return string
     */
    private function getConnectionName(): string
    {
        return (string)config('cart.database.connection', config('database.default'));
    }
    
    /**
     * @return string
     */
    private function sessionInstanceName(): string
    {
        return sprintf('%s.%s', config('cart.session_root_key'), $this->cartInstance->getInstance());
    }
    
    /**
     * @return int|null
     */
    private function getIdentifier(): ?int
    {
        return auth($this->cartInstance->getGuard())->id();
    }
    
    /**
     * User is Authorized?
     *
     * @return bool
     */
    private function isAuthorized(): bool
    {
        return $this->getIdentifier() !== null;
    }
}