<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    use \ReflectRules\Type;
    use \ReflectRules\Rules;
    use \ReflectRules\Ruleset;

    use \Reflect\Database\Database;
    use \Reflect\Database\Keys\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Keys.php");

    class POST_ReflectKey extends Database implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->POST([
                (new Rules("id"))
                    ->type(Type::STRING)
                    ->min(32)
                    ->max(32),

                (new Rules("user"))
                    ->required()
                    ->type(Type::STRING)
                    ->max(255),

                (new Rules("expires"))
                    ->type(Type::NUMBER)
                    ->max(PHP_INT_MAX)
            ]);
            
            parent::__construct();
        }

        // Derive key from a SHA256 hash of user id and current time if no custom key is provided
        private function derive_key(): string {
            return $_POST["id"] = substr(hash("sha256", implode("", [$_POST["user"], time()])), -32);
        }

        public function main(): Response {
            // Request parameters are invalid, bail out here
            if (!$this->rules->is_valid()) {
                return new Response($this->rules->get_errors(), 422);    
            }

            // Check that the user exists and is active
            $user = Call("reflect/user?id={$_POST["user"]}", Method::GET);
            if (!$user->ok) {
                return new Response(["Failed to create key", "No user with id '{$_POST["user"]}' found"], 404);
            }

            // Generate API key if not provided
            $_POST["id"] = !empty($_POST["id"]) ? $_POST["id"] : $this->derive_key();

            // Attempt to add key
            try {
                $insert = $this->for(Model::TABLE)
                    ->with(Model::values())
                    ->insert([
                        $_POST["id"],
                        // Set key as active
                        true,
                        // Set user id
                        $_POST["user"],
                        // Set expiry timestamp if defined
                        !empty($_POST["expires"]) ? $_POST["expires"] : null,
                        // Set created timestamp
                        time()
                    ]);
            } catch (\mysqli_sql_exception $error) {
                return new Response("Failed to add key", 500);
            }

            // Check if key got added sucessfully
            return Call("reflect/key?id={$_POST["id"]}", Method::GET)->ok
                ? new Response("OK")
                : new Response("Failed to add key", 500);
        }
    }