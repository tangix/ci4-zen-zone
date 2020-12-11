>Environment Variables should also be used for anything private such as passwords, API keys, or other sensitive data.

and

>CodeIgniter makes it simple and painless to set Environment Variables by using a “dotenv” file. 

*From [CI4 documentation](https://codeigniter4.github.io/userguide/general/configuration.html).*

# The dangers of the .env file

I recently submitted a [PR](https://github.com/codeigniter4/CodeIgniter4/pull/3955) to CI4 with an documentation update clarifying a subtle (at least to me) danger with ``.env`` files:

**Settings from the ``.env`` files are inserted in your configuration files and available as Environment Variables.**

The **and** is important to digest since this means that *your secrets from ``.env`` are exposed* if you do something like:

* ``var_dump($_ENV)``
* ``phpinfo();``

If you are like me, thinking a ``phpinfo()`` somewhere in your application is convenient, you are in for a nasty surprise when your secret database password is shown under *Environment Variables*! 

Maybe just the info from ``phpinfo(INFO_GENERAL);`` is good enough for debugging purposes?

The PR was approved and a warning is added to the documentation.