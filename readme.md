```php
// ====================================================
// EXEMPLES D'UTILISATION
// ====================================================

/**
 * Migration
 */
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->bigInteger('price')->default(0); // Stocke en centimes
    $table->timestamps();
});

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->bigInteger('balance')->default(0);
    $table->timestamps();
});

/**
 * Models
 */
class Product extends Model
{
    protected $casts = [
        'price' => MoneyCast::class,
    ];
}

class User extends Model
{
    protected $casts = [
        'balance' => MoneyCast::class,
    ];
}

/**
 * Usage - Création et affichage
 */
$product = new Product();
$product->price = Money::from("19.99");
$product->save(); // DB: 1999

echo $product->price; // "19.99"
echo $product->price->format(); // "19.99"

/**
 * Usage - Calculs
 */
$itemPrice = Money::from("19.99");
$quantity = 3;
$subtotal = $itemPrice->mul($quantity); // 59.97

$vat = $subtotal->percentage(20); // 11.99
$total = $subtotal->add($vat); // 71.96

/**
 * Usage - Division avec arrondi
 */
$bill = Money::from("100.00");
$perPerson = $bill->div(3); // 33.33 (ROUND_HALF_UP)

/**
 * Usage - Comparaisons
 */
$user = User::find(1);

if ($user->balance->greaterThanOrEqual(Money::from("50.00"))) {
    $user->balance = $user->balance->sub("50.00");
    $user->save();
}

/**
 * Usage - Gestion des négatifs (si allow_negative=true)
 */
// config/money.php : 'allow_negative' => true

$balance = Money::from("-25.50"); // OK
if ($balance->isNegative()) {
    // Gérer le découvert
}

/**
 * Usage - Intérêts composés
 */
$principal = Money::from("1000.00");
$rate = 5.5; // 5.5% annuel

$interest = $principal->percentage($rate); // 55.00
$newBalance = $principal->add($interest); // 1055.00

/**
 * Configuration multi-devises (même package, config différente)
 */

// Pour JPY (pas de décimales)
// config/money.php : 'precision' => 0
$priceJPY = Money::from("1500"); // 1500¥
$priceJPY->format(); // "1500"

// Pour KWD (3 décimales)
// config/money.php : 'precision' => 3
$priceKWD = Money::from("10.500"); // 10.500 KD
$priceKWD->mul(2); // 21.000 KD
```