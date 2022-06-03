# Migrating to PHP 8.1

The migration of several CI3 and CI4 projects to PHP 8.1 is almost complete. All projects were running PHP 7.4.x before and the decision was made to skip 8.0 and move directly to 8.1. 

Will also start using PHPstan to analyze the code.

Some thoughts so far.

## Problems encountered

### Null arguments to string functions generates warnings

The change to many string-functions to not accept null took lots of time to complete. Using constructs such as `strlen($row['field'])` where `$row['field']` comes from a database allowing NULL generates warnings and problems. Casting of the value from the database has been tedious.

### Missing mcrypt support

Removal of `mycrypt_*` in 8.0 was no surprise and a long overdue migration to [phpseclib](https://phpseclib.com/) had to be done. We are using Twofish encryption when communicating using a legacy API and luckily it is supported. All other encryptions are made with `openssl_*` functions.

This had the side-effect with [TCPDF](https://github.com/tecnickcom/TCPDF) all of a sudden running terribly slow, requiring ~15 seconds to create a PDF. Checking the code I saw that the basic encryption was RC4 and with `mcrypt_*` gone, this encryption was made using native PHP code. Updating to AES-128 encryption (and thus creating files requiring Acrobat 7.0 or later) solved this issue.

## Features I like

### Nullsafe operator

```php
$eventLink = db_connect()
            ->table(Table::cert_regs)
            ->select('eventLink')
            ->where('certregID', $certregID)
            ->get()
            ->getRowObject()?->eventLink;
```

Introduced in 8.0, the `?->` operator really makes the code more compact, specially when running database queries. 

### Constructor property promotion

```php
<?php
class Point {
    public function __construct(protected int $x, protected int $y = 0) {
    }
}
```

So much boiler-plate code I no longer have to write! 

### Getting class of an object

The addition of `$obj::class` is nice.

### Named arguments

On the fence of this one. I'm not sure I like 
