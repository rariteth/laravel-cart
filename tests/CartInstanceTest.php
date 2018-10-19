<?php

namespace Rariteth\LaravelCart\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase;
use Rariteth\LaravelCart\Entities\CartInstance;

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
        $guardName = $this->faker()->name;
        
        new CartInstance('', $guardName);
    }
    
    /**
     * @test
     * @expectedException \Assert\AssertionFailedException
     * @expectedExceptionMessage Value "" is blank, but was expected to contain a value.
     */
    public function cannot_empty_guard_name()
    {
        $instanceName = $this->faker()->name;
        
        new CartInstance($instanceName, '');
    }
    
    /**
     * @test
     * @throws \Assert\AssertionFailedException
     */
    public function correctry_getting_instance_name()
    {
        $instanceName = $this->faker()->name;
        $guardName    = $this->faker()->name;
        
        $cart = new CartInstance($instanceName, $guardName);
        
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
