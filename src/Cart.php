<?php

declare(strict_types=1);

namespace Rariteth\LaravelCart;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Database\DatabaseManager;
use Rariteth\LaravelCart\Contracts\BuyableInterface;
use Rariteth\LaravelCart\Contracts\CartInterface;
use Rariteth\LaravelCart\Contracts\Repositories\CartRepositoryInterface;
use Rariteth\LaravelCart\Exceptions\UnknownModelException;
use Rariteth\LaravelCart\Exceptions\InvalidRowIDException;
use Rariteth\LaravelCart\Exceptions\CartAlreadyStoredException;

class Cart implements CartInterface
{
    public const DEFAULT_INSTANCE = 'default';
    
    /**
     * Holds the current cart instance.
     *
     * @var string
     */
    private $instance;
    
    /**
     * Cart constructor.
     *
     * @param string                  $instance
     */
    public function __construct(string $instance = self::DEFAULT_INSTANCE)
    {
        $this->instance       = $instance;
    }
    
    /**
     * Get the current cart instance.
     *
     * @return string
     */
    public function getInstance(): string
    {
        return $this->instance;
    }
    
    /**
     * Add an items to the cart.
     *
     * @param Collection $items
     *
     * @return Collection
     */
    public function addMulti(Collection $items): Collection
    {
        return $items->map(function (BuyableInterface $buyable) {
            return $this->add($buyable);
        });
    }
    
    /**
     * Update the cart item with the given rowId.
     *
     * @param string $rowId
     * @param mixed  $qty
     *
     * @return CartItem
     */
    public function update($rowId, $qty)
    {
        $cartItem = $this->get($rowId);
        
        if ($qty instanceof BuyableInterface) {
            $cartItem->updateFromBuyable($qty);
        } elseif (is_array($qty)) {
            $cartItem->updateFromArray($qty);
        } else {
            $cartItem->qty = $qty;
        }
        
        $content = $this->items;
        
        if ($rowId !== $cartItem->rowId) {
            $content->pull($rowId);
            
            if ($content->has($cartItem->rowId)) {
                $existingCartItem = $this->get($cartItem->rowId);
                $cartItem->setQty($existingCartItem->qty + $cartItem->qty);
            }
        }
        
        if ($cartItem->qty <= 0) {
            $this->remove($cartItem->rowId);
            
            return;
        } else {
            $content->put($cartItem->rowId, $cartItem);
        }
        
        $this->events->fire('cart.updated', $cartItem);
        
        $this->session->put($this->instance, $content);
        
        return $cartItem;
    }
    
    /**
     * Remove the cart item with the given rowId from the cart.
     *
     * @param string $rowId
     *
     * @return void
     */
    public function remove($rowId)
    {
        $cartItem = $this->get($rowId);
        
        $content = $this->items;
        
        $content->pull($cartItem->rowId);
        
        $this->events->fire('cart.removed', $cartItem);
        
        $this->session->put($this->instance, $content);
    }
    
    /**
     * Get a cart item from the cart by its rowId.
     *
     * @param string $rowId
     *
     * @return CartItem
     */
    public function get(string $rowId): CartItem
    {
        if ( ! $this->items->has($rowId)) {
            throw new InvalidRowIDException("The cart does not contain rowId {$rowId}.");
        }
        
        return $this->items->get($rowId);
    }
    
    /**
     * Destroy the current cart instance.
     *
     * @return void
     */
    public function destroy()
    {
        $this->session->remove($this->instance);
    }
    
    /**
     * Get the number of items in the cart.
     *
     * @return int|float
     */
    public function count()
    {
        return $this->items->sum('qty');
    }
    
    /**
     * Get the total price of the items in the cart.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeperator
     *
     * @return string
     */
    public function total($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        $total = $this->items->reduce(function ($total, CartItem $cartItem) {
            return $total + ($cartItem->qty * $cartItem->priceTax);
        }, 0);
        
        return $this->numberFormat($total, $decimals, $decimalPoint, $thousandSeperator);
    }
    
    /**
     * Get the total tax of the items in the cart.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeperator
     *
     * @return float
     */
    public function tax($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        $tax = $this->items->reduce(function ($tax, CartItem $cartItem) {
            return $tax + ($cartItem->qty * $cartItem->tax);
        }, 0);
        
        return $this->numberFormat($tax, $decimals, $decimalPoint, $thousandSeperator);
    }
    
