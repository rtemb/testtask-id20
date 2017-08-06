<?php

require_once __DIR__ . '/dbMysql.php';

/**
 * Обертка над классом модели
 * Возвращает экземпляр подключения 
 *
 * @version 1.0 3-09-2013
 * @author Artyom Barhanov
 */
class Model {

	public function __construct() {}
	
	/**
	 * Обьект соеденения с БД
	 * @return \dbMysql
	 */
	protected static function _db(): dbMysql {
		return dbMysql::getInstance();
	}
}