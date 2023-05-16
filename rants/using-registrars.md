# Implementing a CodeIgniter Registrar for Database config

Our system consists of multiple CI4 projects, each being a "micro-service" with a limited responsibility. Now where the need came to add yet another one of these micro-services I (once again) found myself copying the logic for the database configuration from `app/Config/Database.php` getting the environment from the Docker container running the server (`getenv()`):

```php
<?php

namespace Config;

class Database extends \CodeIgniter\Database\Config
{

	/**
	 * ... redacted ...
	 */

    public function __construct()
    {
        parent::__construct();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';

            // Under Travis-CI, we can set an ENV var named 'DB_GROUP'
            // so that we can test against multiple databases.
            if ($group = getenv('DB')) {
                if (is_file(TESTPATH . 'travis/Database.php')) {
                    require TESTPATH . 'travis/Database.php';

                    if (! empty($dbconfig) && array_key_exists($group, $dbconfig)) {
                        $this->tests = $dbconfig[$group];
                    }
                }
            }
        } else {
            $this->default['hostname'] = getenv('CONFIG_RDS_RW');
            $this->default['password'] = getenv('CONFIG_RDS_PASSWORD');
            $this->default['username'] = getenv('CONFIG_RDS_USERNAME');
            $this->default['database'] = getenv('CONFIG_RDS_DATABASE');

            // Set up the Read-Only database connection
            $this->readonly             = $this->default;
            $this->readonly['hostname'] = getenv('CONFIG_RDS_RW');
        }
    }
}
```

We already have a project-wide composer package `tangix/virtualtester` with many generic functions, models and libraries that we use across the projects. **Why not adding Database configuration to this?**

## Creating a Registrar

Simply creating `src/Config/Registrat.php` in the *composer package* (namespace `Tangix\VirtualTester`) with the logic needed:

```php
<?php

namespace Tangix\VirtualTester\Config;

class Registrar
{
    public static function Database(): array
    {

        $default = [
            'DSN'      => '',
            'hostname' => 'localhost',
            'username' => '',
            'password' => '',
            'database' => '',
            'DBDriver' => 'MySQLi',
            'DBPrefix' => '',
            'pConnect' => true,
            'DBDebug'  => (ENVIRONMENT !== 'production'),
            'cacheOn'  => true,
            'cacheDir' => '',
            'charset'  => 'utf8',
            'DBCollat' => 'utf8_general_ci',
            'swapPre'  => '',
            'encrypt'  => false,
            'compress' => false,
            'strictOn' => false,
            'failover' => [],
            'port'     => 3306,
        ];

        $default['hostname'] = getenv('CONFIG_RDS_RW');
        $default['password'] = getenv('CONFIG_RDS_PASSWORD');
        $default['username'] = getenv('CONFIG_RDS_USERNAME');
        $default['database'] = getenv('CONFIG_RDS_DATABASE');

        // Set up the Read-Only database connection
        $readonly             = $default;
        $readonly['hostname'] = getenv('CONFIG_RDS_RW');

        return [
            'default' => $default,
            'readonly' => $readonly
        ];
    }
}
```

Nothing is now needed to be changed in the micro-services. Also, no conflicts with existing code as the Registrar is called at `parent::__construct()` in `app/Config/Database.php`.

## Lessons learned

**Note to self:** Remember to actually try to connect to the database using `db_connect()`, otherwise the Registrar will not run...