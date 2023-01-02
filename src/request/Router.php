<?php

    require_once Path::src("database/Auth.php");

    // Client/server connection medium
    enum ConType {
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
        public function __construct(private ConType $con) {
            // Set HTTP as the default connection type and JSON as
            // the default response Content-Type
            if ($this->con === ConType::HTTP) {
                header("Content-Type: application/json");
            }

            // Attempt to read from /endpoints if no path provided
            if (empty($_ENV["endpoints"])) {
                $_ENV["endpoints"] = Path::root("endpoints");
            }

            // Endpoints directory is not accessible
            if (!is_dir($_ENV["endpoints"])) {
                $this->exit_here_with_error("Invalid endpoint path", 500, "Invalid path to endpoints");
            }

            // Open connection to AuthDB
            $this->con = $con;
            parent::__construct($this->con);
        }

        // Turn "/path/to/endpoint" into "PathToEndpoint" which will be the 
        // name of the class to instantiate when called.
        private function get_api_class(string $path): string {
            // Create crumbs from path
            $path = explode("/", $path);

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
            if ($this->con === ConType::INTERNAL) {
                return $msg;
            }

            if ($this->con === ConType::AF_UNIX) {
                return $_ENV["SOCKET_STDOUT"](json_encode($msg), $code);
            }

            // For ConType::HTTP
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

        // Get requested endpoint
        private function get_endpoint_path(string $path): string {
            // Deconstruct path into array of crumbs
            $path = parse_url($_SERVER["REQUEST_URI"])["path"];
            $path = explode("/", $path);
            // Strip empty array value from leading "/"
            array_shift($path);

            // Get array length
            $len = count($path);

            // Carry path as file name for root endpoints ("/ping" becomes "/ping/ping")
            if ($len <= 1) {
                $path[] = $path[0];
                $len++;
            }

            // Capitaize last crumb to match file name on disk
            $path[$len - 1] = ucfirst($path[$len - 1]);

            // Return reconstructed path as string
            return implode("/", $path);
        }

        // Call an API endpoint by parsing a RESTful request, checking key
        // permissions against AuthDB, and initializing the endpoint handler class.
        // This is the default request flow.
        public function main() {
            // Strip query string
            $endpoint = $this->get_endpoint_path($_SERVER["REQUEST_URI"]);

            // Check that the endpoint exists.
            // If root endpoint starts with "reflect/" read from the internal_endpoint folder
            // as this is a meta-request for details about the current Reflect instance.
            $path = substr($endpoint, 0, 8) !== "reflect/" 
                ? Path::endpoints("public/${endpoint}.php") 
                : Path::src("api/${endpoint}.php");

            if (!file_exists($path)) {
                return $this->exit_here_with_error("No endpoint", 404, "The requested endpoint does not exist");
            }

            // Return available endpoints for API key and exit here
            // if the method is OPTIONS.
            if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
                return $this->exit_here($this->get_options($endpoint), 200);
            }

            // Check if user has permission to call this endpoint
            if (!$this->check($endpoint, $_SERVER["REQUEST_METHOD"])) {
                return $this->exit_here_with_error("Forbidden", 403, "You do not have permission to call this endpoint. Send OPTIONS request to this endpoint for list of allowed methods");
            }

            // Import endpoint code
            require_once $path;

            // Instantiate default endpoint class
            $class = $this->get_api_class($endpoint);
            if (!class_exists($class)) {
                return $this->exit_here_with_error("Service unavailable", 503, "Endpoint is not configured yet");
            }

            $api = new $class();

            // Check input constraints for API before running endpoint method
            if (in_array($_SERVER["REQUEST_METHOD"], ["POST", "PUT", "PATCH"])) {
                // Parse JSON payload from client into superglobal.
                // $_POST will be used for all methods containing a client payload.
                if ($this->con !== ConType::INTERNAL && !empty($_SERVER["HTTP_CONTENT_TYPE"]) && $_SERVER["HTTP_CONTENT_TYPE"] === "application/json") {
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