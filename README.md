<p align="center">
   <img src="https://github.com/VictorWesterlund/reflect/assets/35688133/274464b2-04b4-430f-bf4e-73d77e023bee">
</p>
<h1 align="center">Reflect API Framework</h1>

<p align="center">Reflect is an API framework written in- and for PHP that aims to simplify endpoint development.<br>This framework handles authorization, routing, and request validation.</p>

<h2 align="center">Key Features</h2>

- **ACL Authorization**: Fine-tuned control over endpoint access on a user group and/or method-level. Easier management with an included CLI-tool!
- **Request Validation**: GET and POST parameter validation with [ReflectRules](https://github.com/victorwesterlund/reflect-rules-plugin).
- **Endpoint = Path**: Endpoints follow a one-to-one relationship with the *folder* structure of the server - like an ordinary web server.
- **Separate Files for Request Methods**: In each folder, all HTTP request methods have their own file named after its verb.
- **Internal Request to Peer Endpoints**: Endpoints can internally call each other without leaving the request thread with proxying. The request will appear as any other HTTP request to the receiving endpoint.

This is an example of what the simplest endpoint in Reflect can look like

```
HTTP GET https://api.example.com/foo/bar
```
```php
// File: <endpoints>/foo/bar/GET.php

use Reflect\Endpoint;
use Reflect\Response;

// Handle GET-request related stuff
class GET_FooBar implements Endpoint {

   // Runs before request validation but after request authorization
   public function __construct() {}

   // Runs after request validation, and only if the request is valid
   public function main(): Response {
      return new Response("This is the response body! It can be anything JSON-serializable");
   }

}
```

---

> [!WARNING]
> Technical documentation for Reflect is very incomplete, and user guides essentially missing. I am acutely aware of this and will make an effort to write documentation for this framework.

---

## More examples

### Request input with native superglobals

Reflect exposes incoming GET (query string) and POST parameters as native PHP would through the `$_GET` and `$_POST` superglobal variables. This is true even if the endpoint is being called internally from another endpoint.

> [!TIP]
> In addition to PHP's native decoding of `application/x-www-form-urlencoded` and `multipart/form-data`. Reflect will also decode JSON key-value objects into `$_POST` if the `Content-Type: application/json` request header is set!

```
HTTP POST https://api.example.com/create
Content-Type: application/json

{"foo": "bar"}
```
```php
// File: <endpoints>/create/POST.php

use Reflect\Endpoint;
use Reflect\Response;

// Class names follow the following pattern:
// <REQUEST METHOD IN CAPS>_<PascalCaseOfEndpointPath>
class POST_Create implements Endpoint {

   public function __construct() {}

   public function main(): Response {
      // Pass an HTTP Response code as the second argument, defaults to 200 OK
      return new Response($_POST["foo"], 201);
   }

}
```

### Calling other endpoints internally

Endpoints can call each other internally without creating new HTTP requests. Internal calls are superglobal-proxied ([more info](https://github.com/VictorWesterlund/php-globalsnapshot)) and can be really fast to execute with opcache!

> [!NOTE]
> This demo uses `Reflect\Call` which has no documentation. It follows the same pattern as all reflect clients (listed below). See the [Reflect client for PHP](https://github.com/VictorWesterlund/reflect-client-php) as an example for now

```
HTTP PATCH https://api.example.com/users?id=someone
Content-Type: application/json

{"display_name":"Someone Someoneson"}
```
```php
// File: <endpoints>/user/PATCH.php

use Reflect\Call;
use Reflect\Endpoint;
use Reflect\Response;

class PATCH_Users implements Endpoint {

   public function __construct() {}

   public function main(): Response {
      // Call another Reflect endpoint by pathname, as if it was an HTTP call
      $request = new Call("/users");

      // Associative array of $_GET parameters available to called endpoint
      $request->params([
         "id" => $_GET["id"]
      ]);

      // Returns a Reflect\Response
      $response = $request->get();

      // Return response body as JSON, for example
      $user = $response->ok ? $user->json() : new Response("Response can be called anywhere, even here for example");

      return $this->update_username_by_id($user["id"], $_POST["display_name"])
         ? new Response("Yay")
         : new Response("Nay", 500);
   }

}
```

### Request validation

Incoming requests can be validated by you, or by using this plugin:

[**See ReflectRules for more information**](https://github.com/VictorWesterlund/reflect-rules-plugin)

## Client libraries

Integrate Reflect directly with your program using these pre-built libraries

Language|Install|Repository
---|---|---
PHP|[reflect/client](https://packagist.org/packages/reflect/client) (Packagist)|[victorwesterlund/reflect-client-php](https://github.com/VictorWesterlund/reflect-client-php)
Python|[reflect-client](https://pypi.org/project/reflect-client/) (PyPI)|[victorwesterlund/reflect-client-python](https://github.com/VictorWesterlund/reflect-client-python)
JavaScript|[reflect-client](https://www.npmjs.com/package/reflect-client) (npm)|[victorwesterlund/reflect-client-js](https://github.com/VictorWesterlund/reflect-client-js)
