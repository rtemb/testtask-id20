<?php
require_once __DIR__ . '/application/models/transaction.model.php';

/**
 * Скрипт для пересчета транзакций
 *
 * @author Barhanov Artem <darakon92@gmail.com>
 * @version 1.1 2017-08-07
 */

const CORRECTIONS_PER_PART		= 500;
const PER_PART_SLEEP_SECONDS	= 1;

echo ("[" . date('Y-m-d H:i:s') . "]" . " [INFO]  Starting up... \n"); 

$fails = 0;
$success = 0;
$dontCorrected = 0;

$correctionIds = Transaction::getCorrectionsIds();
echo ("[" . date('Y-m-d H:i:s') . "]" . " [INFO]  Selected " . count($correctionIds) . " refund ids \n"); 
$chunks = array_chunk($correctionIds, CORRECTIONS_PER_PART);
foreach ($chunks as $part) {
	foreach ($part as $value) {
		$id = (int) $value['id'];
		$Transaction = Transaction::getById($id);
		$res = $Transaction->makeCorrection();
		if ($res === false) {
			echo ("[" . date('Y-m-d H:i:s') . "]" . " [WARN]  while correction refund transaction id:  " . $id ."\n");
			$fails ++;
		} else if ($res === null)  {
			$dontCorrected++;
		} else {
			$success++;
			echo ("[" . date('Y-m-d H:i:s') . "]" . " [WARN]  while correction refund transaction id:  " . $id ."\n");
		}
	}
	sleep(PER_PART_SLEEP_SECONDS);
}

if (!$fails) {
	echo ("[" . date('Y-m-d H:i:s') . "]" . " [OK]    " . $success . " transactions were corrected \n");
	echo ("[" . date('Y-m-d H:i:s') . "]" . " [OK]    For " . $dontCorrected . " can't find previous operations \n");
	exit(0);
}

echo ("[" . date('Y-m-d H:i:s') . "]" . " [ERROR] " . $fails . " errors are occurred while correction \n"); 
exit(1);