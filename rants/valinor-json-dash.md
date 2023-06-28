# Parsing JSON with dashes in Cuzy/Valinor

While implementing [Google reCaptcha](https://developers.google.com/recaptcha/docs/verify) in a project I came across an issue with Cuzy/Valinor mapper that I struggled to find a solution to. 

Using the `siteverify` API to validate a response to the reCaptcha I get the following structure back:

```json
{
  "success": true|false,
  "challenge_ts": timestamp,
  "hostname": string,
  "error-codes": [...]        // optional
}
```

I now want to map this to a PHP class:

```php
<?php

namespace App\Classes\Json;

class ReCaptchaResponse
{

    public readonly bool $success;
    public readonly ?string $challenge_ts;
    public readonly ?string $hostname;
    public readonly ?array $errorCodes;

}
```

The problem is with the `error-codes` property in the JSON that needs to be mapped to `$errorCodes` property of the PHP class.

In order to solve this, the code to validate must be changed somewhat from the simple (`$request` is the CURLrequest):

```php
$data = (new MapperBuilder())
		->enableFlexibleCasting()
		->allowPermissiveTypes()
		->mapper()
		->map(ReCaptchaResponse::class, new JsonSource($request->getBody()));
```

to

```php
$source = Source::json($request->getBody())->camelCaseKeys();

$data = (new MapperBuilder())
		->enableFlexibleCasting()
		->allowPermissiveTypes()
		->allowSuperfluousKeys()
		->mapper()
		->map(ReCaptchaResponse::class, $source);
```

Notice the use of `$source` here that allows us to call `camelCaseKeys()` forcing the mapping of `error-codes` (and `error codes` and `error_codes`) to `errorCodes` and thus allows us to map it to `$errorCodes`.