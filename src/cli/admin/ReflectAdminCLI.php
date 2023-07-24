<?php

    namespace Reflect\CLI;

    use \Reflect\Path;
    use \Reflect\CLI\CLI;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    require_once Path::reflect("src/cli/CLI.php");
    require_once Path::reflect("src/request/Router.php");
    
    require_once Path::reflect("src/api/builtin/Call.php");

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
                    $this->error("Not a valid option", "reflect <endpoint/user/key/acl>");
                    break;
            }
        }

        private function endpoint() {
            switch ($this->args[1]) {
                case "list":
                    // Get list of endpoints
                    $endpoints = Call("reflect/endpoint", Method::GET);

                    if (!$endpoints->ok) {
                        return $this->error($endpoints);
                    }

                    $endpoints = array_column($endpoints->output(), "endpoint");

                    // Format list of endpoints
                    $endpoints = array_map(function($endpoint): string {
                        $crumbs = explode("/", $endpoint);

                        // Endpoint is internal
                        if ($crumbs[0] === "reflect") {
                            array_unshift($crumbs, "\e[96msystem:\e[0m ");
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
                    // Next argument should be the endpoint name
                    $endpoint = $this->args[2];

                    if (!$endpoint) {
                        return $this->error("Expected endpoint name", "reflect add <endpoint>");
                    }

                    // Check that the endpoint does not already exist.
                    if (Call("reflect/endpoint?id={$endpoint}", Method::GET)->ok) {
                        return $this->error("Endpoint already exists");
                    }

                    // Attempt to create the endpoint
                    $create = Call("reflect/endpoint", Method::POST, [
                        "endpoint" => $endpoint
                    ]);

                    return $create->ok 
                        ? $this->echo("OK") 
                        : $this->error(["Failed to create endpoint", $create]);

                case "remove":
                    // Next argument should be endpoint name
                    $endpoint =$this->args[2];

                    // Check that the endpoint does not already exist.
                    if (!$endpoint) {
                        return $this->error("Expected endpoint name", "reflect endpoint remove <endpoint>");
                    }

                    // Endpoint doesn't exist
                    if (!Call("reflect/endpoint?id={$endpoint}", Method::GET)->ok) {
                        return $this->error($check);
                    };

                    // Delete endpoint by id
                    return $this->echo(Call("reflect/endpoint?id={$endpoint}", Method::DELETE));

                default:
                    return $this->error("Expected endpoint operation", "reflect endpoint <list/add/remove>");
            }
        }

        private function user() {
            switch ($this->args[1]) {
                case "list":
                    $users = Call("reflect/user?id={$this->args[2]}", Method::GET);
                    return $users->ok 
                        ? $this->list($users->output()) 
                        : $this->error(["Failed to get users", $users]);

                case "add":
                    $name = strtoupper($this->args[2] ?? "");

                    if (empty($name)) {
                        return $this->error("Name can not be empty", "reflect user add <name>");
                    }

                    $user = Call("reflect/user?id={$name}", Method::GET);

                    // Check that the user does not already exist.
                    if ($user->ok && $user->output()["active"]) {
                        return $this->error("User '{$name}' already exists");
                    }

                    // Reactivate previously deactivated user
                    if ($user->ok && $user->output()["active"] !== 1) {
                        $reactivate = Call("reflect/user?id={$this->args[2]}", Method::PUT, [
                            "active" => true
                        ]);
                        return $reactivate->ok 
                            ? $this->echo("OK") 
                            : $this->error(["Failed to reactivate user", $reactivate]);
                    }

                    // Attempt to create the endpoint
                    $create = Call("reflect/user", Method::POST, [
                        "id" => $name
                    ]);

                    return $create->ok 
                        ? $this->echo("OK") 
                        : $this->error(["Failed to add user", $create]);

                case "remove":
                    $name = strtoupper($name ?? "");

                    if (empty($name)) {
                        return $this->error("Name can not be empty", "reflect user remove <name>");
                    }

                    // Check that the user does not already exist.
                    $user = Call("reflect/user?id={$name}", Method::GET);

                    // User does not exist
                    if (!$user->ok) {
                        return $this->error("User '{$name}' does not exist", 404);
                    }

                    // Delete user by id
                    $delete = Call("reflect/user?id={$name}", Method::DELETE);
                    return $delete->ok 
                        ? $this->echo("OK") 
                        : $this->error(["Failed to delete user", $delete]);

                default:
                    return $this->error("Expected user operation", "reflect user <list/add/remove>");
            }
        }

        private function key() {
            switch ($this->args[1]) {
                case "list":
                    $keys = Call("reflect/key?id={$this->args[2]}", Method::GET);
                    return $keys->ok 
                        ? $this->list($keys->output()) 
                        : $this->error(["Failed to get keys", $keys]);

                case "add":
                    if (empty($this->args[2])) {
                        return $this->error("API user can not be empty", "reflect key add <user> [expires] [key]");
                    }

                    // User does not exist
                    $user = Call("reflect/user?id={$this->args[2]}", Method::GET);
                    if (!$user->ok) {
                        return $this->error("No user with id '{$this->args[2]}' could be fond");
                    }

                    // Create or generate API key
                    return $this->echo(Call("reflect/key", Method::POST, [
                        "user" => $this->args[2],
                        // Set expiry date (Unix epoch) if provided
                        "expires"   => !empty($this->args[3]) ? (int) $this->args[3] : null,
                        // Pass user defined API key if provided
                        "id"   => $this->args[4] ?? null
                    ]));

                case "remove":
                    if (empty($this->args[2])) {
                        return $this->error("Key can not be empty");
                    }

                    // Check that the key exists
                    $key = Call("reflect/key?id={$this->args[2]}", Method::GET);
                    if (!$key->ok) {
                        return $this->error("No API key with id '{$this->args[2]}' was found");
                    };

                    // Delete key by id
                    $delete = Call("reflect/key?id={$this->args[2]}", Method::DELETE);
                    return $delete->ok 
                        ? $this->echo("OK") 
                        : $this->error(["Failed to delete key", $delete]);

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

                            return $this->echo(Call("reflect/key?id={$this->args[4]}"), Method::PATCH, [
                                "expires" => $this->args[3]
                            ]);

                        default:
                            return $this->error("Invalid set option");
                    }

                default:
                    return $this->error("Expected key operation", "reflect key <list/add/remove/set>");
            }
        }

        private function acl() {
            switch ($this->args[1]) {
                case "list":
                    // Get all userspace ACL records
                    $acl = Call("reflect/acl", Method::GET);

                    if (!$acl->ok) {
                        return $this->error("Failed to list ACL", $acl->output());
                    }

                    return !empty($acl->output()) 
                        ? $this->list($acl->output()) 
                        : $this->error("No ACL records defined");

                case "grant":
                    if (empty($this->args[2]) || empty($this->args[3]) || empty($this->args[4])) {
                        return $this->error("Expected ACL options", "reflect acl grant <endpoint> <verb> <key>");
                    }

                    $grant = Call("reflect/acl?id={$this->args[2]}", Method::POST, [
                        "endpoint" => $this->args[2],
                        "method"   => $this->args[3],
                        "api_key"  => $this->args[4]
                    ]);
                    return $grant->ok
                        ? $this->echo("OK") 
                        : $this->error($grant->output());

                case "deny":
                    if (empty($this->args[2]) || empty($this->args[3]) || empty($this->args[4])) {
                        return $this->error("Expected ACL options", "reflect acl deny <endpoint> <verb> <key>");
                    }

                    $delete = Call("reflect/acl?endpoint={$this->args[2]}&method={$this->args[3]}&api_key={$this->args[4]}", Method::DELETE);
                    return $delete->ok
                        ? $this->echo("OK") 
                        : $this->error($delete->output());

                default:
                    return $this->error("Expected ACL operation", "reflect acl <list/grant/deny>");
            }
        }
    }