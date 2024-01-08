<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    use \ReflectRules\Type;
    use \ReflectRules\Rules;
    use \ReflectRules\Ruleset;

    use \Reflect\Database\Endpoints\Model;

    require_once Path::reflect("src/database/model/Endpoints.php");

    class DELETE_ReflectEndpoint implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->GET([
                (new Rules("id"))
                    ->required()
                    ->type(Type::STRING)
                    ->max(255)
            ]);
        }

        public function main(): Response {
            // Request parameters are invalid, bail out here
            if (!$this->rules->is_valid()) {
                return new Response($this->rules->get_errors(), 422);    
            }
            
            // Soft-delete endpoint by setting active to false
            $delete = Call("reflect/endpoint?id={$_GET["id"]}", Method::PUT, [
                Model::ACTIVE->value => false
            ]);
            
            return $delete->ok
                ? new Response("OK")
                : new Response(["Failed to delete key", $delete], 500);
        }
    }