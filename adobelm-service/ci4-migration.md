# Migrating a project from CI3 to CI4

It's that time of the year - doing stuff that should have been done a long time ago before wrapping up and winding down for the holidays.

Today I started a migration project of one of the components in the system - the Adobe Learning Manager (formerly Captivate Prime) integration. This is a fairly simple part of the system handling communication with the Adobe LM API to get the users' achievements and learning badges.

Most of the project is plain vanilla PHP parsing JSON-formatted responses and preparing requests. AWS Simple Queue Service ("SQS") is in use to manage tasks between the multiple instances and the rest of the system. Most of the database calls are done with the query builder in CI3.

The project is a long-running CLI, polling SQS for messages and then acting on them. AWS FARGATE manage the fleet of containers running the system and re-launching if a task dies.

## Name-spacing

The CI3-version of the project relies on libraries loaded through `config.php` using auto-loader:

```
$autoload['libraries'] = array('tgixconstants', 'tgixmailer', 'database', 'cwlogs', 'sqs', 'captivate');
```

Since name-spacing is not strictly required for CI3, the old project didn't have any and instead some `if (!class_exists())` calls for defining helping classes within libraries. This is changed with a `app/Classes` approach and properly name-spaced classes.

Another goal with the migration is to improve handling of errors related to expired authentication tokens for the API. With the current library approach, the library is initialized once so replacing the authentication tokens is problematic and something that needs to be fixed. In the old implementation, the worker task simply died when encountering an error forcing FARGATE to re-launch and then a token exchange was made.

## Porting Config-files

The old project contains many worst-practices when it comes to handling configurations. Some due to laziness and some due to missing functionality in CI3. In CI3, all configuration is inside the same `$this->config` variable. 

Porting to CI4-style we instead use separate files and `$config = config('Aws');` to get the values. In CI4 we also use the `.env`-file to set configuration values during development and deployment.

## Porting CURL calls

As CI3 did not have a framework-native CURL class, the external library `php-curl-class/php-curl-class` was used as a wrapper. This library is well-maintained so porting was simply to replace calls to `Curl()` constructor with a properly name-spaced version. The rest of the code was unchanged.

## Using Services where possible

Instead of loading libraries, the CI4 version now use [Services](https://codeigniter.com/user_guide/concepts/services.html?highlight=services). This will fix the issue of the current implementation when authentication tokens expire - we will get a fresh object each time calling `Services::learningmanager()` by passing `false` to the `$getShared` parameter.

All libraries are ported to be used as a service.

## Porting database queries

Much of the old code is fairly straight-forward to port. In general the database calls are created with the Query Builder from CI3 and look like this:

```php
	// Check to see if the user exists
	$this->db->select('*');
	$this->db->where('id', $id);

	/** @var CI_DB_result $query */
	$query = $this->db->get(TBL_CAPTIVATE_USERS);

	if ($query->num_rows() == 0)
	{
		$query->free_result();
		return FALSE;
	}

	$captivate_user = $query->row_object(0);
	$query->free_result();
```

Using the Query Builder and fluent interface of CI4, this translates to:

```php
	$db = db_connect();
	// Check to see if the user exists
	$captivate_user = $db->table(Table::captivate_users)
		->select('*')
		->where('id', $id)
		->get()
		->getRow(0);

	if (is_null($captivate_user)) {
		return false;
	}
```

The `$query->num_rows()` from CI3 lacks a proper implementation in CI4 where the query object returned must be used in a `foreach()`-loop or similar. If only one record is expected an `is_null()` works fine.

Of course a model would have been best-practice implementation.

## Running as CLI Commands

Instead of running through the router as in the old version of the project, porting to the CLI approach of CI4 is made. Commands are running through `spark` from the command line.

## Conclusions

As the project mainly contained plain PHP code, porting was relatively straight-forward. After setting up name-space, getting everything properly loaded is easy through `composer`. 

Porting the database calls was a repetitive chore but straight-forward. 

Changing to CI4-style `Config` gives better flexibility when handling dev and production environments and easily integrates with the AWS FARGATE using `$_ENV[]`.