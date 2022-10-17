# Making JSON parsing more strict

Our front-end is sending JSON formatted data to the backend. We know what attributes we require so it would be easy doing something along the lines:

```php
$json = $this->request->getJSON();
$attr = ['firstname', 'lastname', 'email', 'country'];
foreach ($attr as $a) {
	if (!property_exists($json, $a)) {
		throw new RequestExecption::forArgumentError();
	}
}
```

Cute, but what do we do about the rest of code accessing the data. How would we avoid typos and allow a static analyzer to work the code?

Enter [Valinor library](https://github.com/CuyZ/Valinor) - PHP object mapper with strong type support!

Using Valinor, we gain three benefits: 

1. We make sure the JSON is properly formatted and contains the correct information
2. We allow code insight in our IDE and static analyzers to check the code
3. We introduce Value Objects instead of a `stdClass` objects with lax control

## Creating a Value Object

Our code is expecting a new user JSON POST. We define that POST as a Value Object where we configure the properties:

```php
<?php

namespace App\Classes\Json;

final class NewUser
{

    public readonly string $firstname;
    public readonly string $lastname;
    public readonly string $email;
    public readonly string $country;
    public readonly ?string $password;

}
```

Using Valinor we can now process the request in this way:

```php
public function new()
{
	try {
		/** @var NewUser $data */
		$data = (new MapperBuilder())->mapper()
			->map(NewUser::class, new JsonSource($this->request->getBody()));
	} catch (MappingError $error) {
		throw RequestException::forArgumentError();
	}
}
```

We simply take the body of the request and let Valinor try to map everything to the `NewUser` class. If this fails, an exception is thrown and if it succeeds we get an instance of class `NewUser` that we can safely use with class properties that are defined and understood by the IDE:

```php
$id = UserModel::factory()
	->insert([
				'firstname' => $data->firstname,
				'lastname' => $data->lastname,
				'email' => $data->email,
				'country' => $data->country
			]);
```

Cool, eh?