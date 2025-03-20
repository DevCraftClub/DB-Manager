<?php
global $parsedData, $mh_admin;

if (!defined('DATALIFEENGINE')) {
	header('HTTP/1.1 403 Forbidden');
	header('Location: ../../../../');
	exit('Hacking attempt!');
}

$exportData   = filter_var_array($parsedData);
$parsedTables = [];
$settings     = DataManager::getConfig('db_manager');

foreach ($exportData['table'] as $table) {
	$parser         = new SqlTableParser($table, DBNAME);
	$parsedTables[] = $parser->getResult();
}

/**
 * Сортирует таблицы так, чтобы если у таблицы есть родитель (зависимость),
 * то она располагалась в массиве после всех своих родителей.
 *
 * @param ParsedTable[] $tables Массив объектов ParsedTable.
 * @return ParsedTable[] Отсортированный массив.
 * @throws Exception Если обнаружена циклическая зависимость.
 */
function sortTablesByDependency(array $tables): array {
	// Создаем отображение: имя таблицы => объект ParsedTable
	$tableMap = [];
	foreach ($tables as $table) {
		$tableMap[$table->getName()] = $table;
	}

	// Инициализируем in-degree (число зависимостей) для каждой таблицы.
	$inDegree = [];
	foreach ($tables as $table) {
		$inDegree[$table->getName()] = 0;
	}

	// Для каждой таблицы увеличиваем in-degree, если она зависит от другой,
	// которая присутствует в нашем списке.
	foreach ($tables as $table) {
		$parents = $table->getParent();
		foreach ($parents as $parentName) {
			// Учитываем только те зависимости, для которых присутствует соответствующая таблица
			if (isset($tableMap[$parentName])) {
				$inDegree[$table->getName()]++;
			}
		}
	}

	// Собираем все таблицы с in-degree = 0 (нет зависимостей)
	$queue = [];
	foreach ($inDegree as $name => $degree) {
		if ($degree === 0) {
			$queue[] = $tableMap[$name];
		}
	}

	$sorted = [];

	// Алгоритм Кана: пока есть таблицы без зависимостей, удаляем их и уменьшаем in-degree у зависимых.
	while (!empty($queue)) {
		// Извлекаем таблицу из очереди
		$current = array_shift($queue);
		$sorted[] = $current;

		// Для каждой таблицы, которая зависит от текущей, уменьшаем in-degree
		foreach ($tables as $table) {
			// Если текущая таблица является родителем для $table
			if (in_array($current->getName(), $table->getParent(), true)) {
				$inDegree[$table->getName()]--;
				// Если зависимостей больше не осталось – добавляем таблицу в очередь
				if ($inDegree[$table->getName()] === 0) {
					$queue[] = $tableMap[$table->getName()];
				}
			}
		}
	}

	// Если количество отсортированных таблиц меньше исходного,
	// значит, в зависимостях обнаружен цикл.
	if (count($sorted) !== count($tables)) {
		throw new Exception('Циклическая зависимость обнаружена между таблицами.');
	}

	return $sorted;
}

$parsedTables = sortTablesByDependency($parsedTables);

$createStrings = [];
$createStrings[] = '-- ------------------------------------------------------ --';
$createStrings[] = '--                                                        --';
$createStrings[] = '-- ' . __('Экспорт базы данных при помощи DB Manager') . '              --';
$createStrings[] = '-- ' . __('Ссылка: https://devcraft.club/downloads/db-manager.30/') . ' --';
$createStrings[] = '--                                                        --';
$createStrings[] = '-- ------------------------------------------------------ --';
$createStrings[] = __('-- Дата создания: ') . date('r');
$createStrings[] = __("-- Сервер: ") . DBHOST;
$createStrings[] = '-- ------------------------------------------------------ --' . PHP_EOL;
$createStrings[] = "/*!40030 SET NAMES UTF8 */;";
$createStrings[] = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;";
$createStrings[] = "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;";
$createStrings[] = "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;";
$createStrings[] = "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;";
$createStrings[] = "/*!40103 SET TIME_ZONE='" . date('P') . "' */;";
$createStrings[] = "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;";
$createStrings[] = "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;";
$createStrings[] = "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;";
$createStrings[] = "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;" . PHP_EOL;
$createStrings[] = "--";
$createStrings[] = __("-- База данных: ") . DBNAME ;
$createStrings[] = '--';
$createStrings[] = $mh_admin->load_data(DBNAME, [
	'sql' => "SHOW CREATE DATABASE `" . DBNAME . "`"
])[0]['Create Database'] . ";";
$createStrings[] = 'USE ' . DBNAME . ';';
$createStrings[] = '--' . PHP_EOL;


