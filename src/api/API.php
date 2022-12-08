<?php

    require_once Path::src("request/Router.php");
    require_once Path::src("database/Idemp.php");
    require_once Path::src("api/helpers/RuleMatcher.php");
    require_once Path::src("api/helpers/InternalStateStack.php");

    // Allowed response Content-Types
    enum ContentType {
        case JSON;
        case Text;
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

    // This class is inherited by all API endpoints and contains
    // some standard bootstrapping functionality.
    class API {
        // An endpoint must specify a valid Content-Type to use
        // the standard outputs of this class.
        public function __construct(private ContentType $type) {
            $this->type = $type;
            
            if (in_array($_SERVER["REQUEST_METHOD"], ["POST", "PUT", "PATCH"])) {
                // Parse JSON payload from client into superglobal.
                // $_POST will be used for all methods containing a client payload.
                if (!empty($_SERVER["HTTP_CONTENT_TYPE"]) && $_SERVER["HTTP_CONTENT_TYPE"] === "application/json") {
                    $_POST = JSON::load("php://input") ?? [];
                }

                $this->input_constraints();
            }
        }

        // Attempt to spend an idempotency key sent by the requester
        private function idempotent_ok(): bool|null {
            // Idempotency key not provided
            if (empty($_POST[IdempDb::$key])) {
                return false;
            }

            return (new IdempDb())->set($_POST[IdempDb::$key]);
        }

        // Request body must match certain requirements since
        // we're changing data now and wish to be more careful.
        private function input_constraints() {
            // A request body is required
            if (empty($_POST)) {
                return $this->stderr("Payload required", 400, "The request body can not be empty");
            }

            // Validate and spend idempotency key if enabled for environment
            if (!empty($_ENV["idempotency"])) {
                $idemp = $this->idempotent_ok();

                if (!$idemp) {
                    $key = IdempDb::$key;
                    $msg = $idemp === null ? "Not a valid UUID4 string" : "This key has been used before";

                    return $this->stderr("Idempotency failed", 409, $msg);
                }
            }

            // The endpoint has defiend input rules so let's
            // make sure all fields match constraints before we proceed.
            // PATCH is not included here since it updates a subset of columns.
            // Validation of these should be performed on an Endpoint level in
            // the "_PATCH()" method.
            if (property_exists($this, "rules")) {
                $matches = (new RuleMatcher($_POST))->match_rules($this::$rules);

                // Post body fields did not satisfy rules emposed by the endpoint
                if (!empty($matches)) {
                    return $this->stderr("Unprocessable entity", 422, [
                        "info"   => "The following fields did not meet their requirements",
                        "errors" => $matches
                    ]);
                }

                // Add exception for idempotency key for next operation.
                // This field should be allowed to exist.
                $this::$rules[IdempDb::$key] = null;

                // Restrict PATCH-able columns to the keys defined in ruleset (whitelist)
                foreach (array_keys($_POST) as $field) {
                    // Field is not in whitelist so abort
                    if (!in_array($field, array_keys($this::$rules))) {
                        return $this->stderr("Unprocessable entity", 422, "Can not process unknown field '${field}'");
                    }
                }
            }
        }

        // Send output to the standard output of the current connection
        // method. It can be either HTTP or through UNIX socket.
        public function stdout(mixed $output, int $code = 200) {
            switch ($this->type) {
                case ContentType::JSON:
                    $pretty = !empty($_GET["pretty"]) ? JSON_PRETTY_PRINT : 0;
                    $output = json_encode($output, $pretty);

                case ContentType::Text:
                    $output = is_string($output) ? $output : json_encode($output);
            }

            // Connection is through internal router
            if (isset($_ENV["INTERNAL_STDOUT"])) {
                return $output;
            }

            // Connection is through socket
            if (isset($_ENV["SOCKET_STDOUT"])) {
                return $_ENV["SOCKET_STDOUT"]($output, $code);
            }

            // Connection is through HTTP
            http_response_code($code);
            echo $output;
        }

        // Send message to standard error output using the normal
        // standard output as the relay.
        public function stderr(string $error, int $code = 500, mixed $msg = null) {
            $this->stdout([
                "error"     => $error,
                "errorCode" => $code,
                "details"   => $msg
            ], $code);
        }

        // Make call to another endpoint without creating a new request.
        // This method will stash the current request state and simulate a
        // new request by overwriting superglobal parameters. The initial request
        // superglobal state will be restored before returning from this method.
        public function call(string $endpoint, Method $method = null, array $payload = null): mixed {
            // Stash the current superglobal values
            $stack = new InternalStateStack();

            // Use request method from argument or carry current method if not provided
            $stack->set(Super::SERVER, "REQUEST_METHOD", !empty($method) ? $method->value : $_SERVER["REQUEST_METHOD"]);

            // Split endpoint string into path and query
            $endpoint = explode("?", $endpoint, 2);

            // Set requested endpoint path with leading slash
            $stack->set(Super::SERVER, "REQUEST_URI", "/" . $endpoint[0]);

            // Set GET parameters from query string
            if (count($endpoint) == 2) {
                parse_str($endpoint[1], $params);

                foreach ($params as $key => $value) {
                    $stack->set(Super::GET, $key, $value);
                }
            }

            // Set POST parameters from payload array
            if (!empty($payload)) {
                $stack->set(Super::SERVER, "Content-Type", "application/json");
                
                foreach ($payload as $key => $value) {
                    $stack->set(Super::POST, $key, $value);
                }
            }

            // Set flag to enable returning for out functions
            $stack->set(Super::ENV, "INTERNAL_STDOUT", Flag::NULL);

            // Start "proxied" Router. Connection type INTERNAL will make its
            // API->stdout() and API->stderr() return instead of exit.
            $resp = json_decode((new Router(ConType::INTERNAL))->main(), true);

            // Restore initial superglobals
            $stack->restore();
            return $output;
        }
    }