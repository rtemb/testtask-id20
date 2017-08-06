<?php

require_once __DIR__ . '/../core/model.php';
require_once __DIR__ . '/../classes/transactioncorrection.class.php';

/**
 * Модель транзакций
 * 
 * @author Barhanov Artem <darakon92@gmail.com>
 * @version 1.0 2017.08.06
 */
class Transaction extends Model {
	
	/** @var int id транзакции */
	public $id;
	/** @var string номер карты */
	public $cardNumber;
	/** @var string  дата операции*/
	public $operationDate;
	/** @var double обьем */
	public $volume;
	/** @var string наименование сервиса */
	public $serviceName;
	/** @var int id станци */
	public $stationId;

	
	public function __construct($data = false) {
		parent::__construct();
		if (!empty($data)) {
			$this->id			= (int)		array_key_exists('id', $data) ? $data['id'] : false;
			$this->cardNumber	= (string)	array_key_exists('card_number', $data) ? $data['card_number'] : false;
			$this->operationDate= (string)	array_key_exists('operation_date', $data) ? $data['operation_date'] : false;
			$this->volume		= (double)	array_key_exists('volume', $data) ? $data['volume'] : false;
			$this->serviceName	= (string)	array_key_exists('service_name', $data) ? $data['service_name'] : false;
			$this->stationId	= (int)		array_key_exists('station_id', $data) ? $data['station_id'] : false;
		}
	}
	
	/**
	 * Возвращается обьект транзакции по id
	 * 
	 * @author Barhanov Artem <darakon92@gmail.com>
 	 * @version 1.0 2017-08-06
	 * 
	 * @param int $id - id транзакции
	 * @return Transaction
	 */
	public static function getById(int $id): Transaction {
		$sql = 'SELECT id, card_number, operation_date, volume, service_name, station_id FROM transactions WHERE id = ?';
		$res = self::_db()->fetchFirstColumn($sql, $id);
		return new self($res);
	}

	/**
	 * Обновляет обьем транзакции
	 * 
	 * @author Barhanov Artem <darakon92@gmail.com>
 	 * @version 1.0 2017-08-06
	 *
	 * @param TransactionCorrection $TransactionCorrection - обьект корекции
	 * @return bool
	 */
	public function makeCorrection(TransactionCorrection $TransactionCorrection): bool {
		self::_db()->transactionStart();
		
		$sql = 'UPDATE transactions SET volume = ? WHERE id = ?';
		$res[] = self::_db()->update($sql, $TransactionCorrection->recalculate(), $TransactionCorrection->id);
		$sql = 'DELETE FROM transactions WHERE id = ?';
		$res[] = self::_db()->update($sql, $TransactionCorrection->correctionId);

		foreach ($res as $value) {
			if (!$value) {
				self::_db()->transactionRollback();
				return false;
			}
		}
		self::_db()->transactionCommit();
		return true;
	}
	
	/**
	 * Возвращает список обьектов коррекций для таблицы транзакций
	 * 
	 * @author Barhanov Artem <darakon92@gmail.com>
 	 * @version 1.0 2017.08.06
	 *
	 * @return array
	 */
	public static function getCorrections(): array {
		$sql = 'SELECT DISTINCT a.id AS `correction_id`, a.volume AS `correction_volume` 
			FROM transactions AS a JOIN transactions AS b ON a.volume > 0 AND b.volume < 0';
		$correctionEntries = self::_db()->fetchAll($sql);
		
		$ids = [];
		foreach ($correctionEntries as $value) {
			$ids[] = $value['correction_id'] - 1;
		}
		$entriesForCorrection = self::getTransactionDataByIds($ids);
		$data = self::prepareRawData($correctionEntries, $entriesForCorrection);
		$corrections = [];
		if (is_array($data)) {
			foreach($data as $row) {
				$corrections[] = new TransactionCorrection($row);
			}
		}
		return $corrections;
	}

	/**
	 * Возвращает данные по транзакциям по списку id транзакций
	 * 
	 * @author Barhanov Artem <darakon92@email.com>
 	 * @version 1.0 2017.08.06
	 *
	 * @return array
	 */
	private static function getTransactionDataByIds($ids): array {
		$in  = '(' . str_repeat('?,', count($ids) - 1) . '?' . ')';
		$sql = 'SELECT id, volume FROM transactions WHERE id IN' . $in;
		$entriesForCorrection = self::_db()->fetchAll($sql, $ids);
		return $entriesForCorrection;
	}

	/**
	 * Подготавливает данные для передачи в конструктор TransactionCorrection
	 * 
	 * @author Barhanov Artem <darakon92@gmail.com>
 	 * @version 1.0 2017.08.06
	 *
	 * @return array
	 */
	private static function prepareRawData(array $correctionEntries, array $entriesForCorrection): array {
		$combinedArray = [];
		foreach($entriesForCorrection as $row) {
			foreach ($correctionEntries as $value) {
				if ($row['id'] == $value['correction_id'] - 1) {
					$combinedArray[] = [
						'id' => $row['id'],
						'correction_id' => $value['correction_id'],
						'volume' => $row['volume'],
						'correction_volume' => $value['correction_volume']
					];
				}
			}
		}
		return $combinedArray;
	}
}