<p align="center">
   <img src="https://github.com/VictorWesterlund/reflect/assets/35688133/274464b2-04b4-430f-bf4e-73d77e023bee">
</p>
<h1 align="center">Reflect API Framework</h1>

<p align="center">Reflect is a powerful API framework written in- and for PHP that aims to simplify the development of robust and secure APIs. This framework handles essential components such as authentication, routing, and request validation, allowing developers to focus on building their API endpoints quickly and efficiently.</p>

<h2 align="center">Key Features</h2>

- **Authentication Handling**: Reflect provides built-in support for HTTP Bearer token authentication.
- **Request Validation**: Reflect provides powerful request validation capabilities, ensuring that incoming requests meet your specified criteria. You can define validation rules for request parameters, headers, and body, enhancing the security and reliability of your API.
- **Support for HTTP and UNIX Sockets**: Reflect supports both traditional HTTP requests as well as UNIX sockets, providing flexibility in how you accept and handle incoming requests. This allows you to integrate your API with various systems and technologies.
- **One-to-One File Structure**: The framework follows a one-to-one file structure, where each endpoint has its own folder. This design pattern promotes code organization and makes it easy to locate and maintain specific API functionality.
- **Separate Files for Request Methods**: Reflect encourages storing code for different HTTP methods (GET, POST, PUT, PATCH, DELETE) in separate files. This promotes modularity and allows for easier maintenance and testing.
- **CLI Tool for Efficient Management**: Reflect includes a powerful Command-Line Interface (CLI) tool that streamlines the management of your API. The CLI tool allows you to easily create, update, and manage endpoints, users, API keys, and access rules. With intuitive commands and options, you can quickly configure and customize your API, saving you valuable development time and effort. Whether you need to add new endpoints, create user accounts, generate API keys, or define access permissions, the CLI tool provides a convenient and efficient way to handle these administrative tasks.

*Please note that the CLI tool is an optional component of Reflect, providing a convenient way to manage various aspects of your API. You can choose to use it according to your specific needs and preferences.*

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
