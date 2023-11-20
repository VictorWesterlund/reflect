<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    use \ReflectRules\Type;
    use \ReflectRules\Rules;
    use \ReflectRules\Ruleset;

    class DELETE_ReflectSessionUser implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->GET([
                (new Rules("id"))
                    ->required()
                    ->type(Type::STRING)
                    ->max(128)
            ]);
        }

        // Attempt to delete current user
        public function main(): Response {
            // Get ID from current user
            $user = Call("reflect/session/user", Method::GET);
            if (!$user->ok) {
                return new Response(["Failed to delete user", $user], 500);
            }

            // Delete current user by ID
            $delete = Call("reflect/user?id={$user->output()}", Method::DELETE);
            return $delete->ok
                ? new Response("OK")
                : new Response(["Failed to delete user", $delete], 500);
        }
    }