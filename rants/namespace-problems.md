# Sometimes the namespace will bite you!

Trying to write a modern CI4 project with some of the bells-and-whistles can be hard. Just found out that if you want to have a piece of the `Config`-magic you need to pay attention to the namespace of your files!

Using PHPstorm's *New PHP Class* function to create a new file in `app/Config` will give you a correctly namespaced class in `App\Config`. When trying to set values in `.env` and accessing the configuration using `config()` you will get an error:

```
Fatal error: Cannot declare class App\Config\BackendJwt, because the name is already in use in /Users/.../app/Config/BackendJwt.php on line 0
```

Say what? 

After much debugging and finally reading the [CI4 Documentation](https://codeigniter.com/user_guide/general/configuration.html#creating-configuration-files) I noticed that the namespace should be plain `Config`!

**Problem solved!**