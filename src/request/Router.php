<?php

    namespace Reflect\Request;

    use Reflect\ENV;
    use Reflect\Path;
    use Reflect\Method;
    use Reflect\Response;
    use Reflect\Database\Database;
    use Reflect\Request\Connection;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/request/Connection.php");
    
    // These builtins should be exposed to endpoints in userspace
    require_once Path::reflect("src/api/builtin/Response.php");
    require_once Path::reflect("src/api/builtin/Endpoint.php");
    require_once Path::reflect("src/api/builtin/Method.php");
    require_once Path::reflect("src/api/builtin/Call.php");

    // This is the dynamic request router used to translate a RESTful request into a PHP class. It also checks each
    // request against AuthDB to make sure the provided key has access to the requested endpoint with method.
    class Router extends Database {
        private Method $method;
        private string $endpoint;
        private Connection|null $con;

        // A request initiator must provide the connection typeit wish to use.
        // This allows the router to reply in a correct manner.
        public function __construct(Connection $con) {
            // Parse request method string into Enum
            $this->method = Method::tryFrom($_SERVER["REQUEST_METHOD"]) ?? new Response("Method not allowed", 405);

            $this->endpoint = $this::get_endpoint();

            // Open connection to AuthDB
            $this->con = $con;
            parent::__construct($this->con);

            // Return available endpoints for API key and exit here
            // if the method is OPTIONS.
            if ($this->method === Method::OPTIONS) {
                return new Response($this->get_options($this->endpoint), 200);
            }
        }

        // Polyfill for loading parameters from a JSON request body into $_POST
        private static function load_json_payload() {
            $decode = json_decode(file_get_contents("php://input"), true);
            if (!empty($decode) && !is_array($decode)) {
                return new Response("Failed to decode JSON request body", 422);
            }

            return $_POST = $decode ?? [];
        }

        // Get the requested endpoint from request URL
        private static function get_endpoint(): string {
            // Strip leading slash
            $path = ltrim($_SERVER["REQUEST_URI"], "/");

            // Get only pathname component from request URI
            $path = parse_url($path)["path"];

            return $path;
        }

        // Turn "/path/to/endpoint" into "PathToEndpoint" which will be the name of the class to instantiate when called.
        private function get_endpoint_class(): string {
            // Convert hyphens to slashes
            $path = str_replace("-", "/", $this->endpoint);

            // Create crumbs from path
            $path = explode("/", $path);

            // Make each crumb lowercase so we can strip duplicates. That way we don't en up with silly class names like "OrderOrder" etc.
            $path = array_unique(array_map(fn($crumb) => strtolower($crumb), $path));

            // Capitalize each crumb
            $path = array_map(fn($crumb) => ucfirst($crumb), $path);

            // Return path as METHOD_PascalCaseFromCrumbs
            return implode("_", [$this->method->value, implode("", $path)]);
        }

        // Request URLs starting with "reflect/" are reserved and should read from the internal endpoints located at /src/api/
        private function get_endpoint_file_path(): string {
            // Get internal request prefix string from environment variable
            $internal_prefix = ENV::get(ENV::INTERNAL_REQUEST_PREFIX);

            // Request is to an internal Reflect endpoint
            if (substr($this->endpoint, 0, strlen($internal_prefix)) === $internal_prefix) {
                return Path::reflect("src/api/{$this->endpoint}/{$this->method->value}.php");
            }

            // Return path to endpoint method file from user endpoints
            return Path::root(implode("/", [
                Path::ENDPOINTS_FOLDER,
                $this->endpoint,
                $this->method->value . ".php"
            ]));
        }

        // Call an API endpoint by parsing a RESTful request, checking key permissions against AuthDB,
        // and initializing the endpoint handler class. This is the default request flow.
        public function main(): Response {
            // Resolve endpoint file path from pathname and method
            $file = $this->get_endpoint_file_path();
            // Resolve class name from pathname and method
            $class = $this->get_endpoint_class();

            // Check that the endpoint exists and that the user is allowed to call it
            if (!file_exists($file) || !$this->has_access($this->endpoint, $this->method)) {
                return new Response(["No endpoint", "Endpoint not found or insufficient permissions for the requested method"], 404);
            }

            // Import endpoint code if not already loaded
            if (!class_exists($class)) {
                require_once $file;

                /*
                    Invalid class name if the class doesn't exist after import.
                    The class name should be a PascalCase representation of the endpoint path.
                    Eg. /foo/bar should have a class name FooBar inside a <METHOD>.php file
                */
                if (!class_exists($class)) {
                    return new Response(["Internal Server Error", "Class anchor broken"], 500);
                }
            }

            // Parse JSON payload from client into superglobal.
            // $_POST will be used for all methods containing a client payload.
            if ($this->con !== Connection::INTERNAL && !empty($_SERVER["HTTP_CONTENT_TYPE"]) && $_SERVER["HTTP_CONTENT_TYPE"] === "application/json") {
                $this->load_json_payload();
            }

            // Create instance of endpoint class
            $endpoint = new $class();

            // Run main() method from endpoint class or return No Content respone if the endpoint didn't return
            return $endpoint->main() ?? new Response("", 204);
        }
    }