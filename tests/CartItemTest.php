<?php

namespace Rariteth\LaravelCart\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Rariteth\LaravelCart\CartItem;
use Rariteth\LaravelCart\CartItemOptions;
use Rariteth\LaravelCart\Contracts\BuyableInterface;
use Rariteth\LaravelCart\Tests\Fixtures\BuyableProduct;

class CartItemTest extends TestCase
{
    use WithFaker;

    /** @test */
    public function creating_correctrly()
    {
        $product = $this->makeBuyebleProduct();
        $options = new CartItemOptions;
        
        $cartItem = new CartItem($product, $options);
        
        $this->assertEquals($cartItem->identifier, $product->getBuyableIdentifier($options));
        $this->assertEquals($cartItem->name, $product->getBuyableName($options));
        $this->assertEquals($cartItem->price, $product->getBuyablePrice($options));
    }
    
    /**
     * @test
     * @expectedException \Assert\AssertionFailedException
     * @expectedExceptionMessage Provided "0" is not greater than "0".
     */
    public function correctrly_identifier()
    {
        $product = new BuyableProduct(0);
        $options = new CartItemOptions;
        
        new CartItem($product, $options);
    }
    
    /**
     * @test
     */
    public function correctrly_total()
    {
        $qty   = $this->faker()->numberBetween(12, 1212);
        $price = $this->faker()->randomFloat(12.12, 1212.45);
        $total = $price * $qty;
        
        $product  = new BuyableProduct(1, 'Some item', $price);
        $options  = new CartItemOptions;
        $cartItem = new CartItem($product, $options);
        
        $cartItem->setQty($qty);
        
        $this->assertEquals($total, $cartItem->getTotal());
    }
    
    /**
     * @test
     */
    public function correctrly_qty()
    {
        $qty = $this->faker()->numberBetween(12, 1212);
        
        $product  = new BuyableProduct;
        $options  = new CartItemOptions;
        $cartItem = new CartItem($product, $options);
        
        $cartItem->setQty($qty);
        
        $this->assertEquals($qty, $cartItem->qty);
    }
    
    /**
     * @test
     */
    public function correctrly_authorized()
    {
        $product  = new BuyableProduct;
        $options  = new CartItemOptions;
        $cartItem = new CartItem($product, $options);
        
        $cartItem->setAuthorized(true);
        $this->assertEquals(true, $cartItem->authorized);
        
        $cartItem->setAuthorized(false);
        $this->assertEquals(false, $cartItem->authorized);
    }
    
    /**
     * @test
     */
    public function correctrly_update()
    {
        $product  = $this->makeBuyebleProduct();
        $product2 = $this->makeBuyebleProduct();
        
        $options  = new CartItemOptions;
        $cartItem = new CartItem($product, $options);
        
        $this->assertEquals($product->getBuyableIdentifier($options), $cartItem->identifier);
        $this->assertEquals($product->getBuyableName($options), $cartItem->name);
        $this->assertEquals($product->getBuyablePrice($options), $cartItem->price);
        
        $updatedCartItem = $cartItem->update($product2);
        $this->assertEquals($product2->getBuyableIdentifier($options), $updatedCartItem->identifier);
        $this->assertEquals($product2->getBuyableName($options), $updatedCartItem->name);
        $this->assertEquals($product2->getBuyablePrice($options), $updatedCartItem->price);
    }
    
    /**
     * @test
     * @expectedException \Assert\AssertionFailedException
     * @expectedExceptionMessage Value "qweqweqwe" is not an element of the valid values: identifier, rowId, qty, name, price, options,
     *                           addedAt, authorized
     */
    public function correctrly_getters()
    {
        $product = $this->makeBuyebleProduct();
        
        $options  = new CartItemOptions;
        $cartItem = new CartItem($product, $options);
        
        $getter = 'qweqweqwe';
        $cartItem->{$getter};
    }
    
    /**
     * @test
     * @expectedException \Assert\AssertionFailedException
     * @expectedExceptionMessage Value "" is blank, but was expected to contain a value.
     */
    public function correctrly_name()
    {
        $product = new BuyableProduct(123, '');
        $options = new CartItemOptions;
        
        new CartItem($product, $options);
    }
    
