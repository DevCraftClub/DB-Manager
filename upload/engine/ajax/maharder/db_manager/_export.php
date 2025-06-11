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
$dbName       = DBNAME;
$outputDir    = DataManager::joinPaths(ROOT_DIR, $settings['export_path']);
DataManager::createDir($outputDir);

SqlExporter::setConfig($settings);
SqlExporter::setMhAjax($mh_admin);

$dbType = SqlExporter::detectDatabaseType();
$dbVersion = SqlExporter::getDatabaseVersion();

foreach ($exportData['table'] as $table) {
	$parser         = new SqlTableParser($table, $dbName);
	$parsedTables[] = $parser->getResult();
}

$parsedTables = SqlExporter::sortTablesByDependency($parsedTables);

$createStrings = [];
$createStrings[] = '-- ------------------------------------------------------ --';
$createStrings[] = '--                                                        --';
$createStrings[] = '-- ' . __('Экспорт базы данных при помощи DB Manager');
$createStrings[] = '-- ' . __('Ссылка: https://devcraft.club/downloads/db-manager.30/');
$createStrings[] = '--                                                        --';
$createStrings[] = '-- ------------------------------------------------------ --';
$createStrings[] = __('-- Дата создания: ') . date('r');
$createStrings[] = __("-- Сервер: ") . DBHOST;
$createStrings[] = __("-- Тип базы данных: ") . strtoupper($dbType) . ' ' . $dbVersion;
$createStrings[] = '-- ------------------------------------------------------ --' . PHP_EOL;
$compatibleHeaders = SqlExporter::generateCompatibleHeaders();
$createStrings = array_merge($createStrings, $compatibleHeaders);
$createStrings[] = "--";
$createStrings[] = __("-- База данных: ") . $dbName ;
$createStrings[] = '--';

$createDbSql = $mh_admin->load_data($dbName, [
		'sql' => "SHOW CREATE DATABASE `" . $dbName . "`"
	])[0]['Create Database'] . ";";
if (SqlExporter::supportsCreateOrReplace()) {
	$createStrings[] = str_replace('CREATE DATABASE', 'CREATE OR REPLACE DATABASE', $createDbSql);
} else {
	$createStrings[] = str_replace('CREATE DATABASE', 'CREATE DATABASE IF NOT EXISTS', $createDbSql);
}

$createStrings[] = 'USE ' . $dbName . ';';
$createStrings[] = '--' . PHP_EOL;


foreach ($parsedTables as $table) {
	$createStrings[] = "--";
	$createStrings[] = __("-- Таблица: ") . $table->getName() ;
	$createStrings[] = '--';

	$tableSql = $table->generateSql($settings['key_export'] === 'after');
	$tableSql = SqlExporter::fixSqlCompatibility($tableSql);

	$createStrings[] = $tableSql;
}

if ($settings['key_export'] === 'down') {
	foreach ($parsedTables as $table) {
		$indexes = [];
		foreach ($table->getIndexes() as $index) {
			$indexSql = $index->generateSql();
			$indexSql = SqlExporter::fixSqlCompatibility($indexSql);
			if (!empty($indexSql)) $indexes[] = $indexSql;
		}

		if(count($indexes) > 0) {
			$createStrings[] = "--";
			$createStrings[] = __("-- Ключи для таблицы: ") . $table->getName();
			$createStrings[] = '--';
			$createStrings[] = implode(PHP_EOL, $indexes);
		}
	}
	$createStrings[] = PHP_EOL;
}
$createStrings[] = PHP_EOL;

foreach ($parsedTables as $table) {
	$tableValues = $table->getSqlValues($settings['values_export_type'] === 'group');
	if(!empty($tableValues) && '\n' !== $tableValues) {
		$createStrings[] = "--";
		$createStrings[] = __("-- Данные для таблицы: ") . $table->getName();
		$createStrings[] = '--';
		$createStrings[] = $tableValues . PHP_EOL;
	}
}

$createStrings[] = PHP_EOL;

$createStrings = array_merge($createStrings, SqlExporter::generateFooter());

$sql_file_name = $dbName . '_' . (new DateTime())->format('Y_m_d_H_i_s') . '_' . count($parsedTables) . '_tables';
$sql_file = DataManager::joinPaths(ROOT_DIR, $settings['export_path'], "{$sql_file_name}.sql");

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
			'caption'  => __('Экспортированная база данных') . ": ({$dbType}): {$sql_file_name}"
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
