<?php

    class SQLiteDriver extends SQLite3 implements DatabaseDriver {
		function __construct(string $db = ":memory:") {
			$this->db_name = strtoupper($db);

			$this->db_path = dirname(__DIR__, 3) . "/storage/database/";

			// Run .sql file on first run of persistant db
			$run_init = false;

			// Set path to persistant db
			if ($this->db_name !== ":memory:") {
				if (!is_writeable($this->db_path)) {
					throw new Error("Permission denied: Can not write to directory '{$this->db_path}'");
				}

				// Create hidden and ignored file (._dbname.db)
				$file = "{$this->db_name}.db";
				$db = $this->db_path . $file;
				
				$run_init = !file_exists($db) ? true : $run_init;
			}
			
			parent::__construct($db);

			if ($run_init) {
				$this->init_db();
			}
		}

		// Execute a prepared statement and SQLite3Result object
		final private function run_query(string $query, mixed $values = []): SQLite3Result {
			$statement = $this->prepare($query);

			// Format optional placeholder "?" with values
			if (!empty($values)) {
				// Move single arguemnt into array
				if (!is_array($values)) {
					$values = [$values];
				}

				foreach ($values as $k => $value) {
					$statement->bindValue($k + 1, $value); // Index starts at 1
				}
			}

			// Return SQLite3Result object
			$query = $statement->execute();
			return $query;
		}

		// Execute SQL from a file
		private function exec_file(string $file): bool {
			$file = $this->db_path . $file;
			$sql = file_get_contents($file);

			return $this->exec($sql);
		}

		/* ---- */

		// Get result as column indexed array
		public function return_array(string $query, mixed $values = []): array {
			$result = $this->run_query($query, $values);
			$rows = [];

			// Get each row from SQLite3Result
			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				$rows[] = $row;
			}

			return $rows;
		}

		// Get only whether a query was sucessful or not
		public function return_bool(string $query, mixed $values = []): bool {
			$result = $this->run_query($query, $values);

			// Get first row or return false
			$row = $result->fetchArray(SQLITE3_NUM);
			return $row !== false ? true : false;
		}

		/* ---- */

		// Initialize a fresh database with SQL from file
		final private function init_db() {
			$file = str_replace(":", "", $this->db_name);
			return $this->exec_file("init/{$file}.sql");
		}
	}