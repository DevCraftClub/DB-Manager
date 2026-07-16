<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Services;

use JsonException;

/**
 * Парсер структуры и данных SQL-таблицы для экспорта.
 */
final class SqlTableParser {

	private ParsedTable $tableData;

	public function __construct(
		private readonly string      $table,
		private readonly string      $schema,
		private readonly DbSqlLoader $loader,
	) {
		$this->tableData = new ParsedTable($this->table);
		$this->parse();
		$this->parseValues();
	}

	public function parse(): void {
		$logical = $this->loader->logicalTableName($this->table);

		$this->parseColumns($this->loader->loadSql(
			"SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_DEFAULT, EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$this->schema}' AND TABLE_NAME = '{$this->table}';",
		));

		$this->parseIndex($this->loader->loadSql(<<<SQL
SELECT distinct k.CONSTRAINT_NAME,
                k.TABLE_NAME,
                k.COLUMN_NAME,
                k.REFERENCED_TABLE_NAME,
                k.REFERENCED_COLUMN_NAME,
                s.NON_UNIQUE
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
         LEFT JOIN INFORMATION_SCHEMA.STATISTICS s
                   ON k.TABLE_NAME = s.TABLE_NAME
                       AND k.CONSTRAINT_NAME = s.INDEX_NAME
WHERE k.TABLE_NAME = '{$this->table}' and k.TABLE_SCHEMA = '{$this->schema}'
SQL
		));

		$collation = $this->loader->loadSql(<<<SQL
SELECT TABLE_COLLATION, ENGINE
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_NAME = '{$this->table}';
SQL
		);

		$defaultCollation = defined('COLLATE') ? COLLATE . '_general_ci' : 'utf8mb4_general_ci';
		$tableCollation   = $collation[0]['TABLE_COLLATION'] ?? $defaultCollation;
		$tableEngine      = $collation[0]['ENGINE'] ?? 'InnoDB';

		$charset = $this->loader->loadSql(<<<SQL
SELECT CHARACTER_SET_NAME
FROM INFORMATION_SCHEMA.COLLATIONS
WHERE COLLATION_NAME = (
    SELECT TABLE_COLLATION
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME = '{$this->table}'
    AND TABLE_SCHEMA = '{$this->schema}'
);
SQL
		);

		$defaultCharset = defined('COLLATE') ? COLLATE : 'utf8mb4';
		$tableCharset   = $charset[0]['CHARACTER_SET_NAME'] ?? $defaultCharset;

		$this->tableData->setCollation($tableCollation);
		$this->tableData->setEngine($tableEngine);
		$this->tableData->setCharset($tableCharset);
	}

	/**
	 * @param array<int, array<string, mixed>> $columns
	 */
	private function parseColumns(array $columns): void {
		foreach($columns as $column) {
			$tableColumn = new TableColumn(
				(string) $column['COLUMN_NAME'],
				(string) $column['DATA_TYPE'],
				$column['CHARACTER_MAXIMUM_LENGTH'],
				(string) $column['IS_NULLABLE'],
				$column['COLUMN_DEFAULT'],
				$column['EXTRA'] !== null ? (string) $column['EXTRA'] : null,
			);
			$tableColumn->setIsPrimary(($column['EXTRA'] ?? '') === 'auto_increment');
			$this->tableData->setColumns($tableColumn);
		}
	}

	/**
	 * @param array<int, array<string, mixed>> $keys
	 */
	private function parseIndex(array $keys): void {
		$parsed = [];

		foreach($keys as $index) {
			if(!in_array($index['CONSTRAINT_NAME'], $parsed, true)) {
				$tableIndex = new TableIndex(
					(string) $index['CONSTRAINT_NAME'],
					(string) $index['TABLE_NAME'],
					(string) $index['COLUMN_NAME'],
					$index['REFERENCED_TABLE_NAME'] !== null ? (string) $index['REFERENCED_TABLE_NAME'] : null,
					$index['REFERENCED_COLUMN_NAME'] !== null ? (string) $index['REFERENCED_COLUMN_NAME'] : null,
					$index['NON_UNIQUE'],
				);

				if($tableIndex->isForeignKey()) {
					$this->tableData->setParent($tableIndex->getReferenceTable());
					$rules = $this->loader->loadSql(<<<SQL
SELECT UPDATE_RULE, DELETE_RULE
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
WHERE  REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME = '{$tableIndex->getName()}';
SQL
					);

					$tableIndex->setOnUpdate($rules[0]['UPDATE_RULE'] ?? null);
					$tableIndex->setOnDelete($rules[0]['DELETE_RULE'] ?? null);
				}

				$this->tableData->setIndexes($tableIndex);
				$parsed[] = $tableIndex->getName();
			}
		}

		$indexes = $this->loader->loadSql('SHOW INDEX FROM ' . $this->table);

		foreach($indexes as $idx => $index) {
			if(in_array($index['Key_name'], $parsed, true)) {
				continue;
			}

			$nextId     = $idx + 1;
			$next       = $indexes[$nextId] ?? false;
			$tableIndex = new TableIndex(
				(string) $index['Key_name'],
				(string) $index['Table'],
				(string) $index['Column_name'],
			);
			$tableIndex->setType((string) $index['Index_type']);

			while($next) {
				if($next['Key_name'] === $index['Key_name']) {
					$tableIndex->setColumn((string) $next['Column_name']);
					$nextId++;
					$next = $indexes[$nextId] ?? false;
				} else {
					$next = false;
				}
			}

			$this->tableData->setIndexes($tableIndex);
			$parsed[] = $index['Key_name'];
		}
	}

	/**
	 * @throws JsonException
	 */
	private function parseValues(): void {
		$data = $this->loader->loadTable($this->table);

		foreach($data as $row) {
			$values = [];

			foreach($this->tableData->getColumns() as $column) {
				$values[] = $row[$column->getName()] ?? null;
			}

			$this->tableData->setValues($values);
		}
	}

	public function getResult(): ParsedTable {
		return $this->tableData;
	}

}
