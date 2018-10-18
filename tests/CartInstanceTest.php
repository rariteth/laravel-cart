<?php

namespace Rariteth\LaravelCart\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase;
use Rariteth\LaravelCart\CartInstance;

class CartInstanceTest extends TestCase
{
    use WithFaker;
    
    /**
     * @test
     * @expectedException \Assert\AssertionFailedException
     * @expectedExceptionMessage Value "" is blank, but was expected to contain a value.
     */
    public function cannot_empty_instance_name()
    {
        new CartInstance('');
    }
    
    /**
     * @test
     * @expectedException \Assert\AssertionFailedException
     * @expectedExceptionMessage Value "" is blank, but was expected to contain a value.
     */
    public function cannot_empty_guard_name()
    {
        new CartInstance('test', '');
    }
    
    /**
     * @test
     * @throws \Assert\AssertionFailedException
     */
    public function correctry_getting_instance_name()
    {
        $instanceName = $this->faker()->name;
        
        $cart = new CartInstance($instanceName);
        
        $this->assertEquals($instanceName, $cart->getInstance());
    }
    
    /**
     * @test
     * @throws \Assert\AssertionFailedException
     */
    public function correctry_getting_guard_name()
    {
        $guardName = $this->faker()->name;
        
        $cart = new CartInstance($this->faker()->name, $guardName);
        
        $this->assertEquals($guardName, $cart->getGuard());
    }
}
