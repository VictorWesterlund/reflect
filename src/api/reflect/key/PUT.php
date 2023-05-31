<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;
    use \Reflect\Database\AuthDB;
    use \Reflect\Request\Connection;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class PUT_ReflectKey extends AuthDB implements Endpoint {
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
                "active"  => [
                    "required" => false,
                    "type"     => "boolean"
                ],
                "expires" => [
                    "required" => false,
                    "type"     => "int",
                    "max"      => PHP_INT_MAX
                ]
            ]);

            parent::__construct(Connection::INTERNAL);
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

            // Filter out values from $_POST that should not go into prepared statement
            $values = array_intersect_key($_POST, array_flip(array_keys($this::POST)));
            // Overwrite existing values in database with values from $_POST
            $values = array_merge($key->output(), $values);

            // Append ID of current key to use as reference in WHERE statement later
            $values[] = $_GET["id"];

            // Execute query with SQL string and the array of values we prepared earlier
            $sql = "UPDATE api_keys SET id = ?, user = ?, active = ?, expires = ?, created = ? WHERE id = ?";
            return $this->return_bool($sql, array_values($values))
                ? new Response("OK")
                : new Response("Failed to update key", 500);
        }
    }