<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Ajax;

use DateTime;
use ZipArchive;
use DevCraft\Core\Application;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Support\DataManager;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\DbManager\Services\DbSqlLoader;
use DevCraft\Modules\DbManager\Services\SqlExporter;
use DevCraft\Modules\DbManager\Services\SqlTableParser;
use DevCraft\Modules\DbManager\Services\BackupPathHelper;

/**
 * AJAX-обработчик экспорта выбранных таблиц в SQL-файл.
 */
final class ExportHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$exportData = filter_var_array($request->data);
		$tables     = $exportData['table'] ?? [];

		if(!is_array($tables) || $tables === []) {
			return JsonResponse::fail(
				__('Ошибка'),
				__('Не выбраны таблицы для экспорта'),
				'validation',
				422,
			);
		}

		$settings = DataManager::getConfig('db_manager');
		$dbName   = DBNAME;
		$outputDir = BackupPathHelper::exportDir($settings);

		if(!is_dir($outputDir)) {
			DataManager::createDir($outputDir);
		}

		$app    = Application::instance();
		$loader = new DbSqlLoader($app->database(), $app->dataLoader());

		SqlExporter::setConfig($settings);
		SqlExporter::setDbSqlLoader($loader);

		$dbType    = SqlExporter::detectDatabaseType();
		$dbVersion = SqlExporter::getDatabaseVersion();
		$parsedTables = [];

		foreach($tables as $table) {
			$tableName      = (string) $table;
			$parser         = new SqlTableParser($tableName, $dbName, $loader);
			$parsedTables[] = $parser->getResult();
		}

		try {
			$parsedTables = SqlExporter::sortTablesByDependency($parsedTables);
		} catch(\RuntimeException $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 500);
		}

		$createStrings   = [];
		$createStrings[] = '-- ------------------------------------------------------ --';
		$createStrings[] = '--                                                        --';
		$createStrings[] = '-- ' . __('Экспорт базы данных при помощи DB Manager');
		$createStrings[] = '-- ' . __('Ссылка: https://devcraft.club/downloads/db-manager.30/');
		$createStrings[] = '--                                                        --';
		$createStrings[] = '-- ------------------------------------------------------ --';
		$createStrings[] = __('-- Дата создания: ') . date('r');
		$createStrings[] = __('-- Сервер: ') . DBHOST;
		$createStrings[] = __('-- Тип базы данных: ') . strtoupper($dbType) . ' ' . $dbVersion;
		$createStrings[] = '-- ------------------------------------------------------ --' . PHP_EOL;
		$createStrings   = array_merge($createStrings, SqlExporter::generateCompatibleHeaders());
		$createStrings[] = '--';
		$createStrings[] = __('-- База данных: ') . $dbName;
		$createStrings[] = '--';

		$createDbRows = $loader->loadSql('SHOW CREATE DATABASE `' . $dbName . '`');
		$createDbSql  = ($createDbRows[0]['Create Database'] ?? '') . ';';

		if(SqlExporter::supportsCreateOrReplace()) {
			$createStrings[] = str_replace('CREATE DATABASE', 'CREATE OR REPLACE DATABASE', $createDbSql);
		} else {
			$createStrings[] = str_replace('CREATE DATABASE', 'CREATE DATABASE IF NOT EXISTS', $createDbSql);
		}

		$createStrings[] = 'USE ' . $dbName . ';';
		$createStrings[] = '--' . PHP_EOL;

		$keyAfter = ($settings['key_export'] ?? 'down') === 'after';

		foreach($parsedTables as $table) {
			$createStrings[] = '--';
			$createStrings[] = __('-- Таблица: ') . $table->getName();
			$createStrings[] = '--';

			$tableSql = $table->generateSql($keyAfter);
			$tableSql = SqlExporter::fixSqlCompatibility($tableSql);

			$createStrings[] = $tableSql;
		}

		if(($settings['key_export'] ?? 'down') === 'down') {
			foreach($parsedTables as $table) {
				$indexes = [];

				foreach($table->getIndexes() as $index) {
					$indexSql = SqlExporter::fixSqlCompatibility($index->generateSql());

					if($indexSql !== '') {
						$indexes[] = $indexSql;
					}
				}

				if(count($indexes) > 0) {
					$createStrings[] = '--';
					$createStrings[] = __('-- Ключи для таблицы: ') . $table->getName();
					$createStrings[] = '--';
					$createStrings[] = implode(PHP_EOL, $indexes);
				}
			}

			$createStrings[] = PHP_EOL;
		}

		$createStrings[] = PHP_EOL;
		$groupValues     = ($settings['values_export_type'] ?? 'group') === 'group';

		foreach($parsedTables as $table) {
			$tableValues = $table->getSqlValues($groupValues);

			if($tableValues !== '' && $tableValues !== '\n') {
				$createStrings[] = '--';
				$createStrings[] = __('-- Данные для таблицы: ') . $table->getName();
				$createStrings[] = '--';
				$createStrings[] = $tableValues . PHP_EOL;
			}
		}

		$createStrings[] = PHP_EOL;
		$createStrings   = array_merge($createStrings, SqlExporter::generateFooter());

		$sqlFileName = $dbName . '_' . (new DateTime())->format('Y_m_d_H_i_s') . '_' . count($parsedTables) . '_tables';
		$sqlFile     = DataManager::joinPaths($outputDir, "{$sqlFileName}.sql");

		file_put_contents(
			$sqlFile,
			implode(PHP_EOL, array_filter($createStrings, static fn($sql) => $sql !== '')),
			LOCK_EX,
		);

		$zipMode = $settings['zip_data'] ?? 'raw';

		if($zipMode === 'zip') {
			$zip         = new ZipArchive();
			$zipFileName = str_replace('.sql', '.zip', $sqlFile);

			if($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
				return JsonResponse::fail(__('Ошибка'), __('Архивация архива не удалась!'), 'validation', 500);
			}

			$zip->addFile($sqlFile, "{$sqlFileName}.sql");
			$zip->close();
		}

		if($zipMode === 'bzip2') {
			$bzipFileName = str_replace('.sql', '.bz2', $sqlFile);
			$fileContents = file_get_contents($sqlFile);

			if($fileContents === false || file_put_contents($bzipFileName, bzcompress($fileContents, 9)) === false) {
				return JsonResponse::fail(__('Ошибка'), __('BZIP2 сжатие файла не удалось!'), 'validation', 500);
			}
		}

		if(!empty($settings['export_to_telegram'])) {
			try {
				$extension = match ($zipMode) {
					'zip'   => 'zip',
					'bzip2' => 'bz2',
					default => 'sql',
				};
				$filePath   = DataManager::joinPaths($settings['export_path'] ?? BackupPathHelper::DEFAULT_EXPORT_PATH, "{$sqlFileName}.{$extension}");
				$fileToSend = new \CURLFile(DataManager::joinPaths(ROOT_DIR, $filePath));
				$tgUrl      = "https://api.telegram.org/bot{$settings['tg_token']}/sendDocument";

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $tgUrl);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, [
					'chat_id'  => (int) $settings['tg_chat'],
					'document' => $fileToSend,
					'caption'  => __('Экспортированная база данных') . ": ({$dbType}): {$sqlFileName}",
				]);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				$response = curl_exec($ch);
				$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

				if($response === false || $httpCode !== 200) {
					$errorMsg = curl_error($ch);
					curl_close($ch);

					throw new \Exception(__('Ошибка при отправке файла в Telegram: ') . $errorMsg);
				}

				curl_close($ch);
			} catch(\Throwable $e) {
				return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 500);
			}
		}

		if($zipMode !== 'raw') {
			unlink($sqlFile);
		}

		return JsonResponse::toast(__('Создание резервной копии завершено!'), [
			'file' => "{$sqlFileName}." . ($zipMode === 'raw' ? 'sql' : ($zipMode === 'zip' ? 'zip' : 'bz2')),
		]);
	}

}