foreach ($parsedTables as $table) {
	$createStrings[] = "--";
	$createStrings[] = __("-- Таблица: ") . $table->getName() ;
	$createStrings[] = '--';
	$createStrings[] = $table->generateSql($settings['key_export'] === 'after');
}

if ($settings['key_export'] === 'down') {
	foreach ($parsedTables as $table) {
		if(count($table->getIndexes()) > 0) {
			$createStrings[] = "--";
			$createStrings[] = __("-- Ключи для таблицы: ") . $table->getName();
			$createStrings[] = '--';
		}
		foreach ($table->getIndexes() as $index) {
			$createStrings[] = $index->generateSql();
		}
	}
	$createStrings[] = PHP_EOL;
}
$createStrings[] = PHP_EOL;
foreach ($parsedTables as $table) {
	if (count($table->getValues()) > 0) {
		$createStrings[] = "--";
		$createStrings[] = __("-- Данные для таблицы: ") . $table->getName();
		$createStrings[] = '--';
	}
	$createStrings[] = $table->getSqlValues($settings['values_export_type'] === 'group') . PHP_EOL;
}

$sql_file_name = DBNAME . '_' . (new DateTime())->format('Y_m_d_H_i_s') . '_' . count($parsedTables) . '_tables';
$sql_file = DataManager::joinPaths(ROOT_DIR,$settings['export_path'], "{$sql_file_name}.sql");

file_put_contents(
	$sql_file,
	implode(PHP_EOL, array_filter($createStrings, fn($sql) => $sql != "")),
	LOCK_EX
);

if ($settings['zip_data'] == 'zip') {
	$zip         = new ZipArchive();
	$zipFileName = str_replace('sql', 'zip', $sql_file);

	if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
		$zip->addFile($sql_file, "{$sql_file_name}.sql");
		$zip->close();

	} else {
		echo (new ErrorResponseAjax())->setData(__('Архивация архива не удалась!'))->send();
		exit;
	}
}

if ($settings['zip_data'] == 'bzip2') {

	$bzipFileName = str_replace('sql', 'bz2', $sql_file);

	$fileContents = file_get_contents($sql_file);

	if ($fileContents === false || file_put_contents($bzipFileName, bzcompress($fileContents, 9)) === false) {
		echo (new ErrorResponseAjax())->setData(__('BZIP2 сжатие файла не удалось!'))->send();
		exit;
	}
}

if ($settings['export_to_telegram']) {

	try {
		$filePath   = DataManager::joinPaths(
			$settings['export_path'],
			"{$sql_file_name}." . ($settings['zip_data'] === 'raw' ? 'sql' : ($settings['zip_data'] === 'zip' ? 'zip' : 'bz2'))
		);
		$fileToSend = new CURLFile($filePath);
		$tgUrl = "https://api.telegram.org/bot{$settings['tg_token']}/sendDocument";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $tgUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, [
			'chat_id'  => (int) $settings['tg_chat'],
			'document' => $fileToSend,
			'caption'  => __('Экспортированная база данных') . ": {$sql_file_name}"
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($response === false || $httpCode !== 200) {
			$errorMsg = curl_error($ch);
			curl_close($ch);
			throw new Exception(__('Ошибка при отправке файла в Telegram: ') . $errorMsg);
		}

		curl_close($ch);

	} catch (Exception|Throwable $e) {
		echo (new ErrorResponseAjax())->setData($e->getMessage())->send();
		exit;
	}
}

if ($settings['zip_data'] !== 'raw') {
	unlink($sql_file);
}

echo (new SuccessResponseAjax())->setData(__('Создание резервной копии завершено!'))->setRedirect(
	$_SERVER['HTTP_REFERER']
)->send();
exit;
