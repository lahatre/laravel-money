# Lahatre Money

This package provides a precise and developer-friendly way to handle monetary values in Laravel.

It solves the common pitfalls of floating-point arithmetic (like `0.1 + 0.2 !== 0.3`) by using BCMath and integer storage under the hood. It offers a fluent API to perform calculations, handle rounding, and format prices, all while seamlessly integrating with Eloquent.

## Installation

You can install the package via composer:

```bash
composer require lahatre/money
```

## Usage

### Preparing the database

We recommend storing money values as integers (minor units, e.g., cents).

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    // 10.00 is stored as 1000
    $table->bigInteger('price')->default(0); 
    $table->timestamps();
});
```

### Preparing the model

Add the `MoneyCast` to your Eloquent model. This will automatically convert the integer from the database into a `Money` object instance.

```php
use Illuminate\Database\Eloquent\Model;
use Lahatre\Money\Casts\MoneyCast;

class Product extends Model
{
    protected $casts = [
        'price' => MoneyCast::class,
    ];
}
```

### working with Money

You can assign values using strings, integers, or floats. The package will normalize them for you.

```php
$product = new Product();

// You can assign a string (recommended for precision)
$product->price = '19.99'; 

// Or a float
$product->price = 19.99;

// The value is saved as an integer (1999)
$product->save(); 
```

When retrieving the value, you get an immutable `Money` instance.

```php
// Automatic formatting as string
echo $product->price; // "19.99"

// Fluent arithmetic
$newPrice = $product->price->add('5.00'); // "24.99"
```

### Calculations & Rounding

All operations return a new instance of `Money`. By default, the package uses Banker's Rounding (`HALF_UP`) and 2 decimals precision.

```php
$price = Money::from('100.00');

// Chaining
$total = $price
    ->add('50.00')
    ->mul('0.20'); // Returns a new Money instance

// Splitting values (e.g. 10 / 3)
// You can define a specific rounding mode for operations that require it
use Lahatre\Money\Support\BigNumber;

$bill = Money::from('10.00');

$split = $bill->div(3, BigNumber::ROUND_UP); // "3.34"
```

### Configuration

You can publish the config file to change the default precision (e.g. for cryptocurrencies) or the global rounding mode.

```bash
php artisan vendor:publish --tag=money-config
```
