<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    use \Reflect\Database\Database;
    use \Reflect\Database\Keys\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Keys.php");

    class PATCH_ReflectKey extends Database implements Endpoint {
        public function __construct() {
            Rules::GET([
                "id" => [
                    "required" => true,
                    "min"      => 1,
                    "max"      => 128
                ]
            ]);

            Rules::POST([
                "id"      => [
                    "required" => false,
                    "type"     => "string",
                    "min"      => 1,
                    "max"      => 128
                ],
                "user"    => [
                    "required" => false,
                    "type"     => "string"
                ],
                "expires" => [
                    "required" => false,
                    "type"     => "int",
                    "max"      => PHP_INT_MAX
                ]
            ]);

            parent::__construct();
        }

        public function main(): Response {
            $update = $this->for(Model::TABLE)
                ->with(Model::values())
                ->where([
                    Model::ID->value       => $_GET["id"],
                    Model::EXPIRES->values => time()
                ])
                ->update(self::filter_columns($_POST, Model::values()));

            //$update = Call("reflect/key?id={$_GET["id"]}", Method::PUT, $_POST);
            return $update ? new Response("OK") : new Response("Failed to update key", 500);
        }
    }