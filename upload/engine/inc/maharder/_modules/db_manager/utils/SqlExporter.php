<?php

/**
 * Класс для экспорта SQL с поддержкой совместимости между разными версиями MySQL/MariaDB.
 *
 * Предоставляет функциональность для:
 * - Определения типа и версии базы данных
 * - Генерации совместимых SQL-заголовков и футеров
 * - Исправления синтаксиса SQL для обеспечения совместимости
 * - Сортировки таблиц с учетом зависимостей
 */

class SqlExporter {

	/**
	 * @var string|null Тип используемой базы данных (mysql или mariadb)
	 */
	private static $dbType;

	/**
	 * @var string|null Версия базы данных
	 */
	private static $dbVersion;

	/**
	 * @var MhAjax|null Экземпляр класса для выполнения AJAX-запросов
	 */
	private static ?MhAjax $mhAjax;

	/**
	 * @var array Конфигурационный массив
	 */
	private static array $config;


	/**
	 * Устанавливает конфигурационные параметры.
	 *
	 * @param array $config Массив конфигурации
	 * @return void
	 */
	public static function setConfig(array $config): void {
		self::$config = $config;
	}

	/**
	 * Устанавливает экземпляр MhAjax для выполнения запросов.
	 *
	 * @param MhAjax $mhAjax Экземпляр класса MhAjax
	 * @return void
	 */
	public static function setMhAjax(MhAjax $mhAjax): void {
		self::$mhAjax = $mhAjax;
	}

	/**
	 * Определяет тип используемой базы данных (MySQL или MariaDB).
	 *
	 * @throws Exception При ошибке выполнения запроса
	 * @return string Тип базы данных ('mysql' или 'mariadb')
	 */
	public static function detectDatabaseType(): string {
		if (is_null(self::$mhAjax)) {
			LogGenerator::generateLog(
				'SqlExporter',
				'detectDatabaseType',
				'\$mhAjax не был инициирован!',
				'crit',
			);
			die(__('\$mhAjax не был инициирован!'));
		}
		if (count(self::$config) === 0) {
			LogGenerator::generateLog(
				'SqlExporter',
				'detectDatabaseType',
				'\$config не был инициирован!',
				'crit',
			);
			die(__('\$config не был инициирован!'));
		}

		if (self::$dbType === NULL) {
			try {
				$versionResult = self::$mhAjax->load_data('version_check', [
					'sql' => "SELECT VERSION() as version, @@version_comment as comment",
				]);

				$version = $versionResult[0]['version'] ?? '';
				$comment = strtolower($versionResult[0]['comment'] ?? '');

				self::$dbVersion = $version;
				if (self::$config['export_compatibility'] === 'current') {
					if (stripos($version, 'mariadb') !== FALSE
					    || stripos($comment, 'mariadb') !== FALSE) {
						self::$dbType = 'mariadb';
					} else {
						self::$dbType = 'mysql';
					}
				} else {
					self::$dbType = 'mysql';
				}
			}
			catch (Exception $e) {
				self::$dbType    = 'mysql';
				self::$dbVersion = '5.7.0';
			}
		}

		return self::$dbType;
	}

	/**
	 * Возвращает версию базы данных.
	 *
	 * @return string Версия базы данных
	 */
	public static function getDatabaseVersion(): string {
		if (self::$dbVersion === NULL) {
			self::detectDatabaseType();
		}

		return self::$dbVersion;
	}

	/**
	 * Проверяет поддержку конструкции CREATE OR REPLACE.
	 *
	 * @return bool TRUE если поддерживается, FALSE в противном случае
	 */
	public static function supportsCreateOrReplace(): bool {
		$dbType  = self::detectDatabaseType();
		$version = self::getDatabaseVersion();

		if ($dbType === 'mariadb') {
			return version_compare($version, '10.0.8', '>=');
		}

		return FALSE;
	}

	/**
	 * Проверяет поддержку конструкции DROP INDEX IF EXISTS.
	 *
	 * @return bool TRUE если поддерживается, FALSE в противном случае
	 */
	public static function supportsDropIndexIfExists(): bool {
		$dbType  = self::detectDatabaseType();
		$version = self::getDatabaseVersion();

		if ($dbType === 'mariadb') {
			return version_compare($version, '10.1.4', '>=');
		} else {
			return version_compare($version, '5.7.0', '>=');
		}
	}

