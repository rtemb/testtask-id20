<?php

/**
 * Базовый класс модели
 * Выполняет подключение к БД  
 * предоставляет методы для работы с БД
 *
 * @author Barhanov Artem <darakon92@email.com>
 * @version 1.0 2017-08-06
 */
class dbMysql {
	const DB_HOST_NAME = '';
	const DB_USER_NAME = '';
	const DB_USER_PASSWd = '';
	const DB_NAME = '';

	/** @var PDO экземпляр подключения к БД */
	private static $_pdo;
	/** @var dbMysql объект класса */
	private static $_db;
	/** @var int последний вставленный id */
	public $lastId;
	
	public static function getInstance() {
		if (!self::$_db) {
			$dsn = 'mysql:host=' . self::DB_HOST_NAME . ';dbname=' . self::DB_NAME . ';charset=utf8';
			$opt = array(
				PDO::ATTR_ERRMODE				=> PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC
			);
			self::$_pdo= new PDO($dsn, self::DB_USER_NAME, self::DB_USER_PASSWd, $opt);
			self::$_db = new self;
		}
		return self::$_db;
	}

	public function fetchAll() {
		$args = func_get_args();
		$result = call_user_func_array(array($this, 'query'), $args);
		return $result->fetchAll();
	}
	
	public function fetchFirstColumn() {
		$args = func_get_args();
		$result = call_user_func_array(array($this, 'query'), $args);
		return $result->fetchAll()[0];
	}
	
	public function fetchFirstField() {
		$args = func_get_args();
		$result = call_user_func_array(array($this, 'query'), $args);
		return $result->fetchColumn();
	}
	
	public function insert() {
		$args = func_get_args();
		$result = call_user_func_array(array($this, 'query'), $args);
		$this->lastId = self::$_pdo->lastInsertId();
		return intval($result->errorCode()) ? false : true;
	}
	
	public function update() {
		$args = func_get_args();
		$result = call_user_func_array(array($this, 'query'), $args);
		$this->lastId = self::$_pdo->lastInsertId();
		return intval($result->errorCode()) ? false : true;
	}
	
	public function query() {
		$sqlArgs = func_get_args();
		$sql = $sqlArgs[0];
		unset($sqlArgs[0]);
		$values = $sqlArgs;
		if (!is_string($sql)) {
			return false;
		}
		$sth = self::$_pdo->prepare($sql);
		if (!empty($values)) {
			if (is_array($values) && is_array($values[1])) {
				$values = array_combine(range(1, count($values[1])), array_values($values[1]));
			}
			$this->_bindParams($sth, $values);
		}
		$sth->execute();		
		return $sth;
	}

	public function transactionStart() {
		self::$_pdo->beginTransaction();
	}

	public function transactionRollback() {
		self::$_pd->rollBack();
	}

	public function transactionCommit() {
		self::$_pdo->commit();
	}
	
	private function _bindParams($sth, array &$params) {
		foreach ($params as $key => $value) {
			$type = null;
			$type = gettype($value);
			if ($type == 'integer') {
				$valType = PDO::PARAM_INT;
			} elseif ($type == 'string' || $type == 'double') {
				$valType = PDO::PARAM_STR;
			} elseif ($type == 'boolean') {
				$valType = PDO::PARAM_BOOL;
			} elseif ($type == 'null') {
				$valType = PDO::PARAM_NULL;
			}
			$sth->bindValue($key, $value, $valType);
		}
	}
}