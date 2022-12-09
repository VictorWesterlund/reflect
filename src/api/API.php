<?php

    require_once Path::src("request/Router.php");
    require_once Path::src("database/Idemp.php");
    require_once Path::src("api/helpers/RuleMatcher.php");
    require_once Path::src("api/helpers/GlobalSnapshot.php");

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
        // Returns true if constraints matched or array explaining
        // what went wrong.
        public function input_constraints(): bool|array {
            // A request body is required
            if (empty($_POST)) {
                return ["Payload required", 400, "The request body can not be empty"];
            }

            // Validate and spend idempotency key if enabled for environment
            if (!empty($_ENV["idempotency"])) {
                $idemp = $this->idempotent_ok();

                if (!$idemp) {
                    $key = IdempDb::$key;
                    $msg = $idemp === null ? "Not a valid UUID4 string" : "This key has been used before";

                    return ["Idempotency failed", 409, $msg];
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
                    return ["Unprocessable entity", 422, [
                        "info"   => "The following fields did not meet their requirements",
                        "errors" => $matches
                    ]];
                }

                // Add exception for idempotency key for next operation.
                // This field should be allowed to exist.
                $this::$rules[IdempDb::$key] = null;

                // Restrict PATCH-able columns to the keys defined in ruleset (whitelist)
                foreach (array_keys($_POST) as $field) {
                    // Field is not in whitelist so abort
                    if (!in_array($field, array_keys($this::$rules))) {
                        return ["Unprocessable entity", 422, "Can not process unknown field '${field}'"];
                    }
                }
            }

            return true;
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
            return $this->stdout([
                "error"     => $error,
                "errorCode" => $code,
                "details"   => $msg
            ], $code);
        }

        // Make request to another internal/peer endpoint
        public function call(string $endpoint, Method $method = null, array $payload = null): mixed {
            // Capture a snapshot of the current superglobal state so when
            // we can restore it before returning from this method.
            $snapshot = new GlobalSnapshot();

            // Use request method from argument or carry current method if not provided
            $_SERVER["REQUEST_METHOD"] = !empty($method) ? $method->value : $_SERVER["REQUEST_METHOD"];

            // Split endpoint string into path and query
            $endpoint = explode("?", $endpoint, 2);
            // Set requested endpoint path with leading slash
            $_SERVER["REQUEST_URI"] = "/" . $endpoint[0];

            // Truncate GET superglobal
            $_GET = [];
            // Set GET parameters from query string
            if (count($endpoint) == 2) {
                parse_str($endpoint[1], $params);

                foreach ($params as $key => $value) {
                    $_GET[$key] = $value;
                }
            }

            // Truncate POST superglobal
            $_POST = [];
            // Set POST parameters from payload array
            if (!empty($payload)) {
                $_SERVER["HTTP_CONTENT_TYPE"] = "application/json";
                
                foreach ($payload as $key => $value) {
                    $_POST[$key] = $value;
                }
            }

            // Set flag to let outputting functions know that we wish to return
            // instead of exit.
            $_ENV["INTERNAL_STDOUT"] = true;

            // Start "proxied" Router. Connection type INTERNAL will make its
            // API->stdout() and API->stderr() return instead of exit.
            $resp = (new Router(ConType::INTERNAL))->main();

            // Restore initial superglobals
            $snapshot->restore();

            // Return response as array
            return is_string($resp) ? json_decode($resp, true) : $resp;
        }
    }