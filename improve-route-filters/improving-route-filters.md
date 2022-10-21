# Improving Route Filters definition

The `app/Config/Routes.php` file quickly gets long and hard to get an overview of. I have found one particular issue that easily generates an hour of troubleshooting and head-scratching, namely the definition of Filter arguments. We use Filter arguments to check the user's permissions in the system before the request is routed, for example:

```php
// Routes for handle Session
$routes->options('session/(.+)', 'Options::index');
$routes->get('session', 'Session::index', ['filter' => 'bearer-auth:jwtcommand']);
$routes->get('session/image/(:segment)/(:segment)', 'Session::image/$1/$2', ['filter' => 'bearer-auth:jwtcommand']);
$routes->get('session/attach/(:segment)/(:segment)', 'Session::attach/$1/$2', ['filter' => 'bearer-auth:jwtcommand']);

// Routes for handle Proctor Recents
$routes->options('recents/(.+)', 'Options::index');
$routes->resource('recents', ['controller' => 'Recents', 'filter' => 'bearer-auth:all']);

// Routes for handling Users and Employees
$routes->options('users/(.+)', 'Options::index');
$routes->resource('users', ['controller' => 'Users', 'filter' => 'bearer-auth:user,empl']);
```

We pass the argument to the filter `bearer-auth` (we use Bearer authentication with JWT token) and the argument can be a comma separated list of permissions required for the route, for example `all` or `user,empl` as seen above.

## Adding command and parameter to JWT

Adding new features we wanted to get away from relying on arguments passed in the URL, for example, the session ID is clearly visible in this URL. 

`https://verdandi.tangixdev.se/admin-api/index.php/session/3504567?access_token=<jwt-token>`

We need to open this page in a separate tab to allow printing, so we cannot do a POST or similar to prevent the user from simply changing the session ID (who haven't done that on a website?). Some of the numbers are easy to guess because the are consecutive incrementing numbers and unfortunately we currently have those in the admin section (user-facing parts of the system are using [ULIDs](../rants/please-just-use-ulids.md) instead of numerals).

In the JWT forming the `access_token` we added fields for command and parameter:

```json
{
  "iss": "https://verdandi.tangixdev.se/admin-api",
  "iat": 1666334632,
  "jti": "01GFWMHAVZH5XSAC8ZNPJSC5G0",
  "nbf": 1666334631,
  "exp": 1666370632,
  "access": {
    "comp": 775,
    "empl": 263,
    "user": 16135,
    "gen": 209664,
    "event": 199427,
    "conf": 1249024,
    "rep": 3840
  },
  "jwtcommand": {
    "command": "session",
    "id": "3504567"
  }
}
```

Easy, right? Now the controller simply checks for `jwtcommand` and starts working on the request.

## Problems with Filter arguments

As always when dealing with strings typos may happen. In the route definition:

```php
$routes->get('session', 'Session::index', ['filter' => 'bearer-auth:jwtcommand']);
```

I typed `jtwcommand` instead of `jwtcommand` so an unknown argument was passed and thus nothing worked!

## Enter enum

PHP 8.1 added enumerations to the language which I find is one of the most useful recent additions. Defining all possible permissions as backed enumerations and also adding a convenience function to build the filter definition in `app/Classes/RouteFilter.php`:

```php 
<?php

namespace App\Classes;

enum RouteFilter: string
{
    case Proctor    = 'proctor';
    case JwtCommand = 'jwtcommand';
    case All        = 'all';
    case User       = 'user';
    case Empl       = 'empl';
    case Tangix     = 'tangix';

    static public function build(RouteFilter $f1, RouteFilter $f2 = null) {
        return 'bearer-auth:' . $f1->value . (null !== $f2 ? ',' . $f2->value : '');
    }
}
```

My filter definition in `Routes.php` can now be written as:

```php
// Routes for handle Session
$routes->options('session/(.+)', 'Options::index');
$routes->get(
	'session',
	'Session::index',
	['filter' => RouteFilter::build(RouteFilter::JwtCommand)]
);
$routes->get(
    'session/image/(:segment)/(:segment)',
    'Session::image/$1/$2',
    ['filter' => RouteFilter::build(RouteFilter::JwtCommand)]
);
$routes->get(
    'session/attach/(:segment)/(:segment)',
    'Session::attach/$1/$2',
    ['filter' => RouteFilter::build(RouteFilter::JwtCommand)]
);
```

Now the IDE will help me with auto-complete and refactoring if needed. To further reduce the typing, `use as` can be implemented:

```
<?php

namespace Config;

use App\Classes\RouteFilter as RF;
```

Then `RouteFilter::build(RouteFilter::JwtCommand)` can be written `RF::build(RF::JwtCommand)`.