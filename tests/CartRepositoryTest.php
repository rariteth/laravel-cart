<?php

namespace Rariteth\LaravelCart\Tests;

use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use Rariteth\LaravelCart\CartServiceProvider;
use Rariteth\LaravelCart\Contracts\Repositories\CartRepositoryInterface;
use Rariteth\LaravelCart\Events\CartAddedItemEvent;
use Rariteth\LaravelCart\Tests\Fixtures\BuyableProduct;

class CartRepositoryTest extends TestCase
{
    /**
     * Set the package service provider.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [CartServiceProvider::class];
    }
    
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cart.database.connection', 'testing');
        
        $app['config']->set('session.driver', 'array');
        
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
    
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->app->afterResolving('migrator', function ($migrator) {
            $migrator->path(realpath(__DIR__ . '/../database/migrations'));
        });
    }
    
    
    /** @test */
    public function it_can_add_an_item()
    {
        Event::fake();
        /** @var CartRepositoryInterface $cartRepository */
        $cartRepository = $this->app->make(CartRepositoryInterface::class);

        $cartRepository->add(new BuyableProduct);

        $this->assertEquals(1, $cartRepository->getItems()->count());

        Event::assertDispatched(CartAddedItemEvent::class);
    }
}
