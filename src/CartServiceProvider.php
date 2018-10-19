<?php

declare(strict_types=1);

namespace Rariteth\LaravelCart;

use Illuminate\Auth\Events\Logout;
use Illuminate\Session\SessionManager;
use Illuminate\Support\ServiceProvider;
use Rariteth\LaravelCart\Contracts\CartInstanceInterface;
use Rariteth\LaravelCart\Contracts\Repositories\CartRepositoryInterface;
use Rariteth\LaravelCart\Entities\CartInstance;
use Rariteth\LaravelCart\Repositories\CartRepository;

class CartServiceProvider extends ServiceProvider
{
    
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cart.php', 'cart');
        
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
        $this->app->bind(CartInstanceInterface::class, function () {
            $defaultInstance = (string)config('cart.default_cart_instance');
            $defaultGuard    = (string)config('cart.default_auth_guard');
            
            return new CartInstance($defaultInstance, $defaultGuard);
        });
        
        //$this->setupEvents();
        
        if ($this->app->runningInConsole()) {
            
            // Config publish
            $this->publishes([__DIR__ . '/../config/cart.php' => config_path('cart.php')], 'laravel-cart-config');
            
            // Migration publish
            $stubMigrationFile = sprintf('%s/../database/migrations/0000_00_00_000000_create_cart_table.php', __DIR__);
            $copyMigrationFile = database_path(sprintf('migrations/%s_create_cart_table.php', date('Y_m_d_His')));
            
            $this->publishes([$stubMigrationFile => $copyMigrationFile], 'migrations');
        }
    }
    
    private function setupEvents(): void
    {
        $this->app['events']->listen(Logout::class, function () {
            if ($this->app['config']->get('cart.destroy_on_logout')) {
                $this->app->make(SessionManager::class)->forget('cart');
            }
        });
    }
}
