<?php

require_once __DIR__ . '/../debug.php';

if (!function_exists('str_contains')) {
	function str_contains($haystack, $needle) {
		return $needle !== '' && mb_strpos($haystack, $needle) !== false;
	}
}

class DynamicPDOStatement {

	public $dtpdo, $query, $options, $st;

	function __construct(DynamicPDO $dtpdo, string $query, array $options) {
		$this->dtpdo = $dtpdo;
		$this->query = $query;
		$this->options = $options;
	}

	private function getAndRemoveParameter(string $placeholder, array &$params) {
		if (! $params || ! array_key_exists($placeholder, $params)) {
			raiseErrorAndExit('Error', 'Argumnet $params of method execute() should contain table name for placeholder :' . $placeholder . '.');
		}
		$name = $params[$placeholder];
		unset($params[$placeholder]);
		return $name;
	}

	private function findNextPlaceholder(string $label_pattern) {
		$placeholder_matches = [];
		preg_match($label_pattern, $this->query, $placeholder_matches);
		if (! empty($placeholder_matches)) {
			$placeholder = $placeholder_matches[1];
			return $placeholder;
		} else {
			return False;
		}
	}

	private function prepare(array &$params) {
		$table_names = [];
		while ($table_placeholder =
				$this->findNextPlaceholder('/.*?[ .]+t:(\w*).*/')) {
			$table_name = $this->getAndRemoveParameter($table_placeholder,
				 $params);
			$table_names[] = $table_name;
			if (! $this->dtpdo->hasTable($table_name)) {
				raiseErrorAndExit('Error', 'Table "' . $table_name . '" does not exist.');
			}
			$this->query = str_replace('t:' . $table_placeholder,
				$table_name, $this->query);
		}
		while ($column_placeholder =
				$this->findNextPlaceholder('/.*?[ .=<>()]+c:(\w*).*/')) {
			$column_name = $this->getAndRemoveParameter($column_placeholder,
				$params);
			if (! $this->dtpdo->hasColumnFromTable($column_name,
					$table_names)) {
				$x = implode('" , "', $table_names);
				raiseErrorAndExit('Error', 'Column "' . $column_name . '" does not exist in tables "' . '".');
			}
			$this->query = str_replace('c:' . $column_placeholder,
				$column_name, $this->query);
		}
		return $this->dtpdo->prepare($this->query);
	}

	public function execute(array $params = null) {
		try {
			if (! isset($this->st)) {
				$this->st = $this->prepare($params);
			}
			pprint($this->query, ['name', 'color' => 'blue']);
			if (! empty($params)) pprint($params, ['name', 'json', 'color' => 'blue']);
			return $this->st->execute($params);
		} catch (PDOException $e) {
			raiseErrorAndExit('PDO error', $e->getMessage());
		}
	}

	public function __call($method, $args) {
		if (method_exists($this->st, $method)) {
			return call_user_func_array([$this->st, $method], $args);
		} else {
			raiseErrorAndExit('Uncaught Error', 'Call to undefined method DynamicPDOStatement::' . $method . '()');
		}
	}

}

class DynamicPDO extends PDO {

	private $table_names, $column_names;

	public function dynamicPrepare(string $query, array $options = []) {
		return new DynamicPDOStatement($this, $query, $options);
	}

	public function dPrepare($query, $options = []) {
		return $this->dynamicPrepare($query, $options);
	}

	private function getAllTableNames() {
		if (! isset($this->table_names)) {
			try {
				$st = $this->prepare('SHOW TABLES');
				$st->execute();
			} catch (PDOException $e) {
				raiseErrorAndExit('PDO error', $e->getMessage());
			}
			$this->table_names = [];
			while ($row = $st->fetch()) {
				$this->table_names[] = $row[0];
			}
		}
		return $this->table_names;
	}

	public function hasTable($table_name) {
		return in_array($table_name, $this->getAllTableNames());
	}

	public function hasColumnFromTable($column_name, $possible_table_names) {
		if (! isset($this->column_names)) {
			$this->column_names = [];
		}
		if (str_contains($column_name, '.')) {
			list($table_name, $column_name) = explode('.', $column_name);
			$possible_table_names = [$table_name];
		}
		foreach ($possible_table_names as $table_name) {
			if ($this->hasTable($table_name)) {
				if (isset($this->column_names[$table_name]) && in_array(
						$column_name, $this->column_names[$table_name])) {
					return True;
				}
			}
		}
		foreach ($possible_table_names as $table_name) {
			if ($this->hasTable($table_name)) {
				$table_column_names = $this->getColumnNamesFromTable($table_name);
				if (in_array($column_name, $table_column_names)) {
					return True;
				}
			}
		}
		return False;
	}

	public function getColumnNamesFromTable($table_name) {
		if (! isset($this->column_names[$table_name])) {
			try {
				$st = $this->dPrepare('SHOW COLUMNS FROM t:table');
				$st->execute(['table' => $table_name]);
			} catch (PDOException $e) {
				raiseErrorAndExit('PDO error', $e->getMessage());
			}
			$this->column_names[$table_name] = $st->fetchAll(PDO::FETCH_COLUMN);
		}
		return $this->column_names[$table_name];
	}

}

class DB {

	private static $db = null;

	final private function __construct() { }
	final private function __clone() { }

	public static function getConnection() {
		if (DB::$db === null) {
			try {
				$base = 'mysql:host=rp2.studenti.math.hr;dbname=slapnicar;charset=utf8';
				$user = 'student';
				$pass = 'pass.mysql';
				//DB::$db = new PDO($base, $user, $pass);
				DB::$db = new DynamicPDO($base, $user, $pass);
				DB::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				DB::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			} catch(PDOException $e) {
				raiseErrorAndExit('PDO error', $e->getMessage());
			}
		}
		return DB::$db;
	}
}

?>