	/**
	 * Генерирует SQL-заголовки с учетом совместимости.
	 *
	 * @return array Массив SQL-заголовков
	 */
	public static function generateCompatibleHeaders(): array {
		$dbType = self::detectDatabaseType();

		$headers = [
			'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
			'SET AUTOCOMMIT = 0;',
			'START TRANSACTION;',
		];

		if ($dbType === 'mariadb') {
			$headers = array_merge($headers, [
				"/*!100000 SET NAMES UTF8MB4 */;",
				"/*!100101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;",
				"/*!100101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;",
				"/*!100101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;",
			]);
		} else {
			$headers = array_merge($headers, [
				"/*!40030 SET NAMES UTF8MB4 */;",
				"/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;",
				"/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;",
				"/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;",
			]);
		}

		$headers = array_merge($headers, [
			"/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;",
			"/*!40103 SET TIME_ZONE='" . date('P') . "' */;",
			"/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;",
			"/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;",
			"/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;",
			"/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;",
		]);

		return $headers;
	}

	/**
	 * Генерирует SQL-футер для восстановления первоначальных настроек.
	 *
	 * @return array Массив SQL-команд футера
	 */
	public static function generateFooter(): array {
		$footer = [];

		$footer[]
			      = "-- ------------------------------------------------------ --";
		$footer[] = "-- " . __("Восстановление прежних данных");
		$footer[]
			      = "-- ------------------------------------------------------ --";
		$footer[]
			      = "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;";
		$footer[]
			      = "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;";
		$footer[]
			      = "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
		$footer[] = "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;";
		$footer[] = "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;";
		$footer[]
			      = "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;";
		$footer[] = "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;";
		$footer[] = "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;";
		$footer[] = "COMMIT;";

		return $footer;
	}

	/**
	 * Исправляет синтаксис CREATE OR REPLACE TABLE для обеспечения совместимости.
	 *
	 * @param string $sql SQL-запрос
	 * @return string Исправленный SQL-запрос
	 */
	public static function fixCreateOrReplaceTable(string $sql): string {
		if (!self::supportsCreateOrReplace()) {
			$pattern = '/CREATE\s+OR\s+REPLACE\s+TABLE\s+(`?\w+`?)/i';
			if (preg_match($pattern, $sql, $matches)) {
				$tableName  = $matches[1];

				return preg_replace($pattern,
					"CREATE TABLE $tableName",
					$sql);
			}
		}

		return $sql;
	}

	/**
	 * Исправляет определения SET/ENUM на основе существующих данных.
	 *
	 * @param string $sql SQL-запрос
	 * @return string Исправленный SQL-запрос
	 */
	public static function fixSetEnumDefinitions(string $sql): string {
		$sql = preg_replace_callback(
			'/`(\w+)`\s+(set|enum)\((\d+)\)/i',
			function($matches) {
				$columnName = $matches[1];
				$type       = strtoupper($matches[2]);
				$number     = (int) $matches[3];

				$values = self::generateValuesForColumn($columnName,
					$type,
					$number);

				return "`{$columnName}` {$type}(" . implode(',', $values) . ")";
			},
			$sql,
		);

		return $sql;
	}

	/**
	 * Генерирует значения для SET/ENUM столбцов на основе данных в базе.
	 *
	 * @param string $columnName Имя столбца
	 * @param string $type Тип данных (SET или ENUM)
	 * @param int $maxCount Максимальное количество значений
	 * @return array Массив значений
	 */
	private static function generateValuesForColumn(
		string $columnName,
		string $type,
		int $maxCount,
	): array {
		try {
			$existingColumns = self::$mhAjax->load_data('existing_columns', [
				'sql' => "SELECT table_name, column_type 
						  FROM information_schema.columns 
						  WHERE table_schema = DATABASE() 
						  AND column_name = '{$columnName}'
						  AND (column_type LIKE 'enum%' OR column_type LIKE 'set%')
						  LIMIT 1",
			]);

			if (!empty($existingColumns)) {
				$columnType = $existingColumns[0]['column_type'];

				preg_match_all("/'([^']+)'/", $columnType, $matches);

				if (!empty($matches[1])) {
					$values = [];
					foreach ($matches[1] as $value) {
						$values[] = "'{$value}'";
						if (count($values) >= $maxCount) {
							break;
						}
					}

					return $values;
				}
			}

			$tablesWithColumn = self::$mhAjax->load_data('tables_with_column', [
				'sql' => "SELECT table_name 
						  FROM information_schema.columns 
						  WHERE table_schema = DATABASE() 
						  AND column_name = ?
						  LIMIT 5",
			], [$columnName]);

			$allValues = [];
			foreach ($tablesWithColumn as $tableRow) {
				$tableName = $tableRow['table_name'];

				try {
					$distinctValues
						= self::$mhAjax->load_data('distinct_values', [
						'sql' => "SELECT DISTINCT `{$columnName}` as value 
								  FROM `{$tableName}` 
								  WHERE `{$columnName}` IS NOT NULL 
								  AND `{$columnName}` != '' 
								  LIMIT ?",
					], [$maxCount]);

					foreach ($distinctValues as $row) {
						$value = trim($row['value']);

						if ($type === 'SET') {
							$setValues = array_map('trim',
								explode(',', $value));
							foreach ($setValues as $setValue) {
								if (!empty($setValue)
								    && !in_array("'{$setValue}'", $allValues)) {
									$allValues[] = "'{$setValue}'";
								}
							}
						} else {
							if (!empty($value)
							    && !in_array("'{$value}'",
									$allValues)) {
								$allValues[] = "'{$value}'";
							}
						}

						if (count($allValues) >= $maxCount) {
							break 2;
						}
					}
				}
				catch (Exception $e) {
					continue;
				}
			}

			if (!empty($allValues)) {
				return array_slice(array_unique($allValues), 0, $maxCount);
			}
		}
		catch (Exception $e) {
			LogGenerator::generateLog('SqlExporter',
				'generateValuesForColumn',
				[
					__("Ошибка вывода данных для Enum столбца {$columnName}"),
					$e->getMessage(),
				]);
		}

		return [];
	}

