
[![Build Status](https://travis-ci.org/rariteth/laravel-cart.svg?branch=master)](https://travis-ci.org/rariteth/laravel-cart)
[![Latest Stable Version](https://poser.pugx.org/rariteth/laravel-cart/v/stable)](https://packagist.org/packages/rariteth/laravel-cart)
[![Total Downloads](https://poser.pugx.org/rariteth/laravel-cart/downloads)](https://packagist.org/packages/rariteth/laravel-cart)
[![License](https://poser.pugx.org/rariteth/laravel-cart/license)](https://packagist.org/packages/rariteth/laravel-cart)

* [Installation](#installation)
* [Configuration](#configuration)
* [Instances](#instances)
* [Models](#models)
* [Database](#database)
* [Events](#events)
* [Example](#example)

## Installation

Install the package through [Composer](http://getcomposer.org/).

Run the Composer require command from the Terminal:

    composer require rariteth/laravel-cart

If you're using Laravel 5.5, this is all there is to do.

Now you're ready to start using the laravel-cart in your application.

### Configuration
To save cart into the database so you can retrieve it later, the package needs to know which database connection to use and what the name of the table is.
By default the package will use the default database connection and use a table named `laravel-cart`.
If you want to change these options, you'll have to publish the `config` file.

    php artisan vendor:publish --provider="Rariteth\LaravelCart\CartServiceProvider" --tag="laravel-cart-config"

This will give you a `cart.php` config file in which you can make the changes.

To make your life easy, the package also includes a ready to use `migration` which you can publish by running:

    php artisan vendor:publish --provider="Rariteth\LaravelCart\CartServiceProvider" --tag="migrations"

This will place a `laravel-cart` table's migration file into `database/migrations` directory. Now all you have to do is run `php artisan migrate` to migrate your database.

### Instances

For using many instances like 'wishlist', 'some-other-items' and etc... Inject in container the CartInstance with params 'instanceName' and 'guardName'

```php

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Instance 'whishlist' with guard 'web'
        $this->app
             ->when(
                [
                    Whishlist\ManageController::class,
                    Whishlist\CheckoutController::class
                ]
             )
             ->needs(CartRepositoryInterface::class)
                ->give(function () {
                    return new CartRepository(new CartInstance('whishlist', 'web'));
                });

        // Instance 'other-cart' with guard 'frontend'
        $this->app
             ->when(OtherCartController::class)
             ->needs(CartInstanceInterface::class)
             ->give(function () {
                return new CartInstance('other-cart', 'frontend');
             });
             
...

```

### Models

All products most implements BuyableInterface


### Example

```php

    class CartController extends Controller
    {
        /**
         * @var CartRepositoryInterface
         */
        private $cartRepository;

        /**
         * CartController constructor.
         *
         * @param CartRepositoryInterface $cartRepository
         */
        public function __construct(CartRepositoryInterface $cartRepository)
        {
            $this->cartRepository = $cartRepository;
        }

        /**
         * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
         */
        public function index()
        {
            $items = $this->cartRepository->getItems();

            return view('cart.index', compact('items'));
        }

        /**
         * @param Product $product
         *
         * @return \Illuminate\Http\RedirectResponse
         */
        public function add(Product $product)
        {
            $qty = 1;
            $this->cartRepository->add($product, $qty);

            return redirect()->route('cart.index');
        }

        /**
        * @return \Illuminate\Http\RedirectResponse
        */
        public function clear()
        {
           $this->cartRepository->clear();

           return redirect()->route('cart.index');
        }

        /**
        * @param string $rowId
        *
        * @return \Illuminate\Http\RedirectResponse
        */
        public function remove(string $rowId)
        {
           if ($cartItem = $this->cartRepository->get($rowId)) {
               $this->cartRepository->remove($cartItem);

               return redirect()->route('cart.index');
           }

           return abort(404);
        }

        /**
        * @param string $rowId
        * @param int    $qty
        *
        * @return \Illuminate\Http\RedirectResponse
        */
        public function updateQty(string $rowId, int $qty)
        {
           if ($cartItem = $this->cartRepository->get($rowId)) {

               $cartItem->setQty($qty);
               $this->cartRepository->update($cartItem);

               return redirect()->route('cart.index');
           }

           return abort(404);
        }
        
        /**
         * @param CartRepositoryInterface $cartRepository
         */
        public function refreshCartItems(CartRepositoryInterface $cartRepository): void
        {
            $shouldRefreshItems = $cartRepository->search(function (CartItem $cartItem) {
                return $this->shouldRefreshCartItem($cartItem);
            });

            $cartRepository->refresh($shouldRefreshItems);
        }
        
        /**
         * @param CartRepositoryInterface $cartRepository
         */
        public function refreshCartItems(CartRepositoryInterface $cartRepository): void
        {
            $shouldRefreshItems   = $cartRepository->getGuestItems();

            $cartRepository->refresh($shouldRefreshItems);
        }
        
        /**
         * @param CartRepositoryInterface $cartRepository
         */
        public function removeOldCartItems(CartRepositoryInterface $cartRepository): void
        {
            $itemsForRemove = $cartRepository->search(function (CartItem $cartItem) {
                return $this->shouldRemoveCartItem($cartItem);
            });

            $cartRepository->removeBatch($itemsForRemove);
        }
        
        /**
         * @param CartItem $cartItem
         *
         * @return bool
         */
        private function shouldRefreshCartItem(CartItem $cartItem): bool
        {
            return $cartItem->updatedAt < $this->expireAt()->subDay();
        }

        /**
         * @param CartItem $cartItem
         *
         * @return bool
         */
        private function shouldRemoveCartItem(CartItem $cartItem): bool
        {
            return $cartItem->addedAt < now()->subDays(5);
        }
    }

```
