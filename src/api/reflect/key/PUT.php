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

    class PUT_ReflectKey extends Database implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->GET([
                (new Rules("id"))
                    ->required()
                    ->max(255)
            ]);

            $this->rules->POST([
                (new Rules("id"))
                    ->required()
                    ->type(Type::STRING),
                
                (new Rules("user"))
                    ->required()
                    ->type(Type::STRING)
                    ->max(128),
                
                (new Rules("active"))
                    ->required()
                    ->type(Type::BOOLEAN),
                
                (new Rules("expires"))
                    ->required()
                    ->type(Type::NUMBER)
                    ->max(PHP_INT_MAX)
            ]);

            parent::__construct();
        }

        // Check if user exists or return true if no user change requested
        private function user_exists(): bool {
            return in_array("user", array_keys($_POST))
                ? Call("reflect/user?id={$_POST["user"]}", Method::GET)->ok
                : true;
        }

        public function main(): Response {
            // Get existing key details
            $key = Call("reflect/key?id={$_GET["id"]}", Method::GET);
            if (!$key->ok) {
                return new Response(["No key", "No API key with id '{$_GET["id"]}' was found"], 404);
            }

            // Check if user exists
            if (!$this->user_exists()) {
                return new Response(["No user with id '{$_POST["user"]}' was found"], 404);
            }

            $update = $this->for(Model::TABLE)
                ->with(Model::values())
                ->where([
                    Model::ID->value => $_GET["id"]
                ])
                ->update(array_values($_POST));

            // Return key id if update was successful
            return $update ? new Response($_POST["id"]) : new Response("Failed to update key", 500);
        }
    }