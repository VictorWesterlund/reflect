<?php

    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    use \ReflectRules\Type;
    use \ReflectRules\Rules;
    use \ReflectRules\Ruleset;

    class PATCH_ReflectUser implements Endpoint {
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
        }

        public function main(): Response {
            // Alias for PUT
            return Call("reflect/user?id={$_GET["id"]}", Method::PUT, $_POST);
        }
    }