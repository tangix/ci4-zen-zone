# Non-destructive Database testing in CodeIgniter

For our production system we use AWS Aurora MySQL clusters. There is a staging system running in AWS but with slimmed down resources and finally there are the local developer machines. We have tools to copy a snapshot of the production database to the staging system and then download a `mysqldump` to local dev machines. The size of the database is currently approaching 14 GB but due to the snapshot feature in AWS RDS, the process is quite quick.

With an urge to be able to test my Models' logic and Entity cast conversions I set out copy information from the CI docs about how to write tests that utilize the database. **WARNING - Don't to this!** Following the instructions in the CI4 docs I updated `.env` pointing to my local dev database and ran `phpunit` - **BAM!** my database was gone when `phpunit` completed the run. WTF?

Turns out that the default mode of CI's database is to tear-down the database and reset everything when running tests! Clearly a good idea in some cases as you get a fresh, known database for each test - but with a large and complicated database with more than 100 tables, joins sometimes spanning +10 tables etc, etc - setting up a test environment is not feasible. 

After some discussions on the forums and re-reading the docs I found out that there are ways to prevent this. `CIUnitTestCase` has two properties `$migrate` and `$refresh` and there defaults are what bit me, they are both set to `true`!

So to start testing the models, I generated a Base-class for the database tests and put it in `tests/_support/Database/Base.php`, with the only purpose of disabling the database migration and refreshes:

```php
<?php

namespace Tests\Support\Database;

class Base extends \CodeIgniter\Test\CIUnitTestCase
{

    protected $refresh = false;
    protected $migrate = false;

}
```

With the `Base` class I could now go on to create tests for my model, in this case a simple model of modeling User. In `tests/database/UserModelTest.php`:

```php
<?php

final class UserModelTest extends \Tests\Support\Database\Base
{

    public function testModelFind() {
        $u = new \App\Models\UserModel();
        $obj = $u->find(28);

        $this->assertInstanceOf(\App\Entities\UserEntity::class, $obj);
        $this->assertIsInt($obj->userID);
        $this->assertEquals(28, $obj->userID);
    }

}
```

Running this test now works fine and the database is still intact after completion. 

However, make sure you **don't run anything database-related not extending** the `Base` class or you will find your database gone.