<?php
require_once __DIR__ . '/application/models/transaction.model.php';

/**
 * Скрипт для пересчета транзакций
 *
 * @author Barhanov Artem <darakon92@gmail.com>
 * @version 1.0 2017-08-06
 */

const CORRECTIONS_PER_PART		= 500;
const PER_PART_SLEEP_SECONDS	= 1;

echo ("[" . date('Y-m-d H:i:s') . "]" . " [INFO]  Starting up... \n"); 

$fails = 0;
$correctionList = Transaction::getCorrections();
$correctionParts = array_chunk($correctionList, CORRECTIONS_PER_PART);
foreach ($correctionParts as $part) {
	foreach ($part as $correction) {
		$Transaction = Transaction::getById($correction->id);
		$res = $Transaction->makeCorrection($correction);
		if (!$res) {
			echo ("[" . date('Y-m-d H:i:s') . "]" . " [WARN]  in correction of transaction: " . $correction->id .  ". Refund transaction id:  " . $correction->correctionId ."\n");
			$fails ++;
		}
	}
	sleep(PER_PART_SLEEP_SECONDS);
}

if (!$fails) {
	echo ("[" . date('Y-m-d H:i:s') . "]" . " [OK]    All " . count($correctionList) . " transactions were corrected \n");
	exit(0);
}

echo ("[" . date('Y-m-d H:i:s') . "]" . " [ERROR] " . $fails . " errors are occurred while correction \n"); 
exit(1);