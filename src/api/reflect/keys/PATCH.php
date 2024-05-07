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

    class PATCH_ReflectKey extends Database implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->GET([
                (new Rules("id"))
                    ->required()
                    ->type(Type::STRING)
                    ->max(255)
            ]);

            $this->rules->POST([
                (new Rules("id"))
                    ->type(Type::STRING)
                    ->max(255),

                (new Rules("user"))
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

            $update = $this->for(Model::TABLE)
                ->with(Model::values())
                ->where([
                    Model::ID->value => $_GET["id"]
                ])
                ->update(self::filter_columns($_POST, Model::values()));

            // Use new id from POST if changed, else use existing id
            $id = $_POST["id"] ? $_POST["id"] : $_GET["id"];

            // Return key if update was successful
            return $update && $this->affected_rows === 1 ? new Response($id) : new Response("Failed to update key", 500);
        }
    }