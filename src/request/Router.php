<?php

    namespace Reflect\Request;

    use const \Reflect\ENV;
    use \Reflect\Path;
    use \Reflect\Response;
    use \Reflect\Database\AuthDB;
    use \Reflect\Database\IdempDB;

    require_once Path::reflect("src/database/Auth.php");
    require_once Path::reflect("src/database/Idemp.php");

    // These builtins should be exposed to endpoints in userspace
    require_once Path::reflect("src/api/builtin/Response.php");
    require_once Path::reflect("src/api/builtin/Call.php");

    // Client/server connection medium
    enum Connection {
        case AF_UNIX;
        case HTTP;
        case INTERNAL;
    }

    // Allowed HTTP verbs
    enum Method: string {
        case GET     = "GET";
        case POST    = "POST";
        case PUT     = "PUT";
        case DELETE  = "DELETE";
        case PATCH   = "PATCH";
        case OPTIONS = "OPTIONS";
    }

    // This is the dynamic request router used to translate a
    // RESTful request into a PHP class. It also checks each
    // request against AuthDB to make sure the provided key has
    // access to the requested endpoint with method.
    class Router extends AuthDB {
        // A request initiator must provide the connection type
        // it wish to use. This allows the router to reply in a
        // correct manner.
        public function __construct(private Connection $con) {
            // Parse request method string into Enum
            $this->method = Method::tryFrom($_SERVER["REQUEST_METHOD"]) ?? $this->exit_here("Method not allowed", 405);

            $this->endpoint = $this::get_endpoint();

            // Open connection to AuthDB
            $this->con = $con;
            parent::__construct($this->con);
        }

        // Polyfill for loading parameters from a JSON request body into $_POST
        private static function load_json_payload() {
            return $_POST = json_decode(file_get_contents("php://input"), true) ?? [];
        }

        // Get the requested endpoint from request URL
        private static function get_endpoint(): string {
            // Get only pathname component from request URI
            $path = parse_url($_SERVER["REQUEST_URI"])["path"];
            // Strip leading slash
            $path = ltrim($path, "/");

            return $path;
        }

        // Turn "/path/to/endpoint" into "PathToEndpoint" which will be the 
        // name of the class to instantiate when called.
        private function get_endpoint_class(): string {
            // Create crumbs from path
            $path = explode("/", $this->endpoint);

            // Make each crumb lowercase so we can strip duplicates. That way
            // we don't en up with silly class names like "OrderOrder" etc.
            $path = array_unique(array_map(fn($crumb) => strtolower($crumb), $path));

            // Capitalize each crumb
            $path = array_map(fn($crumb) => ucfirst($crumb), $path);

            // Return path as PascalCaseFromCrumbs
            return implode("", $path);
        }

        // Exit with output on a router level. This is used for
        // errors with the request itself or for control requests
        // such as HTTP method "OPTIONS".
        private function exit_here(mixed $msg, int $code = 200) {
            if ($this->con === Connection::INTERNAL) {
                return $msg;
            }

            if ($this->con === Connection::AF_UNIX) {
                return $_ENV[ENV]["SOCKET_STDOUT"](json_encode($msg), $code);
            }

            // For Connection::HTTP
            http_response_code($code);
            exit(json_encode($msg));
        }

        // Wrapper for exit_here() but sets some standard error
        // formatting before sent to output.
        private function exit_here_with_error(string $error, int $code = 500, mixed $msg = null) {
            return $this->exit_here([
                "error"     => $error,
                "errorCode" => $code,
                "details"   => $msg
            ], $code);
        }

        // Call an API endpoint by parsing a RESTful request, checking key
        // permissions against AuthDB, and initializing the endpoint handler class.
        // This is the default request flow.
        public function main() {
            // Request URLs starting with "reflect/" are reserved and should read from the internal endpoints located at /src/api/
            $file = substr($this->endpoint, 0, 8) !== "reflect/"
                // User endpoints are kept in folders with 'index.php' as the file to run
                ? Path::root("endpoints/{$this->endpoint}/{$this->method->value}.php")
                // Internal endpoints are stored as named files
                : Path::reflect("src/api/{$this->endpoint}/{$this->method->value}.php");

            // Return available endpoints for API key and exit here
            // if the method is OPTIONS.
            if ($this->method === Method::OPTIONS) {
                return new Response($this->get_options($this->endpoint), 200);
            }

            // Check that the endpoint exists and that the user is allowed to call it
            if (!file_exists($file) || !$this->check($this->endpoint, $this->method)) {
                return new Response(["No endpoint", "Endpoint not found or insufficient permissions for the requested method."], 404);
            }

            // Import endpoint code from file
            require_once $file;

            // Instantiate default endpoint class
            $class = $this->get_endpoint_class();
            if (!class_exists($class)) {
                // Return 503 if the class name of the requested endpoint does not match the path.
                // Eg. endpoint '/foo/bar' should have a class with the name 'FooBar' inside a <METHOD>.php file
                return new Response(["Service unavailable", "Endpoint is not configured yet."], 503);
            }

            // Parse JSON payload from client into superglobal.
            // $_POST will be used for all methods containing a client payload.
            if ($this->con !== Connection::INTERNAL && !empty($_SERVER["HTTP_CONTENT_TYPE"]) && $_SERVER["HTTP_CONTENT_TYPE"] === "application/json") {
                $this->load_json_payload();
            }

            // Run main() method from endpoint class
            return (new $class())->main();
        }
    }