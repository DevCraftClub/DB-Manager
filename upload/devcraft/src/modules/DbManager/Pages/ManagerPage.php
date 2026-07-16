<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Pages;

use DevCraft\Core\Application;
use DevCraft\Core\Abstracts\AbstractPage;
use DevCraft\Core\Support\DataManager;
use DevCraft\Modules\DbManager\Services\DbSqlLoader;
use DevCraft\Modules\DbManager\Services\SqlTable;
use DevCraft\Modules\DbManager\Services\BackupPathHelper;

/**
 * Страница управления базой данных: список таблиц и резервных копий.
 */
final class ManagerPage extends AbstractPage {

	public function handle(): array {
		$pageName = __('Управление базой данных');

		$this->addBreadcrumb($pageName);

		$settings = DataManager::getConfig('db_manager');
		$app      = Application::instance();
		$loader   = new DbSqlLoader($app->database(), $app->dataLoader());
		$dbName   = DBNAME;
		$tables   = $loader->loadSql('SHOW TABLES');
		$tableId  = 'Tables_in_' . $dbName;
		$tableInfos = [];

		foreach($tables as $row) {
			$tableName = (string) ($row[$tableId] ?? '');

			if($tableName === '') {
				continue;
			}

			$entries = $loader->loadSql("SELECT COUNT(*) AS entry_count FROM `{$tableName}`;");
			$size    = $loader->loadSql(
				"SELECT TABLE_NAME, (DATA_LENGTH + INDEX_LENGTH) AS table_size_b FROM information_schema.TABLES WHERE TABLE_NAME = '{$tableName}';",
			);

			$tableInfos[] = new SqlTable(
				$tableName,
				(int) ($entries[0]['entry_count'] ?? 0),
				(int) ($size[0]['table_size_b'] ?? 0),
			);
		}

		$exportDir = BackupPathHelper::exportDir($settings);
		$files     = is_dir($exportDir)
			? DataManager::dirToArray($exportDir, '.htaccess', 'index.html', 'index.php')
			: [];
		$exported  = [];

		foreach($files as $file) {
			if(!is_string($file)) {
				continue;
			}

			$fileInfo = pathinfo($file);

			$exported[$fileInfo['basename']] = [
				'name' => $fileInfo['basename'],
				'ext'  => $fileInfo['extension'] ?? '',
				'path' => str_replace(ROOT_DIR, '', DataManager::joinPaths($settings['export_path'] ?? BackupPathHelper::DEFAULT_EXPORT_PATH, $file)),
			];
		}

		ksort($exported);

		return [
			'view' => 'dbmanager/manager.twig',
			'data' => [
				'page_title' => $pageName,
				'tables'     => $tableInfos,
				'exported'   => $exported,
			],
		];
	}

}
