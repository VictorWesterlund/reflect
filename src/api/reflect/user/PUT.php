<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Method;

    use \ReflectRules\Type;
    use \ReflectRules\Rules;
    use \ReflectRules\Ruleset;

    use \Reflect\Database\Users\Model;

    require_once Path::reflect("src/database/Auth.php");
    require_once Path::reflect("src/database/model/Users.php");

    class PUT_ReflectUser extends AuthDB implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->GET([
                (new Rules("id"))
                    ->required()
                    ->type(Type::STRING)
                    ->max(128)
            ]);

            $this->rules->POST([
                (new Rules("active"))
                    ->required()
                    ->type(Type::BOOLEAN)
            ]);

            parent::__construct(Connection::INTERNAL);
        }

        public function main(): Response {
            // Update user active state
            $update = $this->for(Model::TABLE)
                ->with(Model::values())
                ->where([
                    Model::ID->value => $_GET["id"]
                ])
                ->update([
                    Model::ACTIVE->value => $_POST["active"]
                ]);
            
            // Return user id if update was successful
            return $update ? new Response($_POST["id"]) : new Response("Failed to update user", 500);
        }
    }