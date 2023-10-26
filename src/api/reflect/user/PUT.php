<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Method;
    use \Reflect\Database\AuthDB;
    use \Reflect\Request\Connection;

    use \Reflect\Database\Users\Model;

    require_once Path::reflect("src/database/Auth.php");
    require_once Path::reflect("src/database/model/Users.php");

    class PUT_ReflectUser extends AuthDB implements Endpoint {
        public function __construct() {
            Rules::GET([
                "id" => [
                    "required" => true,
                    "min"      => 1,
                    "max"      => 128
                ]
            ]);

            Rules::POST([
                "active"   => [
                    "required" => true,
                    "type"     => "bool"
                ]
            ]);

            parent::__construct(Connection::INTERNAL);
        }

        public function main(): Response {
            // Update user active state
            $update = $this->for(Model::TABLE)
                -with(Model::values())
                -where([
                    Model::ID->value => $_GET["id"]
                ])
                ->update([
                    Model::ACTIVE => $_POST["active"]
                ]);
            
            return $update ? new Response("OK") : new Response("Failed to update user", 500);
        }
    }