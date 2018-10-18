<?php

namespace Rariteth\LaravelCart\Entities;

use Assert\Assertion;
use Assert\InvalidArgumentException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Rariteth\LaravelCart\Contracts\BuyableInterface;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Class CartItem
 *
 * @property int              $identifier
 * @property string           $rowId
 * @property int              $qty
 * @property string           $name
 * @property float            $price
 * @property CartItemOptions  $options
 * @property Carbon           $addedAt
 * @property BuyableInterface $buyable
 * @property bool             $authorized
 *
 * @package Rariteth\LaravelCart
 */
class CartItem implements Arrayable, Jsonable
{
    /**
     * The rowID of the cart item.
     *
     * @var string
     */
    private $rowId;
    
    /**
     * The ID of the cart item.
     *
     * @var int
     */
    private $identifier;
    
    /**
     * The quantity for this cart item.
     *
     * @var int
     */
    private $qty = 1;
    
    /**
     * The name of the cart item.
     *
     * @var string
     */
    private $name;
    
    /**
     * The price of the cart item.
     *
     * @var float
     */
    private $price;
    
    /**
     * The options for this cart item.
     *
     * @var CartItemOptions
     */
    private $options;
    
    /**
     * Added timestamp of item
     *
     * @var Carbon
     */
    private $addedAt;
    
    /**
     * Buyable class name
     *
     * @var string
     */
    private $buyableClass;
    
    /**
     * Add item as authorized user or not
     *
     * @var bool
     */
    private $authorized = false;
    
    private $attributes
        = [
            'identifier',
            'rowId',
            'qty',
            'name',
            'price',
            'options',
            'addedAt',
            'authorized',
        ];
    
    /**
     * CartItem constructor.
     *
     * @param BuyableInterface $buyable
     * @param CartItemOptions  $options
     *
     * @throws InvalidArgumentException
     */
    public function __construct(BuyableInterface $buyable, CartItemOptions $options)
    {
        $allowZeroPrice = config('cart.allow_zero_price', false);
        
        $identifier = $buyable->getBuyableIdentifier($options);
        $name       = $buyable->getBuyableName($options);
        $price      = $buyable->getBuyablePrice($options);
        
        Assertion::boolean($allowZeroPrice);
        Assertion::greaterThan($identifier, 0);
        Assertion::notBlank($name);
        Assertion::greaterOrEqualThan($price, 0.0);
        
        if ( ! $allowZeroPrice) {
            Assertion::greaterThan($price, 0.0);
        }
        
        $this->identifier   = $identifier;
        $this->name         = $name;
        $this->price        = $price;
        $this->rowId        = $this->generateRowId($identifier, $options);
        $this->options      = $options;
        $this->addedAt      = now();
        $this->buyableClass = \get_class($buyable);
    }
    
    /**
     * Returns the formatted total.
     * Total is price for whole CartItem
     *
     * @return float
     */
    public function getTotal(): float
    {
        return $this->qty * $this->price;
    }
    
    /**
     * Set the quantity for this cart item.
     *
     * @param int $qty
     */
    public function setQty(int $qty)
    {
        $this->qty = $qty;
    }
    
    /**
     * @param bool $authorized
     */
    public function setAuthorized(bool $authorized): void
    {
        $this->authorized = $authorized;
    }
    
    /**
     * Update the cart item from a Buyable.
     *
     * @param BuyableInterface $buyable
     *
     * @return self
     */
    public function update(BuyableInterface $buyable): self
    {
        $this->identifier = $buyable->getBuyableIdentifier($this->options);
        $this->name       = $buyable->getBuyableName($this->options);
        $this->price      = $buyable->getBuyablePrice($this->options);
        
        return $this;
    }
    
    /**
     * Get an attribute from the cart item or get the associated model.
     *
     * @param string $attribute
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function __get(string $attribute)
    {
        if ($attribute === 'buyable') {
            
            return \call_user_func([$this->buyableClass, 'findOrFail'], $this->identifier);
        }
        
        Assertion::inArray($attribute, $this->attributes);
        
        return $this->{$attribute};
    }
    
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'rowId'   => $this->rowId,
            'id'      => $this->identifier,
            'name'    => $this->name,
            'qty'     => $this->qty,
            'price'   => $this->price,
            'options' => $this->options->toArray(),
            'total'   => $this->getTotal(),
            'addedAt' => $this->addedAt->format(Carbon::DEFAULT_TO_STRING_FORMAT),
        ];
    }
    
    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     * Generate a unique id for the cart item.
     *
     * @param string          $id
     * @param CartItemOptions $options
     *
     * @return string
     */
    protected function generateRowId($id, CartItemOptions $options): string
    {
        $sortedOptions = $options->sortKeys();
        
        return md5($id . serialize($sortedOptions));
    }
}
