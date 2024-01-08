<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Method;

    use \ReflectRules\Type;
    use \ReflectRules\Rules;
    use \ReflectRules\Ruleset;

    use \Reflect\Database\Database;
    use \Reflect\Database\Acl\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Acl.php");

    class GET_ReflectAcl extends Database implements Endpoint {
        private Ruleset $rules;
        
        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->GET([
                (new Rules("endpoint"))
                    ->type(Type::STRING)
                    ->min(1)
                    ->max(255),

                (new Rules("method"))
                    ->type(Type::STRING)
                    ->min(1),

                (new Rules("api_key"))
                    ->type(Type::STRING)
                    ->min(1)
                    ->max(255)
            ]);

            parent::__construct();
        }

        public function main(): Response {
            // Request parameters are invalid, bail out here
            if (!$this->rules->is_valid()) {
                return new Response($this->rules->get_errors(), 422);    
            }

            $acl = $this->for(Model::TABLE)
                ->with(Model::values())
                // Filter columns based on search parameters provided in request
                ->where(self::filter_columns($_GET, Model::values()))
                ->select(Model::values());

            return $acl->num_rows > 0 
                ? new Response($acl->fetch_all())
                : new Response(["Access denied", "No ACL rules defined for the provided parameters"], 404);
        }
    }