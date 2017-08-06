<?php
/**
 * Обьект корректировки транзакции
 * 
 * @author Barhanov Artem <darakon92@gmail.com>
 * @version 1.0 2017.08.06
 */
class TransactionCorrection {
	
	/** @var int id транзакции для коррекции */
	public $id;
	/** @var int id транзакции возврата */
	public $correctionId;
	/** @var float текущее значенение */
	private $volume;
	/** @var float  величина коррекции */
	private $correctionVolume;

	
	public function __construct($data = false) {
		if (!empty($data)) {
			$this->id				= (int)		array_key_exists('id', $data) ? $data['id'] : false;
			$this->correctionId		= (string)	array_key_exists('correction_id', $data) ? $data['correction_id'] : false;
			$this->volume			= (string)	array_key_exists('volume', $data) ? $data['volume'] : false;
			$this->correctionVolume	= (string)	array_key_exists('correction_volume', $data) ? $data['correction_volume'] : false;
		}
	}

	/**
	 * Выполняет пересчет volume с учетом возврата
	 * 
	 * @author Barhanov Artem <darakon92@gmail.com>
	 * @version 1.0 2017.08.06
	 * 
	 * @return float
	 */
	public function recalculate(): float {
		return (float) $this->volume + $this->correctionVolume;
	}
}