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
    use \Reflect\Database\Endpoints\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Endpoints.php");

    class POST_ReflectEndpoint extends Database implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->POST([
                (new Rules("endpoint"))
                    ->required()
                    ->type(Type::STRING)
                    ->max(255),

                (new Rules("active"))
                    ->required()
                    ->type(Type::BOOLEAN)
            ]);
            
            parent::__construct();
        }

        public function main(): Response {
            // Attempt to INSERT new endpoint
            $this->for(Model::TABLE)
                ->with(Model::values())
                ->insert([
                    $_POST["endpoint"],
                    1
                ]);

            // Ensure the endpoint was successfully created
            $created = Call("reflect/endpoint?id={$_POST["endpoint"]}", Method::GET);
            return $created->ok
                ? new Response("OK")
                : new Response(["Failed to create endpoint", $created], 500);
        }
    }