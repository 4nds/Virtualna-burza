<?php

require_once __DIR__ . '/../app/database/db.class.php';
require_once __DIR__ . '/../app/debug.php';


class Model {

	protected static $table;
	protected static $foreign_keys = [];
	private static $primary_key_names = [];
	private static $foreign_keys_maps = [];
	private $foreign_objects = [];
	

	function __construct(array $properties_map) {
		foreach ($properties_map as $property_name => $property_value) {
			$this->$property_name = $property_value;
		}
	}

	public function __call($method, $args) {
		$foreign_keys_map = self::getForeignKeysMap();
		$foreign_model_name = ucfirst($method);
		if (array_key_exists($foreign_model_name, $foreign_keys_map)) {
			if (! array_key_exists($method, $this->foreign_objects)) {
				$foreign_key_name = $foreign_keys_map[$foreign_model_name];
				$this->foreign_objects[$method] =
					$this->belongsTo($foreign_model_name, $foreign_key_name);
			}
			return $this->foreign_objects[$method];
		} else {
			raiseErrorAndExit('Uncaught Error', 'Call to undefined method DynamicPDOStatement::' . $method . '()');
		}
	}

	protected static function getForeignKeysMap() {
		$static_class = get_called_class();
		if (! array_key_exists($static_class, self::$foreign_keys_maps)) {
			$foreign_keys_map = [];
			foreach (static::$foreign_keys as $foreign_key_string) {
				list($foreign_model_name, $foreign_key_name) = 
					explode(':', $foreign_key_string);
				$foreign_model_name = $foreign_model_name;
				$foreign_keys_map[$foreign_model_name] = $foreign_key_name;
			}
			self::$foreign_keys_maps[$static_class] = $foreign_keys_map;
		}
		return self::$foreign_keys_maps[$static_class];
	}

	protected static function getPrimaryKeyName() {
		$static_class = get_called_class();
		if (! array_key_exists($static_class, self::$primary_key_names)) {
			$db = DB::getConnection();
			$st = $db->dPrepare('SHOW KEYS FROM t:table' .
				' WHERE Key_name = "PRIMARY"');
			$st->execute(['table' => static::$table]);
			$row = $st->fetch(PDO::FETCH_ASSOC);
			if($row === false) {
				exit('Error: Table ' . static::$table . ' should contain primary key.');
			}
			self::$primary_key_names[$static_class] = $row['Column_name'];
		}
		return self::$primary_key_names[$static_class];
	}

