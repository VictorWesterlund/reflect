<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Method;

    use \ReflectRules\Type;
    use \ReflectRules\Rules;
    use \ReflectRules\Ruleset;

    use \Reflect\Database\Database;
    use \Reflect\Database\Endpoints\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Endpoints.php");

    class PUT_ReflectEndpoint extends Database implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->GET([
                (new Rules("id"))
                    ->required()
                    ->type(Type::STRING)
                    -max(255)
            ]);

            $this->rules->POST([
                (new Rules("active"))
                    ->required()
                    ->type(Type::BOOLEAN)
            ]);

            parent::__construct();
        }

        public function main(): Response {
            $update = $this->for(Model::TABLE)
                ->with(Model::values())
                ->where([
                    Model::ID->value => $_GET["id"]
                ])
                ->update([
                    Model::ACTIVE->value => $_POST["active"]
                ]);
            
            // Return endpoint id if update was successful
            return $update ? new Response($_GET["id"]) : new Response("Failed to update endpoint", 500);
        }
    }