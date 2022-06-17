# How to mock Models while testing

One Library I am trying to test contains several calls for two Models, `UserModel` and `CompanyModel`. The login in these Models would require an insane number of database tables and records to be created. Now that I want to start testing this Library, what is the best approach?

Introducing **mocks**!

Taking `CompanyModel` as an example. For the purpose of testing the Library, we are only interested in one thing - the country of the `CompanyEntitiy` returned from a `find()`:

```php
 if ($user->companyLink === 0) {
	$country = $user->country;
} else {
	$company = model(CompanyModel::class)
		->find($user->companyLink);
	$country = $company->company_country;
}
```

This we handle using a mocked version of `UserModel`, returning a `CompanyEntitiy` object with the `company_country` property correctly set, but without doing all the database calls:

```php
<?php

namespace Tests\Support\Mocks;

use Tangix\VirtualTester\Entities\CompanyEntity;
use Tangix\VirtualTester\Models\BaseModel;

class CompanyModel extends BaseModel
{

    public function find($id = null)
    {
        $res = new CompanyEntity();
        $res->id = $id;
        $res->company_country = 'SE';

        return $res;
    }
}
```

So far, so good! Next question - how to force the tests to use out mocked version? Easy, in the test definition we add a `setUp()` method calling `injectMock()`:

```php
protected function setUp(): void
{
	parent::setUp();

	$user = model(\Tests\Support\Mocks\UserModel::class);
	\CodeIgniter\Config\Factories::injectMock('models',   	Tangix\VirtualTester\Models\UserModel::class, $user);

	$company = model(\Tests\Support\Mocks\CompanyModel::class);
	\CodeIgniter\Config\Factories::injectMock('models', Tangix\VirtualTester\Models\CompanyModel::class, $company);
}
```

This works well, but with one caveat - creating instances of the model must be done through CodeIgniter's Factories - `$company = model(CompanyModel::class)`, otherwise the mocked class won't be used.