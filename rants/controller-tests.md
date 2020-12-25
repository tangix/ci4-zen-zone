# Writing Controller Tests

Starting to explore how to write tests for a Controller. I have updated the Apiary-Logger with the 
first Controller tests and achieved 100% code-coverage.

However, there are some funny things with the CI4 Controller testing that makes me a bit
confused. 

## How to call the Controller using the defined routes?

The example code from the docs about passing a Request with ``withRequest()`` seems to be badly 
broken. The suggested syntax doesn't even work due to the method signature of ``IncomingRequest``. I reverted to using ``execute()`` but that seems to skip the request
routing entirely.

There is the ``FeatureTestTrait`` but I don't see a way to pass JSON body data to the request. 
Since the Apiary Logger front-end uses JSON, I maybe get around to add this and submit a PR.

## How to I make sure the Routes are configured correctly?

Checking the defined routes I see that the namespace presented is different from what I would 
expect. The following code works, but is ... strange ...

```php
public function testRoutes() {
    $routes = Services::routes(true);

    // The namespace is different here, so we add a \
    $this->assertEquals("\\" . Queens::class . '::index', $routes->getRoutes('get')['queens']);
}
```

I guess I will return to this topic as I create more tests.
