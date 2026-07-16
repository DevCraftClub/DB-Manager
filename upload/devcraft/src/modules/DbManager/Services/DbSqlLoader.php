<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Services;

use DevCraft\Core\Database\DatabaseGateway;
use DevCraft\Core\Support\DataLoaderService;

/**
 * Загрузка SQL-данных для экспорта: сырой SQL через Cycle и строки таблиц через DataLoaderService.
 */
final class DbSqlLoader {

	public function __construct(
		private readonly DatabaseGateway   $db,
		private readonly DataLoaderService $dataLoader,
	) {}

	/**
	 * Выполняет произвольный SELECT и возвращает строки результата.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function loadSql(string $sql): array {
		$statement = $this->db->query($sql);
		$rows      = [];

		while($row = $statement->fetch()) {
			if(is_array($row)) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	/**
	 * Загружает все строки таблицы по физическому имени (с префиксом DLE).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function loadTable(string $physicalTableName): array {
		return $this->dataLoader->loadData(['table' => $this->logicalTableName($physicalTableName)]);
	}

	/**
	 * Убирает префикс DLE из имени таблицы для DataLoaderService.
	 */
	public function logicalTableName(string $physicalTableName): string {
		$prefix = defined('PREFIX') ? PREFIX . '_' : '';

		if($prefix !== '' && str_starts_with($physicalTableName, $prefix)) {
			return substr($physicalTableName, strlen($prefix));
		}

		return $physicalTableName;
	}

}