	public static function all() {
		$db = DB::getConnection();
		$st = $db->dPrepare('SELECT * FROM t:table');
		$st->execute(['table' => static::$table]);
		$list = [];
		while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
			$list[] = new static($row);
		}
		return $list;
	}

	public static function find($primary_key) {
		$db = DB::getConnection();
		$st = $db->dPrepare('SELECT * FROM t:table' .
			' WHERE c:primary_key_name=:primary_key LIMIT 1');
		$st->execute(['table' => static::$table,
			'primary_key_name' => self::getPrimaryKeyName(),
			'primary_key' => $primary_key]);
		if($row = $st->fetch(PDO::FETCH_ASSOC)) {
			return new static($row);
		}
	}

	public static function where($column_name, $column_value) {
		$db = DB::getConnection();
		$st = $db->dPrepare('SELECT * FROM t:table' .
			' WHERE c:column_name=:column_value');
		$st->execute(['table' => static::$table,
			'column_name' => $column_name,
			'column_value' => $column_value]);
		$list = [];
		while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
			$list[] = new static($row);
		}
		return $list;
	}

	protected function getForeignSelectStringAndParams($model_name) {
		$db = DB::getConnection();
		$model_table_column_names = 
			$db->getColumnNamesFromTable($model_name::$table);
		foreach ($model_table_column_names as &$column_name) {
			$column_name = $model_name::$table . '.' . $column_name;
		}
		$select_column_names = array_merge([], $model_table_column_names);
		$join_string = '';
		$params = [];
		for ($i=0; $i<count($model_name::$foreign_keys); $i++) {
			list($foreign_model_name, $foreign_key_name) = 
				explode(':', $model_name::$foreign_keys[$i]);
			$foreign_table_column_names =
				$db->getColumnNamesFromTable($foreign_model_name::$table);
			foreach ($foreign_table_column_names as &$column_name) {
				$column_name = sprintf('ft%d.', $i) . $column_name;
			}
			$select_column_names = array_merge($select_column_names,
				$foreign_table_column_names);
			$join_string .= sprintf(' INNER JOIN t:foreign_table%1$d as ft%1$d' . 
				' ON ft%1$d.c:primary_key_name%1$d=c:foreign_key_name%1$d', $i);
			$params[sprintf('foreign_table%d', $i)] = $foreign_model_name::$table;
			$params[sprintf('primary_key_name%d', $i)] = 
				$foreign_model_name::getPrimaryKeyName();
			$params[sprintf('foreign_key_name%d', $i)] = 
				$model_name::$table . '.' . $foreign_key_name;
		}
		$select_column_string = implode(', ', $select_column_names);
		$select_string = 'SELECT ' . $select_column_string .
			' FROM t:table' . $join_string;
		$params['table'] = $model_name::$table;
		return [$select_string, $params];
	}

	protected function getSelectStringAndParams($model_name, $options) {
		if (in_array('foreign keys', $options)) {
			list($select_string, $params) = 
				$this->getForeignSelectStringAndParams($model_name);
		} else {
			$select_string = 'SELECT * FROM t:table';
			$params = ['table' => $model_name::$table];
		}
		return [$select_string, $params];
	}

	protected function getPropertiesMap($model_name, $row, $no_primary_key = False) {
		$db = DB::getConnection();
		$table_column_names = 
			$db->getColumnNamesFromTable($model_name::$table);
		$properties_map = [];
		$model_primary_key_name = $model_name::getPrimaryKeyName();
		foreach ($table_column_names as $column_name) {
			if ($no_primary_key && $column_name === $model_primary_key_name) {
				$foreign_key_name = self::getForeignKeysMap()[$model_name];
				$properties_map[$column_name] = $row[$foreign_key_name];
			} else if (array_key_exists($column_name, $row)) {
				$properties_map[$column_name] = $row[$column_name];
			} else {
				raiseErrorAndExit('Error', 'Column name "' . $column_name . '"" does not exists in table "' . $foreign_model_name::table . '".');
			}

		}
		return $properties_map;
	}

	protected function getModelObjectFromRow($model_name, $row, $options) {
		if (in_array('foreign keys', $options)) {
			$model_properties_map = $this->getPropertiesMap($model_name, $row);
			$model_object = new $model_name($model_properties_map);
			$foreign_keys_map = $model_name::getForeignKeysMap();
			foreach ($foreign_keys_map as $foreign_model_name => $fkn) {
				$foreign_properties_map = $model_object->getPropertiesMap(
					$foreign_model_name, $row, True);
				$foreign_object = new $foreign_model_name($foreign_properties_map);
				$foreign_property_name = lcfirst($foreign_model_name);
				$model_object->foreign_objects[$foreign_property_name] = $foreign_object;
			}
		} else {
			$model_object = new $model_name($row);
		}
		return $model_object;
	}

	public function hasMany($model_name, $requested_foreign_key_name,
			$options = []) {
		$db = DB::getConnection();
		$primary_key_name = self::getPrimaryKeyName();
		list($select_string, $params) = 
			$this->getSelectStringAndParams($model_name, $options);
		$query = $select_string . 
			' WHERE c:requested_foreign_key_name=:requested_foreign_key';
		$params['requested_foreign_key_name'] = $requested_foreign_key_name;
		$params['requested_foreign_key'] = $this->$primary_key_name;
		if (array_key_exists('limit', $options)) {
			$query .= ' LIMIT :limit';
			$params['limit'] = $options['limit'];
		}
		$st = $db->dPrepare($query);
		$st->execute($params);
		$list = [];
		while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
			$list[] = $this->getModelObjectFromRow($model_name, $row, $options);
		}
		return $list;
	}

	public function hasOne($model_name, $foreign_key_name) {
		$list = self::hasMany($model_name, $foreign_key_name, 1);
		if (! empty($list)) {
			return $list[0];
		}
	}

	public function belongsTo($model_name, $foreign_key_name) {
		return $model_name::find($this->$foreign_key_name);
	}
	
	protected function update() {
		$db = DB::getConnection();
		$table_column_names = 
			$db->getColumnNamesFromTable(static::$table);
		$set_parts = [];
		$params = [];
		for ($i=0; $i<count($table_column_names); $i++) {
			$column_name = $table_column_names[$i];
			if (property_exists($this, $column_name)) {
				$set_parts[] = sprintf('c:column_name%1$d=:column%1$d', $i);
				$params[sprintf('column_name%d', $i)] = $column_name;
				$params[sprintf('column%d', $i)] = $this->$column_name;
			}
		}
		$set_string = implode(', ', $set_parts);
		$st = $db->dPrepare('UPDATE t:table SET ' . $set_string .
			' WHERE c:primary_key_name=:primary_key');
		$params['table'] = static::$table;
		$primary_key_name = self::getPrimaryKeyName();
		$params['primary_key_name'] = $primary_key_name;
		$params['primary_key'] = $this->$primary_key_name;
		$st->execute($params);
		$updated = $st->fetch(PDO::FETCH_ASSOC);
		return $updated;
	}
	
	protected function insert() {
		$db = DB::getConnection();
		$table_column_names = 
			$db->getColumnNamesFromTable(static::$table);
		$insert_column_names = [];
		$insert_values = [];
		$params = [];
		$primary_key_name = self::getPrimaryKeyName();
		for ($i=0; $i<count($table_column_names); $i++) {
			$column_name = $table_column_names[$i];
			if (property_exists($this, $column_name)) {
				$insert_column_names[] = sprintf('c:column_name%1$d', $i);
				$insert_values[] = sprintf(':column%1$d', $i);
				$params[sprintf('column_name%d', $i)] = $column_name;
				$params[sprintf('column%d', $i)] = $this->$column_name;
			} else if ($column_name === $primary_key_name) {
				$insert_column_names[] = sprintf('c:column_name%1$d', $i);
				$insert_values[] = 'DEFAULT';
				$params[sprintf('column_name%d', $i)] = $column_name;
			}
		}
		$insert_string = implode(', ', $insert_column_names);
		$values_string = implode(', ', $insert_values);
		$st = $db->dPrepare('INSERT INTO t:table (' . $insert_string . ')' .
			' VALUES (' . $values_string . ')');
		$params['table'] = static::$table;
		$st->execute($params);
		$inserted = $st->fetch(PDO::FETCH_ASSOC);
		return $inserted;
	}

	public function save() {
		$primary_key_name = self::getPrimaryKeyName();
		if (property_exists($this, $primary_key_name) &&
				static::find($this->$primary_key_name)) {
			return $this->update();
		} else {
			return $this->insert();
		}
		
	}

}



?>