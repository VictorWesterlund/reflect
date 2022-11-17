<?php

    require_once Path::src("api/helpers/RuleMatcher.php");

    // Allowed response Content-Types
    enum ContentType {
        case JSON;
        case Text;
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

        // Request body must match certain requirements since
        // we're changing data now and wish to be more careful.
        private function input_constraints() {
            // A request body is required
            if (empty($_POST)) {
                return $this->stderr("Payload required", 400, "The request body can not be empty");
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

                // Restrict PATCH-able columns to the keys defined in ruleset (whitelist)
                foreach (array_keys($_POST) as $field) {
                    // Field is not in whitelist so abort
                    if (!in_array($field, array_keys($this::$rules))) {
                        return $this->stderr("Unprocessable entity", 422, "Can not process unkown field '${field}'");
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
        public function stderr(string $error, int $code = 500, mixed $msg = null): never {
            $this->stdout([
                "error"     => $error,
                "errorCode" => $code,
                "details"   => $msg
            ], $code);

            die();
        }

        // Make call to an internal peer endpoint. This can be used to
        // chain actions with a pipeline structure.
        // NOTE: This will bypass AuthDB for any endpoint called using
        //       this method. 
        public function call(string $endpoint) {
            // ...
        }
    }