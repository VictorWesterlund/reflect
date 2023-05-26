<p align="center">
   <img src="https://github.com/VictorWesterlund/reflect/assets/35688133/274464b2-04b4-430f-bf4e-73d77e023bee">
</p>
<h1 align="center">Reflect</h1>

<p align="center">Reflect is a framework for building REST APIs over HTTP and UNIX sockets in PHP. 
It handles authentication and request routing so you can put more attention towards building great endpoints.</p>

---

```php
// URL:  https://localhost/foo/bar?foo=bar
// File: /endpoints/foo/bar/GET.php

use \Reflect\Endpoint;
use \Reflect\Response;

class GET_FooBar implements Endpoint {

   public function __construct() {
      $this->foo = "bar";
   }
   
   public function main(): Response {
      if ($_GET["foo"] === $this->foo) {
         return new Response("Foo is a bar!");
      }
      
      return new Response("Foo is not a bar", 400);
   }
   
}
```
*Reflect uses PHP's superglobals. $_GET for search parameters, $_POST for request body data (even JSON)*

```php
// URL:  https://localhost/foo/bar?foo=bar
// File: /endpoints/foo/bar/PUT.php

use \Reflect\Path;
use \Reflect\Endpoint;
use \Reflect\Response;
use function \Reflect\Call;

// Include other [relative] files (yes, even PHP scripts)
require_once Path::root("/MyDatabase.php");

class POST_FooBar extends MyDatabase implements Endpoint {
   
   /*
      The optional constants GET and POST contain rules that will be enforced on the requester before
      the `main()` method is called. Read more on the wiki. 
      https://github.com/victorwesterlund/reflect/wiki/rules
   */
   
   const GET = [
      "foo" => [
         "required" => true,
         "type"     => "string"
      ]
   ];
   
   const POST = [
      "example_uuid" => [
         "required" => true,
         "type"     => "string",
         "min"      => 32,
         "max"      => 32
      ]
   ];

   public function __construct() {
      parent::__construct("mydatabase");
   }
   
   private function insert_uuid(): bool {
      return $this->do_some_db_stuff($_POST["example_uuid"]);
   }
   
   public function main(): Response {
      // Call another Reflect endpoint without generating a new request with \Reflect\Call
      // This function returns a \Reflect\Response object.
      $valid = Call("/foo/bar?foo={$_GET["foo"]}", "GET");
      
      if (!$valid->ok) {
         return new Response("GET told me that 'foo' is not valid", 400);
      }
      
      return $this->insert_uuid() 
         ? new Response("Nice!", 201)
         : new Response(["Oh no..", $example_insert_db], 500);
   }
   
}
```

Check out the [Reflect wiki](https://github.com/VictorWesterlund/reflect/wiki) and the [Get Started guide](https://github.com/VictorWesterlund/reflect/wiki/Get-Started) for how to use this framework.

# Installation

[See INSTALL.md for installation instructions](https://github.com/VictorWesterlund/reflect/blob/master/INSTALL.md)

# Get started

Read the [Get started guide](https://github.com/VictorWesterlund/reflect/wiki/Get-Started) in the Wiki

## Client libraries

Integrate Reflect directly with your program using these pre-built libraries

Language|Install|Repository
---|---|---
PHP|[reflect/client](https://packagist.org/packages/reflect/client) (Packagist)|[victorwesterlund/reflect-client-php](https://github.com/VictorWesterlund/reflect-client-php)
Python|[reflect-client](https://pypi.org/project/reflect-client/) (PyPI)|[victorwesterlund/reflect-client-python](https://github.com/VictorWesterlund/reflect-client-python)
