# Please just use ULIDs!

Choose `01GFTKF87ZR971SWM7FVA41G1C` instead of `b777e71e-2541-4966-82e9-b1e114c66cb0`!

If you need to generate unique IDs for something, save yourself tons of problems and just start using ULID:s instead of UUIDs. ULIDs sort better as they are built with a timestamp always increasing. They are compact and don't have the line-breaking dashes. 

An excellent [PHP implementation](https://github.com/robinvdvleuten/php-ulid) with permissive MIT License is available through composer:

```
composer require robinvdvleuten/ulid
```
