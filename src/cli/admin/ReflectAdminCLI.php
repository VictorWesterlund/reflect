<?php

    require_once Path::src("cli/CLI.php");
    require_once Path::src("api/API.php");

    class ReflectAdminCLI extends CLI {
        // All Reflect admin operations require at least 3 arguments
        private static $arglen = 3;

        public function __construct(array $args) {
            parent::__construct();

            // Get all CLI args except name of script file
            array_shift($args);
            $this->args = $args;

            // Pad array of arguments to expected length with nulls
            if (count($this->args) < $this::$arglen) {
                $this->pad_args();
            }

            // Send to handler function
            switch ($this->args[0]) {
                case "endpoint":
                    $this->endpoint();
                    break;

                case "user":
                    $this->user();
                    break;

                default:
                    $this->error("'{$this->args[0]}' is not a valid function");
                    break;
            }
        }

        // Add padding to argument array
        private function pad_args() {
            $arglen = count($this->args);
            $padding = $this::$arglen - $arglen;

            // Fill remaining array slots with nulls
            $this->args = array_merge($this->args, array_fill($arglen, $padding, null));
        }

        // Make an internal API request to reflect endpoints
        private function exec(string $endpoint, Method $method, mixed $payload = null) {
            $api = new API(ContentType::JSON);
            return $api->call(...func_get_args());
        }

        private function endpoint() {
            function endpoint(): string|bool {
                if (empty($this->args[2])) {
                    return false;
                }
    
                // Split endpoint string into crumbs
                $crumbs = explode("/", $this->args[2]);
    
                // Use root name as class name if root endpoint
                if (count($crumbs) < 2) {
                    $crumbs[] = $crumbs[0];
                }
    
                // Capitalize first char of endpoint
                $key = array_key_last($crumbs);
                $crumbs[$key] = ucfirst($crumbs[$key]);
    
                // Reconstruct endpoint name
                return implode("/", $crumbs);
            }

            switch ($this->args[1]) {
                case "list":
                    // Get list of endpoints
                    $endpoints = $this->exec("reflect/Endpoint", Method::GET);
                    $endpoints = array_column($endpoints, "endpoint");

                    // Format list of endpoints
                    $endpoints = array_map(function($endpoint): string {
                        $crumbs = explode("/", $endpoint);

                        // Endpoint is internal
                        if ($crumbs[0] === "reflect") {
                            array_unshift($crumbs, "_");
                        }

                        // Endpoint is root, so display only the first crumb
                        if (count($crumbs) === 2 && $crumbs[0] === strtolower($crumbs[1])) {
                            array_shift($crumbs);
                        }

                        // Make all crumbs lowercase
                        $crumbs = array_map(fn($crumb): string => strtolower($crumb), $crumbs);

                        // Reconstruct crumbs
                        return implode("/", $crumbs);
                    }, $endpoints);

                    // Display the formatted list
                    return $this->list($endpoints);

                case "add":
                    $endpoint = endpoint();
                    if (!$endpoint) {
                        $this->error("Endpoint name can not be empty");
                    }

                    // Check that the endpoint does not already exist.
                    $check = $this->exec("reflect/Endpoint?id=${endpoint}", Method::GET);

                    // We expect a 404 response from the endpoint since we're attemting to
                    // create it. Any other errorCode should be treated as an error.
                    if (!empty($check["errorCode"]) && $check["errorCode"] !== 404) {
                        return $this->error($check);
                    };

                    return $this->echo($this->exec("reflect/Endpoint", Method::POST, [
                        "endpoint" => $endpoint
                    ]));

                case "remove":
                    // Check that the endpoint does not already exist.
                    $check = $this->exec("reflect/Endpoint?id=${endpoint}", Method::GET);

                    // Endpoint does not exist
                    if ($check !== "OK") {
                        return $this->error("Endpoint does not exist");
                    }

                    // Delete endpoint by id
                    return $this->echo($this->exec("reflect/Endpoint?id=${endpoint}", Method::DELETE));

                default:
                    return $this->error("No operation");
            }
        }

        private function user() {
            switch ($this->args[1]) {
                case "list":
                    // Get list of users
                    $endpoints = $this->exec("reflect/User", Method::GET);
                    return $this->list(array_column($endpoints, "id"));

                case "add":
                    if (empty($this->args[2])) {
                        return $this->error("User name can not be empty");
                    }

                    // Check that the user does not already exist.
                    $check = $this->exec("reflect/User?id={$this->args[2]}", Method::GET);

                    // We expect a 404 response from the endpoint since we're attemting to
                    // create it. Any other errorCode should be treated as an error.
                    if (!empty($check["errorCode"]) && $check["errorCode"] !== 404) {
                        return $this->error($check);
                    };

                    return $this->echo($this->exec("reflect/User", Method::POST, [
                        "id"     => $this->args[2],
                        "active" => true
                    ]));

                case "remove":
                    if (empty($this->args[2])) {
                        return $this->error("User name can not be empty");
                    }

                    // Check that the user does not already exist.
                    $check = $this->exec("reflect/User?id={$this->args[2]}", Method::GET);

                    // User does not exist
                    if ($check !== "OK") {
                        return $this->error("User does not exist");
                    }

                    // Delete user by id
                    return $this->echo($this->exec("reflect/User?id={$this->args[2]}", Method::DELETE));

                default:
                    return $this->error("No operation");
            }
        }
    }