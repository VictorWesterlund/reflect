<?php

    namespace Reflect\CLI;

    use \Reflect\Path;
    use \Reflect\CLI\CLI;
    use \Reflect\Request\Method;

    require_once Path::reflect("src/cli/CLI.php");
    require_once Path::reflect("src/request/Router.php");

    class ReflectAdminCLI extends CLI {
        public function __construct(array $args) {
            parent::__construct($args, 3);

            // Send to handler function
            switch ($this->args[0]) {
                case "endpoint":
                    $this->endpoint();
                    break;

                case "user":
                    $this->user();
                    break;

                case "key":
                    $this->key();
                    break;

                case "acl":
                    $this->acl();
                    break;

                default:
                    $this->error("Not a valid function");
                    break;
            }
        }

        private static function uc_endpoint(string $endpoint): string {
            // Split endpoint string into crumbs
            $crumbs = explode("/", $endpoint);

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

        // Make an internal API request to reflect endpoints
        private function exec(string $endpoint, Method $method, mixed $payload = null) {
            $api = new API(ContentType::JSON);
            return $api->call(...func_get_args());
        }

        private function endpoint() {
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
                    $endpoint = $this::uc_endpoint($this->args[2]);
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

                    // User already exists
                    if (!empty($check[0])) {
                        // Reactivate user if already exists
                        if ($check[0]["active"] === 0) {
                            return $this->echo($this->exec("reflect/Endpoint?id=${endpoint}", Method::PATCH, [
                                "active" => true
                            ]));
                        }

                        return $this->echo("Endpoint already added");
                    }

                    return $this->echo($this->exec("reflect/Endpoint", Method::POST, [
                        "endpoint" => $endpoint
                    ]));

                case "remove":
                    // Check that the endpoint does not already exist.
                    $endpoint = $this::uc_endpoint($this->args[2]);
                    if (!$endpoint) {
                        $this->error("Endpoint name can not be empty");
                    }

                    $check = $this->exec("reflect/Endpoint?id=${endpoint}", Method::GET);

                    // Endpoint does not exist
                    if (!empty($check["errorCode"])) {
                        return $this->error($check);
                    };

                    // Delete endpoint by id
                    return $this->echo($this->exec("reflect/Endpoint?id=${endpoint}", Method::DELETE));

                default:
                    return $this->error("No operation");
            }
        }

        private function user() {
            switch ($this->args[1]) {
                case "list":
                    return $this->list($this->exec("reflect/User?id={$this->args[2]}", Method::GET));

                case "add":
                    if (empty($this->args[2])) {
                        return $this->error("User name can not be empty");
                    }

                    // Check that the user does not already exist.
                    $user = $this->exec("reflect/User?id={$this->args[2]}", Method::GET);

                    // We expect a 404 response from the endpoint since we're attemting to
                    // create it. Any other errorCode should be treated as an error.
                    if (!empty($user["errorCode"]) && $user["errorCode"] !== 404) {
                        return $this->error($user);
                    };

                    // User already exists
                    if (!empty($user[0])) {
                        // Reactivate user if already exists
                        if ($user[0]["active"] === 0) {
                            return $this->echo($this->exec("reflect/User", Method::PUT, [
                                "id"     => $this->args[2],
                                "active" => true
                            ]));
                        }

                        return $this->echo("User already added");
                    }

                    return $this->echo($this->exec("reflect/User", Method::POST, [
                        "id"     => $this->args[2],
                        "active" => true
                    ]));

                case "remove":
                    if (empty($this->args[2])) {
                        return $this->error("User name can not be empty");
                    }

                    // Check that the user does not already exist.
                    $user = $this->exec("reflect/User?id={$this->args[2]}", Method::GET);

                    // User does not exist
                    if (!empty($user[0]["errorCode"]) && $user[0]["errorCode"] !== 404) {
                        return $this->error($user);
                    };

                    // Delete user by id
                    return $this->echo($this->exec("reflect/User?id={$this->args[2]}", Method::DELETE));

                default:
                    return $this->error("No operation");
            }
        }

        private function key() {
            switch ($this->args[1]) {
                case "list":
                    // Get list of users
                    return $this->list($this->exec("reflect/Key?id={$this->args[2]}", Method::GET));

                case "add":
                    if (empty($this->args[2])) {
                        return $this->error("User name can not be empty");
                    }

                    // User does not exist
                    $user = $this->exec("reflect/User?id={$this->args[2]}", Method::GET);
                    if (!empty($user[0]["errorCode"])) {
                        return $this->error($user);
                    };

                    // Requesting to generate or reactivate a key with specific id
                    if (!empty($this->args[3])) {
                        // Check if key key exists
                        $key = $this->exec("reflect/Key?id={$this->args[3]}", Method::GET);
                        if (empty($key["errorCode"])) {
                            // Reactivate key if exists
                            if ($key["active"] === 0) {
                                return $this->echo($this->exec("reflect/Key?id={$this->args[2]}", Method::PATCH, [
                                    "active" => true
                                ]));
                            }
                        }

                        // Add named key
                        return $this->echo($this->exec("reflect/Key", Method::POST, [
                            "id"   => $this->args[3],
                            "user" => $this->args[2]
                        ]));
                    }

                    // Generate a new key for user
                    return $this->echo($this->exec("reflect/Key", Method::POST, [
                        "user" => $this->args[2],
                    ]));

                case "remove":
                    if (empty($this->args[2])) {
                        return $this->error("Key can not be empty");
                    }

                    // Check that the key exists
                    $key = $this->exec("reflect/Key?id={$this->args[2]}", Method::GET);
                    if (!empty($key[0]["errorCode"])) {
                        return $this->error($key);
                    };

                    // Delete key by id
                    return $this->echo($this->exec("reflect/Key?id={$this->args[2]}", Method::DELETE));

                case "set":
                    if (empty($this->args[2])) {
                        return $this->error("Set option can not be empty");
                    }

                    switch ($this->args[2]) {
                        // Set key expiry date
                        case "expires":
                            // UNIX timestamp and targe api key must be provided
                            if (empty($this->args[3]) || empty($this->args[4])) {
                                return $this->error("Expected: <timestamp> <api_key>");
                            }

                            return $this->echo($this->exec("reflect/Key?id={$this->args[4]}"), Method::PATCH, [
                                "expires" => $this->args[3]
                            ]);

                        default:
                            return $this->error("Invalid set option");
                    }

                default:
                    return $this->error("No operation");
            }
        }

        private function acl() {
            switch ($this->args[1]) {
                case "list":
                    // Get all ACL records
                    $endpoints = $this->exec("reflect/Acl", Method::GET);
                    return $this->list($endpoints);

                case "grant":
                case "deny":
                    if (empty($this->args[2]) || empty($this->args[3]) || empty($this->args[4])) {
                        return $this->error("Expected: <endpoint> <verb> <api_key>");
                    }

                    $this->args[2] = $this::uc_endpoint($this->args[2]);

                    // Id is a SHA256 hash of all ACL fields truncated to 32 chars
                    $hash = substr(hash("sha256", implode("", [
                        $this->args[4], // API key
                        $this->args[2], // Endpoint
                        strtoupper($this->args[3]) // Method
                    ])), -32);

                    // Request is to remove an existing ACL record
                    if ($this->args[1] === "deny") {
                        return $this->echo($this->exec("reflect/Acl?id=${hash}", Method::DELETE));
                    }

                    return $this->echo($this->exec("reflect/Acl", Method::POST, [
                        "api_key"  => $this->args[4],
                        "endpoint" => $this->args[2],
                        "method"   => $this->args[3]
                    ]));

                default:
                    return $this->error("No operation");
            }
        }
    }