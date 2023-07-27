<?php

    namespace Reflect;

    use \Reflect\Response;

    class Rules {
        // String or number can not be shorter/smaller than constraint
        public static function rule_min(string|int|null $value, int $cstr): string|bool {
            if (is_string($value)) {
                return strlen($value) >= $cstr ?: "Length has to exceed {$cstr} characters";
            }

            return $value <= $cstr ?: "Size have to be larger than {$cstr}";
        }

        // String or number can not be longer/larger than constraint
        public static function rule_max(string|int|null $value, int $cstr): string|bool {
            if (is_string($value)) {
                return strlen($value) <= $cstr ?: "Length can not exceed {$cstr} characters";
            }

            return $value <= $cstr ?: "Size can not be larger than {$cstr}";
        }

        // Return true if string matches an arbitrary "type" pattern
        // such as a valid e-mail address for "email" etc.
        public static function rule_type(mixed $value, string $cstr = "text"): string|bool {
            switch ($cstr) {
                // Match a standard non-interal e-mail address
                case "email":
                    $pattern = "/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-z]{2,4}$/";
                    return boolval(preg_match($pattern, $value)) ?: "Not a valid e-mail address";

                // -- Primitives --

                case "boolean":
                case "bool":
                    return is_bool($value) ?: "Must be of type boolean";

                case "object":
                case "array":
                    return is_array($value) ?: "Must be of type array";

                case "integer":
                case "int":
                    return is_int($value) ?: "Must be of type integer";

                case "number":
                case "num":
                    return !is_string($value) && is_numeric($value) ?: "Must be a numeric type";

                case "text":
                default:
                    return is_string($value) ?: "Must be of type string";
            }
        }

        // Return true if field is required and not null
        public static function rule_required(mixed $value, bool $cstr = true): string|bool {
            // Return true if field is not required. It doesn't matter if it's empty
            if ($cstr !== true) {
                return true;
            }

            return $value !== "" ? true : "This field can not be empty";
        }

        /* ---- */

        // Matches field against a rule
        private static function match(string $field, string $rule, mixed $cstr, array $target): string|bool {
            $rule = "rule_" . $rule;

            // Rule does not exist so skip it
            if (!method_exists(__CLASS__, $rule)) {
                return true;
            }

            // Get value from field or empty string if not set in fieldset
            $value = $target[$field] ?? "";

            // Match value and return result
            return self::$rule($value, $cstr);
        }

        private static function match_all(array $all_rules, array &$target): array {
            // Key/value array of nonconforming fields
            $nc = [];

            // Loop over each field
            foreach ($all_rules as $field => $rules) {
                // Ignore invalid fields
                if ($rules === null) {
                    continue;
                }

                // Prepend "required" false rule if not present in rules array
                if (!in_array("required", array_keys($rules))) {
                    $rules["required"] = false;
                }

                // Rule is not required and not present in $target
                if (!isset($target[$field]) && $rules["required"] === false) {
                    // Set to null so endpoints don't have to check for key existance
                    $target[$field] = null;
                    continue;
                }

                // Loop over each rule (and constraint) for field
                foreach ($rules as $rule => $cstr) {
                    $match = self::match($field, $rule, $cstr, $target);

                    // Rule does not match
                    if ($match !== true) {
                        $nc[$field][] = $match;
                    }
                }
            }

            return $nc;
        }

        /* ---- */

        // Enforce GET parameters on search params
        public static function GET(array $rules): bool|Response {
            $errors = self::match_all($rules, $_GET);
            return empty($errors) ?: new Response([
                "Missing search parameters" => "The following search (GET) parameters did not meet their requirements",
                "Errors"                    => $errors
            ], 400);
        }

        // Enforce POST paramters on request body
        public static function POST(array $rules): bool|Response {
            $errors = self::match_all($rules, $_POST);
            return empty($errors) ?: new Response([
                "Missing request body parameters" => "The following request body (POST) parameters did not meet their requirements",
                "Errors"                          => $errors
            ], 422);
        }
    }