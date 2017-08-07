<?php

require_once __DIR__ . '/../core/model.php';

/**
 * Модель транзакций
 * 
 * @author Barhanov Artem <darakon92@gmail.com>
 * @version 1.0 2017.08.06
 */
class Transaction extends Model {
	
	/** @var int id транзакции */
	private $id;
	/** @var string номер карты */
	private $cardNumber;
	/** @var string  дата операции*/
	private $date;
	/** @var float обьем */
	private $volume;
	/** @var string наименование сервиса */
	private $service;
	/** @var int id станци */
	private $addressId;

	
	public function __construct($data = false) {
		parent::__construct();
		if (!empty($data)) {
			$this->id			= array_key_exists('id', $data) ? (int) $data['id'] : false;
			$this->cardNumber	= array_key_exists('card_number', $data) ? (string) $data['card_number'] : false;
			$this->date			= array_key_exists('date', $data) ? (string) $data['date'] : false;
			$this->volume		= array_key_exists('volume', $data) ? (float) $data['volume'] : false;
			$this->service		= array_key_exists('service', $data) ? (string) $data['service'] : false;
			$this->addressId	= array_key_exists('address_id', $data) ? (int) $data['address_id'] : false;
		}
	}
	
	/**
	 * Возвращается обьект транзакции по id
	 * 
	 * @author Barhanov Artem <darakon92@gmail.com>
 	 * @version 1.1 2017-08-07
	 * 
	 * @param int $id - id транзакции
	 * @return Transaction
	 */
	public static function getById(int $id): Transaction {
		$sql = 'SELECT id, card_number, date, volume, service, address_id FROM data WHERE id = ?';
		$res = self::_db()->fetchFirstColumn($sql, $id);
		return new self($res);
	}

	/**
	 * Обновляет обьем транзакции
	 * 
	 * @author Barhanov Artem <darakon92@gmail.com>
 	 * @version 1.1 2017-08-07
	 *
	 * @return bool|null
	 */
	public function makeCorrection() {
		$lastTransaction = $this->getLastOutlay();
		if (!$lastTransaction->id) {
			return null;
		}
		self::_db()->transactionStart();
		$res[] = $lastTransaction->updateVolume($this->volume);
		$res[] = $this->delete();
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
	 * Возвращает список id возвратов
	 * 
	 * @author Barhanov Artem <darakon92@gmail.com>
 	 * @version 1.1 2017.08.07
	 *
	 * @return array
	 */
	public static function getCorrectionsIds(): array {
		$sql = 'SELECT id FROM `data` WHERE volume > 0';
		return self::_db()->fetchAll($sql);
	}

	/**
	 * Возвращает послежнюю транзакцию по карте
	 * Уситывает станцию и адрес
	 * 
	 * @author Barhanov Artem <darakon92@email.com>
 	 * @version 1.1 2017.08.07
	 *
	 * @return Transaction
	 */
	private function getLastOutlay(): Transaction {
		$sql = 'SELECT id, card_number, date, volume, service, address_id FROM `data` WHERE id < ? AND `card_number` = ? AND address_id = ? AND `volume` <= 0 AND service = ? LIMIT 1';
		$data = self::_db()->fetchFirstColumn($sql, $this->id, $this->cardNumber, $this->addressId, $this->service);
		return new self($data);
	}

	/**
	 * Обновляет volume операции 
	 * 
	 * @author Barhanov Artem <darakon92@gmail.com>
 	 * @version 1.1 2017.08.07
	 *
	 * @return bool
	 */
	public function updateVolume(float $newVolume): bool {
		$sql = 'UPDATE data SET volume = ? WHERE id = ?';
		return self::_db()->update($sql, $this->volume + $newVolume, $this->id);
	}

	/**
	 * Удаляет транзакцию
	 * 
	 * @author Barhanov Artem <darakon92@gmail.com>
 	 * @version 1.0 2017.08.07
	 *
	 * @return bool
	 */
	public function delete(): bool {
		$sql = 'DELETE FROM data WHERE id = ?';
		$res =  self::_db()->query($sql, $this->id);
		return (intval($res->errorCode()) ? false : true);
	}
}