<?php

    namespace Reflect;

    use \Reflect\ENV;
    use \Reflect\Path;
    use \Reflect\Response;
    use \Reflect\Request\Router;
    use \Reflect\Request\Method;
    use \Reflect\Request\Connection;
    use \Reflect\Helpers\GlobalSnapshot;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/api/builtin/Response.php");
    require_once Path::reflect("src/api/helpers/GlobalSnapshot.php");

    // Call another (internal) endpoint without performing a new HTTP request or socket txn
    function Call(string $endpoint, string|Method $method = null, array $payload = null): Response {
        // Capture a snapshot of the current superglobal state so when we can restore it before returning from this function.
        $snapshot = new GlobalSnapshot();

        // Convert method from verb (or empty) into a Method enum
        if (!($method instanceof Method)) {
            $method = is_string($method)
                // Attempt to resolve method verb into enum
                ? Method::from(strtoupper($method))
                // Carry current method if not specified
                : Method::from($_SERVER["REQUEST_METHOD"]);
        }

        $_SERVER["REQUEST_METHOD"] = $method->value; 

        // Split endpoint string into path and query
        $endpoint = explode("?", $endpoint, 2);
        // Set requested endpoint path with leading slash
        $_SERVER["REQUEST_URI"] = "/" . $endpoint[0];

        // Truncate GET superglobal and repopulate it with values from method call
        $_GET = [];
        if (count($endpoint) == 2) {
            parse_str($endpoint[1], $params);

            foreach ($params as $key => $value) {
                $_GET[$key] = $value;
            }
        }

        // Truncate POST superglobal and repopulate it with values from method call
        $_POST = [];
        if (!empty($payload)) {
            $_SERVER["HTTP_CONTENT_TYPE"] = "application/json";
            
            foreach ($payload as $key => $value) {
                $_POST[$key] = $value;
            }
        }

        // Set flag to let stdout() know that we wish to return instead of exit.
        ENV::set("INTERNAL_STDOUT", true);

        // Start "proxied" Router (internal request)
        $resp = (new Router(Connection::INTERNAL))->main();

        // Restore all superglobals. This will discard any modifications to superglobals prior to method call.
        $snapshot->restore();

        // Return \Reflect\Response object
        return $resp;
    }