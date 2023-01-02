# Reflect

Reflect is a framework for building REST APIs over HTTP and UNIX sockets in PHP. 
It handles authentication and request routing so you can put more attention towards building great endpoints.

---

An endpoint in Reflect is a *single PHP file* which contains a class of methods with *semantic naming* for your HTTP methods.

```php
<?php

   require_once Path::api();
   require_once Path::endpoint("controller/MyExternalClass.php");

   class Ping extends API {
      public class __construct() {
         parent::__construct(ContentType::JSON);
      }
      
      public function _GET() {
         return $this->stdout("You said: " . $_GET["ping"]);
      }
      
      public function _PUT() {
         $resp = new MyExternalClass($_POST["foo"]);
         return $this->stdout("You said: " . $resp->out());
      }
   }
   
```

Check out the [Reflect wiki](https://github.com/VictorWesterlund/reflect/wiki) and the [Get Started guide](https://github.com/VictorWesterlund/reflect/wiki/Get-Started) for how to use this framework.

## 📥 Installation

Follow the instructions in the [INSTALL.md](https://github.com/VictorWesterlund/reflect/blob/master/INSTALL.md) file.

## Create your first endpoint

Read the [Get started guide](https://github.com/VictorWesterlund/reflect/wiki/Get-Started) in the Wiki
