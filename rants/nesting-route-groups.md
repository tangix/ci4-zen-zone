# Nesting route groups may not work as expected

It is possible to nest ``$routes->group()`` such as this

```php
$routes->group('config', function ($routes) {
    $routes->get('/', 'Config::index');
    $routes->group('region', function ($routes) {
        $routes->get('/', 'Region::index');
    });
});
```
to define a route for ``config/region``. This scales well and makes it easy to maintain larger sets of routes by organizing them into groups. 

However, there is a danger here when passing options to the ``group()``. Consider the following code:

```php
$routes->group('config', ['filter' => 'config'], function ($routes) {
    $routes->get('/', 'Config::index');
    $routes->group('region', ['filter' => 'region'], function ($routes) {
        $routes->get('/', 'Region::index');
    });
});
```

In this case the ``config/`` route will be calling the ``config`` filter and ``config/region`` will **only** call the ``region`` filter. I would have expected both filters to be applied, but that is not the case.

To have filters applied globally, define the filers as [global](https://codeigniter4.github.io/userguide/incoming/filters.html#globals).