    /**
     * Get the subtotal (total - tax) of the items in the cart.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeperator
     *
     * @return float
     */
    public function subtotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        $subTotal = $this->items->reduce(function ($subTotal, CartItem $cartItem) {
            return $subTotal + ($cartItem->qty * $cartItem->price);
        }, 0);
        
        return $this->numberFormat($subTotal, $decimals, $decimalPoint, $thousandSeperator);
    }
    
    /**
     * Search the cart content for a cart item matching the given search closure.
     *
     * @param \Closure $search
     *
     * @return Collection
     */
    public function search(Closure $search)
    {
        return $this->items->filter($search);
    }
    
    /**
     * Associate the cart item with the given rowId with the given model.
     *
     * @param string $rowId
     * @param mixed  $model
     *
     * @return void
     */
    public function associate($rowId, $model)
    {
        if (\is_string($model) && ! class_exists($model)) {
            throw new UnknownModelException("The supplied model {$model} does not exist.");
        }
        
        $cartItem = $this->get($rowId);
        
        $cartItem->associate($model);
        
        $content = $this->items;
        
        $this->items->put($cartItem->rowId, $cartItem);
        
        $this->session->put($this->instance, $content);
    }
    
    /**
     * Store an the current instance of the cart.
     *
     * @param mixed $identifier
     *
     * @return void
     */
    public function store($identifier)
    {
        if ($this->storedCartWithIdentifierExists($identifier)) {
            throw new CartAlreadyStoredException("A cart with identifier {$identifier} was already stored.");
        }
        
        $this->getConnection()
             ->table($this->getTableName())
             ->insert(
                 [
                     'identifier' => $identifier,
                     'instance'   => $this->getInstance(),
                     'content'    => serialize($this->items)
                 ]
             );
        
        $this->events->fire('cart.stored');
    }
    
    /**
     * Restore the cart with the given identifier.
     *
     * @param mixed $identifier
     *
     * @return void
     */
    public function restore($identifier)
    {
        if ( ! $this->storedCartWithIdentifierExists($identifier)) {
            return;
        }
        
        $stored = $this->getConnection()->table($this->getTableName())
                       ->where('identifier', $identifier)->first();
        
        $storedContent = unserialize($stored->content, ['allowed_classes' => true]);
        
        $currentInstance = $this->getInstance();
        
        $this->instance($stored->instance);
        
        $content = $this->items;
        
        foreach ($storedContent as $cartItem) {
            $content->put($cartItem->rowId, $cartItem);
        }
        
        $this->events->fire('cart.restored');
        
        $this->session->put($this->instance, $content);
        
        $this->instance($currentInstance);
        
        $this->getConnection()->table($this->getTableName())
             ->where('identifier', $identifier)->delete();
    }
    
    /**
     * Magic method to make accessing the total, tax and subtotal properties possible.
     *
     * @param string $attribute
     *
     * @return float|null
     */
    public function __get($attribute)
    {
        if ($attribute === 'total') {
            return $this->total();
        }
        
        if ($attribute === 'subtotal') {
            return $this->subtotal();
        }
        
        return null;
    }
    
    /**
     * @param $identifier
     *
     * @return bool
     */
    private function storedCartWithIdentifierExists($identifier)
    {
        return $this->getConnection()->table($this->getTableName())->where('identifier', $identifier)->exists();
    }
    
    /**
     * Get the database connection.
     *
     * @return \Illuminate\Database\Connection
     */
    private function getConnection()
    {
        $connectionName = $this->getConnectionName();
        
        return app(DatabaseManager::class)->connection($connectionName);
    }
    
    /**
     * Get the database table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return config('cart.database.table', 'shoppingcart');
    }
    
    /**
     * Get the database connection name.
     *
     * @return string
     */
    private function getConnectionName()
    {
        return config('cart.database.connection', config('database.default'));
    }
    
    /**
     * Get the Formated number
     *
     * @param $value
     * @param $decimals
     * @param $decimalPoint
     * @param $thousandSeperator
     *
     * @return string
     */
    private function numberFormat($value, $decimals, $decimalPoint, $thousandSeperator)
    {
        if ($decimals === null) {
            $decimals = config('cart.format.decimals', 2);
        }
        if ($decimalPoint === null) {
            $decimalPoint = config('cart.format.decimal_point', '.');
        }
        if ($thousandSeperator === null) {
            $thousandSeperator = config('cart.format.thousand_separator', ',');
        }
        
        return number_format($value, $decimals, $decimalPoint, $thousandSeperator);
    }
}
