<?php

	class MariaDBDriver extends mysqli {
		function __construct(string $database) {
			if (substr($_ENV["mariadb"]["host"], 0, 1) === "/") {
				// Initialize mysqli with unix domain socket
				parent::__construct(
					socket: $_ENV["mariadb"]["host"],
					username: $_ENV["mariadb"]["user"],
					database: $database
				);
			} else {
				// Initialize mysqli with credentials
				parent::__construct(...[
					$_ENV["mariadb"]["host"],
					$_ENV["mariadb"]["user"],
					$_ENV["mariadb"]["pass"],
					$database
				]);
			}
		}

		// Bind SQL statements
		private function bind_params(mysqli_stmt &$stmt, mixed $params) {
			// Make single-value, non-array, param an array with length of 1
			if (gettype($params) !== "array") {
				$params = [$params];
			}

			// Concatenated string with types for each param
			$types = "";

			if (!empty($params)) {
				// Convert PHP primitve to SQL primitive for params
				foreach ($params as $param) {
					switch (gettype($param)) {
						case "integer":
						case "double":
						case "boolean":
							$types .= "i";
							break;

						case "string":
						case "array":
						case "object":
							$types .= "s";
							break;

						default:
							$types .= "b";
							break;
					}
				}

				$stmt->bind_param($types, ...$params);
			}
		}

		// Execute an SQL query with a prepared statement
		private function run_query(string $sql, mixed $params = null): mysqli_result|bool {
			$stmt = $this->prepare($sql);

			// Bind parameters if provided
			if ($params !== null) {
				$this->bind_params($stmt, $params);
			}

			// Execute statement and get affected rows
			$query = $stmt->execute();
			$res = $stmt->get_result();

			// Return true if an INSERT, UPDATE or DELETE was sucessful (no rows returned)
			if (!empty($query) && empty($res)) {
				return true;
			}

			// Return mysqli_result object
			return $res;
		}

		/* ---- */

		// Get result as an associative array
		public function return_array(string $sql, mixed $params = null): array {
			$query = $this->run_query($sql, $params);

			$res = [];
			while ($data = $query->fetch_assoc()) {
				$res[] = $data;
			}

			return $res;
		}

		// Get only whether a query was sucessful or not
		public function return_bool(string $sql, mixed $params = null): bool {
			$query = $this->run_query($sql, $params);

			// Return query if it's already a boolean
			if (gettype($query) === "boolean") {
				return $query;
			}
			
			return $query->num_rows > 0 ? true : false;
		}
	}