<?php

    require_once Path::reflect("src/database/Auth.php");

    // Client/server connection medium
    enum Connection {
        case AF_UNIX;
        case HTTP;
        case INTERNAL;
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
            // Set HTTP as the default connection type and JSON as
            // the default response Content-Type
            if ($this->con === Connection::HTTP) {
                header("Content-Type: application/json");
            }

            $this->endpoint = $this::get_endpoint();

            // Open connection to AuthDB
            $this->con = $con;
            parent::__construct($this->con);
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

            // Return path as _PascalCaseFromCrumbs (with leading "_")
            return "_" . implode("", $path);
        }

        // Exit with output on a router level. This is used for
        // errors with the request itself or for control requests
        // such as HTTP method "OPTIONS".
        private function exit_here(mixed $msg, int $code = 200) {
            if ($this->con === Connection::INTERNAL) {
                return $msg;
            }

            if ($this->con === Connection::AF_UNIX) {
                return $_ENV["SOCKET_STDOUT"](json_encode($msg), $code);
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
            $test = $this->endpoint;
            // Request URLs starting with "reflect/" are reserved and should read from the internal endpoints located at /src/api/
            $file = substr($this->endpoint, 0, 8) !== "reflect/"
                // User endpoints are kept in folders with 'index.php' as the file to run
                ? Path::root("api/{$this->endpoint}/index.php")
                // Internal endpoints are stored as named files
                : Path::reflect("src/api/{$this->endpoint}.php");

            // Check that the endpoint exists
            if (!file_exists($file)) {
                return $this->exit_here_with_error("No endpoint", 404, "The requested endpoint does not exist");
            }

            // Return available endpoints for API key and exit here
            // if the method is OPTIONS.
            if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
                return $this->exit_here($this->get_options($this->endpoint), 200);
            }

            // Check if user has permission to call this endpoint
            if (!$this->check($this->endpoint, $_SERVER["REQUEST_METHOD"])) {
                return $this->exit_here_with_error("Forbidden", 403, "You do not have permission to call this endpoint. Send OPTIONS request to this endpoint for list of allowed methods");
            }

            // Import endpoint code from file
            require_once $file;

            // Instantiate default endpoint class
            $class = $this->get_endpoint_class();
            if (!class_exists($class)) {
                return $this->exit_here_with_error("Service unavailable", 503, "Endpoint is not configured yet");
            }

            // Initialize API endpoint
            $api = new $class();
            // Pass connection type
            $api->set_connection($this->con);

            // Check input constraints for API before running endpoint method
            if (in_array($_SERVER["REQUEST_METHOD"], ["POST", "PUT", "PATCH"])) {
                // Parse JSON payload from client into superglobal.
                // $_POST will be used for all methods containing a client payload.
                if ($this->con !== Connection::INTERNAL && !empty($_SERVER["HTTP_CONTENT_TYPE"]) && $_SERVER["HTTP_CONTENT_TYPE"] === "application/json") {
                    $_POST = JSON::load("php://input") ?? [];
                }

                $constr = $api->input_constraints();
                if ($constr !== true) {
                    return $this->exit_here_with_error(...$constr);
                }
            }

            // Call method from imported class (_GET(), _POST() etc.)
            $method = "_{$_SERVER["REQUEST_METHOD"]}";
            if (!method_exists($api, $method)) {
                return $this->exit_here_with_error("Method not allowed", 405, "The endpoint does not implement the method sent with your request");
            }

            return $api->$method();
        }
    }