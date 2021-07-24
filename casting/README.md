# Using CodeIgniter's Custom Casting mechanism

Introduced in CodeIgniter 4 is a nice way to create customized casting to the Entity class. This is exactly what I needed in a project where multiple choices from a UI picker is stored in the database as a comma-separated string but the UI picker component required an array with integers.

Database thus holds `"2,5,9"` but the UI picker consumes `[2,5,9]`.

## Docs

Checking the CodeIgniter docs I came across [this](https://codeigniter.com/user_guide/models/entities.html#custom-casting) information about Custom casting.

## Converting data read from the database

I start by extending the `BaseCast` with my own implementation in the class `CastCommaArray`:

```php
<?php

namespace App\Entities\Cast;

class CastCommaArray extends \CodeIgniter\Entity\Cast\BaseCast
{

    public static function get($value, array $params = [])
    {
        $p = explode(',', trim($value));
        if (isset($params[0])) {
            if (is_callable($params[0])) {
                $p = array_map($params[0], $p);
            }
        }
        return $p;
    }

    public static function set($value, array $params = [])
    {
        return join(',', $value);
    }

}
```

The use of `$params` is required since we have some UI picker elements that require the array to contain strings instead of integers, mainly for picking multiple countries where countries are stored with their ISO-code.

To get all my Entities behaving in the same way I have a class `BaseEntity`that I use to extend from. In this base-class I register the Cast Handlers I have created for a couple of special mappings between the database and the UI elements.

```php
<?php

namespace App\Entities;

use App\Entities\Cast\CastBoolean;
use App\Entities\Cast\CastCommaArray;
use App\Entities\Cast\CastPickerAll;

class BaseEntity extends \CodeIgniter\Entity\Entity
{

    protected $castHandlers = [
        'commaarray' => CastCommaArray::class,
        'pickerall'  => CastPickerAll::class,
        'boolean'    => CastBoolean::class
    ];
}
```

With everything name-spaced correctly I can now use the `commaarray` casting in my Entity:

```php
<?php

namespace App\Entities;

class PhantomCreditEntity extends BaseEntity
{

    protected $casts = [
        'phantom_credit_id' => 'int',
        'credit_active'     => 'boolean',
        'cert_access'       => 'commaarray[intval]',
        'max_usage'         => 'int',
        'item_version'      => 'int'
    ];

}
```

In this example, the array should contain integers and I thus pass the extra param `intval` indicating a callable function to `array_map()` in the `get()` function. For the example with country picker, no parameter is passed.

## Saving data to the database

When the front-end UI pass an array to the back-end, the `set()` function is invoked, converting the array to a comma-separated string suitable for storing in the database.