    /**
     * @test
     * @expectedException \Assert\AssertionFailedException
     * @expectedExceptionMessage Provided "0" is not greater than "0".
     */
    public function correctrly_price()
    {
        config()->set('cart.allow_zero_price', false);
        $product = new BuyableProduct(123, 'Some Item', 0.0);
        $options = new CartItemOptions;
        
        new CartItem($product, $options);
    }
    
    /**
     * @test
     */
    public function correctrly_zero_price()
    {
        config()->set('cart.allow_zero_price', true);
        
        $zeroPrice = 0.0;
        
        $product = new BuyableProduct(123, 'Some Item', $zeroPrice);
        $options = new CartItemOptions;
        
        $cartItem = new CartItem($product, $options);
        
        $this->assertEquals($zeroPrice, $cartItem->price);
    }
    
    /**
     * @test
     * @expectedException \Assert\AssertionFailedException
     * @expectedExceptionMessage Provided "-0.1" is not greater or equal than "0".
     */
    public function correctrly_price_negative()
    {
        $product = new BuyableProduct(123, 'Some Item', -0.1);
        $options = new CartItemOptions;
        
        new CartItem($product, $options);
    }
    
    /** @test */
    public function generate_row_id_correctrly()
    {
        $product  = new BuyableProduct;
        $options  = new CartItemOptions(['size' => 'XL', 'color' => 'red']);
        $cartItem = new CartItem($product, $options);
        
        $this->assertEquals($cartItem->rowId, '10a24ff3d07678a6c057bcdcbae7517b');
    }
    
    /** @test */
    public function it_can_be_cast_to_an_array()
    {
        $product  = new BuyableProduct;
        $options  = new CartItemOptions(['size' => 'XL', 'color' => 'red']);
        $cartItem = new CartItem($product, $options);
        
        $cartItem->setQty(123);
        
        $this->assertEquals(
            [
                'rowId'   => $cartItem->rowId,
                'id'      => $product->getBuyableIdentifier($options),
                'name'    => $product->getBuyableName($options),
                'price'   => 10.00,
                'qty'     => $cartItem->qty,
                'options' => [
                    'size'  => $options->size,
                    'color' => $options->color,
                ],
                'total'   => $cartItem->getTotal(),
                'addedAt' => $cartItem->addedAt->format(Carbon::DEFAULT_TO_STRING_FORMAT),
            ], $cartItem->toArray()
        );
    }
    
    /** @test */
    public function it_can_be_cast_to_a_json()
    {
        $product  = new BuyableProduct;
        $options  = new CartItemOptions(['size' => 'XL', 'color' => 'red']);
        $cartItem = new CartItem($product, $options);
        
        $cartItem->setQty(123);
        
        $json = json_encode(
            [
                'rowId'   => $cartItem->rowId,
                'id'      => $product->getBuyableIdentifier($options),
                'name'    => $product->getBuyableName($options),
                'qty'     => $cartItem->qty,
                'price'   => 10.00,
                'options' => [
                    'size'  => $options->size,
                    'color' => $options->color,
                ],
                'total'   => $cartItem->getTotal(),
                'addedAt' => $cartItem->addedAt->format(Carbon::DEFAULT_TO_STRING_FORMAT),
            ]
        );
        
        $this->assertEquals($json, $cartItem->toJson());
    }
    
    /**
     * @return \Rariteth\LaravelCart\Tests\Fixtures\BuyableProduct
     */
    protected function makeBuyebleProduct(): BuyableProduct
    {
        $faker = $this->faker();
        
        $id    = $faker->numberBetween(111, 333);
        $name  = $faker->name;
        $price = $faker->randomFloat(333, 5555);
        
        return new BuyableProduct($id, $name, $price);
    }
    
    /**
     * @return \Rariteth\LaravelCart\CartItemOptions
     */
    protected function makeOptions(): CartItemOptions
    {
        $faker = $this->faker();
        
        $optionsArray = $faker->shuffleArray(
            [
                'size'        => $faker->name,
                'color'       => $faker->colorName,
                'distributor' => $faker->userName,
            ]);
        
        return new CartItemOptions($optionsArray);
    }
}