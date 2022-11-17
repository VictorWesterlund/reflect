<?php

    class Rules {
        // String or number can not be shorter/smaller than constraint
        public static function rule_min(string|int|null $value, int $cstr): string|bool {
            if (is_string($value)) {
                return strlen($value) >= $cstr ?: "Length has to exceed ${cstr} characters";
            }

            return $value <= $cstr ?: "Size have to be larger than ${cstr}";
        }

        // String or number can not be longer/larger than constraint
        public static function rule_max(string|int|null $value, int $cstr): string|bool {
            if (is_string($value)) {
                return strlen($value) <= $cstr ?: "Length can not exceed ${cstr} characters";
            }

            return $value <= $cstr ?: "Size can not be larger than ${cstr}";
        }

        // Return true if string matches an arbitrary "type" pattern
        // such as a valid e-mail address for "email" etc.
        public static function rule_type(string|null $value, string $cstr = "text"): string|bool {
            switch ($cstr) {
                // Match a standard non-interal e-mail address
                case "email":
                    $pattern = "/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-z]{2,4}$/";
                    return boolval(preg_match($pattern, $value)) ?: "Not a valid e-mail address";

                // Free text field
                case "text":
                default:
                    return true;
            }
        }

        // Return true if field is required and not null
        public static function rule_required(string|null $value, bool $cstr = true): string|bool {
            $match = $cstr && !empty($value) ? true : false;
            return $match ?: "This field can not be empty";
        }
    }

    class RuleMatcher extends Rules {
        public function __construct(array &$fields) {
            //parent::__construct();
            $this->fields = $fields;
        }

        // Matches field against a rule
        private function match(string $field, string $rule, mixed $cstr): string|bool {
            $rule = "rule_" . $rule;

            // Rule does not exist so skip it
            if (!method_exists($this, $rule)) {
                return true;
            }

            // Get value from field or empty string if not set in fieldset
            $value = $this->fields[$field] ?? "";

            // Match value and return result
            return $this::$rule($value, $cstr);
        }

        public function match_rules(array $all_rules): array {
            // Key/value array of nonconforming fields
            $nc = [];

            // Loop over each field
            foreach ($all_rules as $field => $rules) {
                // Prepend "required" false rule if not present in rules array
                if (!in_array("required", array_keys($rules))) {
                    $rules["required"] = false;
                }

                // Ignore rules for optional field that has not been set
                if (!isset($this->fields[$field]) && $rules["required"] === false) {
                    continue;
                }

                // Fields that have not been provided in a PATCH request
                // should be ignored. That means "required" becomes optional.
                if ($_SERVER["REQUEST_METHOD"] === "PATCH" && !in_array($field, array_keys($_POST))) {
                    continue;
                }

                // Loop over each rule (and constraint) for field
                foreach ($rules as $rule => $cstr) {
                    $match = $this->match($field, $rule, $cstr);

                    // Rule does not match
                    if ($match !== true) {
                        $nc[$field][] = $match;
                    }
                }
            }

            return $nc;
        }
    }