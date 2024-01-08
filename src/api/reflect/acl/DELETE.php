<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    use \ReflectRules\Type;
    use \ReflectRules\Rules;
    use \ReflectRules\Ruleset;

    use \Reflect\Database\Acl\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Acl.php");

    class DELETE_ReflectAcl extends Database implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->GET([
                (new Rules("endpoint"))
                    ->required()
                    ->type(Type::STRING)
                    ->max(255),

                (new Rules("method"))
                    ->required()
                    ->type(Type::STRING),

                (new Rules("api_key"))
                    ->required()
                    ->type(Type::STRING)
                    ->max(255)
            ]);
            
            parent::__construct();
        }

        public function main(): Response {
            // Request parameters are invalid, bail out here
            if (!$this->rules->is_valid()) {
                return new Response($this->rules->get_errors(), 422);    
            }
            
            // Build qualified pathname and query from components
            $url = "reflect/acl?endpoint={$_GET["endpoint"]}&method={$_GET["method"]}&api_key={$_GET["api_key"]}";

            // Check if the ACL rule exists
            if (!Call($url, Method::GET)->ok) {
                return new Response("No matching ACL rule was found", 404);
            }

            // Attempt to delete rule from database
            $sql = "DELETE FROM api_acl WHERE endpoint = ? AND method = ? AND api_key = ?";
            $this->exec($sql, [
                $_GET["endpoint"],
                $_GET["method"],
                $_GET["api_key"]
            ]);

            // Run GET requet again to see if the rule has indeed been removed
            return !Call($url, Method::GET)->ok
                ? new Response("OK")
                : new Response("Failed to delete ACL rule", 500);
        }
    }