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
    use \Reflect\Database\Users\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Users.php");

    class POST_ReflectUser extends Database implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->POST([
                (new Rules("id"))
                    ->required()
                    ->type(Type::STRING)
                    ->max(128)
            ]);
            
            parent::__construct();
        }

        public function main(): Response {
            // Request parameters are invalid, bail out here
            if (!$this->rules->is_valid()) {
                return new Response($this->rules->get_errors(), 422);    
            }

            // Check if user already exists
            $user = Call("reflect/user?id={$_POST["id"]}", Method::GET);
            if ($user->ok) {
                return new Response("User already exists", 409);
            }

            // Attempt to add user
            try {
                $insert = $this->for(Model::TABLE)
                    ->with(Model::values())
                    ->insert([
                        $_POST["id"],
                        true,
                        time()
                    ]);
            } catch (\mysqli_sql_exception $error) {
                return new Response("Failed to add user", 500);
            }

            // Check if user got added sucessfully
            return Call("reflect/user?id={$_POST["id"]}", Method::GET)->ok
                ? new Response("OK")
                : new Response("Failed to add user", 500);
        }
    }