	/**
	 * Исправляет синтаксис CREATE OR REPLACE INDEX.
	 *
	 * @param string $sql SQL-запрос
	 * @return string Исправленный SQL-запрос
	 */
	public static function fixCreateOrReplaceIndex(string $sql): string {
		$pattern
			= '/CREATE\s+OR\s+REPLACE\s+INDEX\s+(`?\w+`?)\s+ON\s+(`?\w+`?)\s*\(([^)]+)\)/i';

		if (preg_match($pattern, $sql, $matches)) {
			$indexName = $matches[1];
			$tableName = $matches[2];
			$columns   = $matches[3];

			if (self::supportsDropIndexIfExists()) {
				return "DROP INDEX IF EXISTS {$indexName} ON {$tableName};\nCREATE INDEX {$indexName} ON {$tableName} ({$columns});";
			} else {
				return __("-- Проверка на существование индекса") . PHP_EOL .
				       "/*!50700 DROP INDEX IF EXISTS {$indexName} ON {$tableName} */;" . PHP_EOL.
				       "CREATE INDEX {$indexName} ON {$tableName} ({$columns});";
			}
		}

		return $sql;
	}

	/**
	 * Применяет все исправления совместимости к SQL-запросу.
	 *
	 * @param string $sql Исходный SQL-запрос
	 * @return string Исправленный SQL-запрос
	 */
	public static function fixSqlCompatibility(string $sql): string {
		$sql = self::fixCreateOrReplaceTable($sql);
		$sql = self::fixSetEnumDefinitions($sql);
		$sql = self::fixCreateOrReplaceIndex($sql);

		$sql = self::fixCurrentTimestampSyntax($sql);

		return self::fixCharsetCollation($sql);
	}

	/**
	 * Исправляет синтаксис CURRENT_TIMESTAMP().
	 *
	 * @param string $sql SQL-запрос
	 * @return string Исправленный SQL-запрос
	 */
	private static function fixCurrentTimestampSyntax(string $sql): string {
		return preg_replace('/current_timestamp\(\)/i',
			'CURRENT_TIMESTAMP',
			$sql);
	}

	/**
	 * Добавляет явное указание COLLATE для кодировок UTF8.
	 *
	 * @param string $sql SQL-запрос
	 * @return string Исправленный SQL-запрос
	 */
	private static function fixCharsetCollation(string $sql): string {
		if (stripos($sql, 'utf8mb4') !== FALSE
		    && stripos($sql, 'COLLATE') === FALSE) {
			$sql = str_replace('utf8mb4',
				'utf8mb4 COLLATE utf8mb4_general_ci',
				$sql);
		}

		if (stripos($sql, 'utf8') !== FALSE
		    && stripos($sql, 'COLLATE') === FALSE) {
			$sql = str_replace('utf8mb4', 'utf8 COLLATE utf8_general_ci', $sql);
		}

		return $sql;
	}

	/**
	 * Сортирует таблицы так, чтобы если у таблицы есть родитель (зависимость),
	 * то она располагалась в массиве после всех своих родителей.
	 *
	 * @param   ParsedTable[]  $tables  Массив объектов ParsedTable.
	 *
	 * @return ParsedTable[] Отсортированный массив.
	 * @throws Exception Если обнаружена циклическая зависимость.
	 */
	public static function sortTablesByDependency(array $tables): array {
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
			$current  = array_shift($queue);
			$sorted[] = $current;

			// Для каждой таблицы, которая зависит от текущей, уменьшаем in-degree
			foreach ($tables as $table) {
				// Если текущая таблица является родителем для $table
				if (in_array($current->getName(), $table->getParent(), TRUE)) {
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

}