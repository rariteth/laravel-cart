
## Installation

Install the package through [Composer](http://getcomposer.org/).

Run the Composer require command from the Terminal:

    composer require rariteth/laravel-cart

If you're using Laravel 5.5, this is all there is to do.

Now you're ready to start using the shoppingcart in your application.

### Configuration
To save cart into the database so you can retrieve it later, the package needs to know which database connection to use and what the name of the table is.
By default the package will use the default database connection and use a table named `laravel-cart`.
If you want to change these options, you'll have to publish the `config` file.

    php artisan vendor:publish --provider="Rariteth\LaravelCart\CartServiceProvider" --tag="laravel-cart-config"

This will give you a `cart.php` config file in which you can make the changes.

To make your life easy, the package also includes a ready to use `migration` which you can publish by running:

    php artisan vendor:publish --provider="Rariteth\LaravelCart\CartServiceProvider" --tag="migrations"

This will place a `laravel-cart` table's migration file into `database/migrations` directory. Now all you have to do is run `php artisan migrate` to migrate your database.
