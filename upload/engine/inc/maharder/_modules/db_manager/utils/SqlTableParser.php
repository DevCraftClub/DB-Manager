<?php

declare(strict_types=1);


class SqlTableParser {
	use DataLoader;

	private ParsedTable $tableData;

	/**
	 * @throws JsonException
	 */
	public function __construct(private string $table, private string $schema) {
		$this->tableData = new ParsedTable($this->table);
		$this->parse();
		$this->parseValues();
	}

	/**
	 * @throws JsonException
	 */
	public function parse(): void {
		$this->parseColumns($this->load_data(str_replace($this->getPrefix() . '_', '', $this->table), [
			'sql' => "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_DEFAULT, EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$this->schema}' AND TABLE_NAME = '{$this->table}';"
		]));

		$this->parseIndex($this->load_data(str_replace($this->getPrefix() . '_', '', $this->table), [
			'sql' => <<<SQL
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
		]));

		$collation = $this->load_data(str_replace($this->getPrefix() . '_', '', $this->table), [
			'sql' => <<<SQL
SELECT TABLE_COLLATION, ENGINE
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_NAME = '{$this->table}';
SQL
		]);

		$tableCollation = isset($collation[0]['TABLE_COLLATION']) ? $collation[0]['TABLE_COLLATION'] : COLLATE . '_general_ci';
		$tableEngine = isset($collation[0]['ENGINE']) ? $collation[0]['ENGINE'] : 'InnoDB';

		$charset = $this->load_data(str_replace($this->getPrefix() . '_', '', $this->table), [
			'sql' => <<<SQL
SELECT CHARACTER_SET_NAME
FROM INFORMATION_SCHEMA.COLLATIONS
WHERE COLLATION_NAME = (
    SELECT TABLE_COLLATION
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME = '{$this->table}'
    AND TABLE_SCHEMA = '{$this->schema}'
);
SQL
		]);

		$tableCharset = isset($charset[0]['CHARACTER_SET_NAME']) ? $charset[0]['CHARACTER_SET_NAME'] : COLLATE ;

		$this->tableData->setCollation($tableCollation);
		$this->tableData->setEngine($tableEngine);
		$this->tableData->setCharset($tableCharset);
	}

	private function parseColumns(array $columns): void {
		foreach ($columns as $column) {
			$tableColumn = new TableColumn($column['COLUMN_NAME'], $column['DATA_TYPE'], $column['CHARACTER_MAXIMUM_LENGTH'], $column['IS_NULLABLE'], $column['COLUMN_DEFAULT'], $column['EXTRA']);
			$tableColumn->setIsPrimary($column['EXTRA'] == 'auto_increment');
			$this->tableData->setColumns($tableColumn);
		}

	}

	private function parseIndex(array $keys): void {
		$parsed = [];

		foreach ($keys as $index) {
			if (!in_array($index['CONSTRAINT_NAME'], $parsed)) {
				$tableIndex = new TableIndex(
					$index['CONSTRAINT_NAME'],
					$index['TABLE_NAME'],
					$index['COLUMN_NAME'],
					$index['REFERENCED_TABLE_NAME'],
					$index['REFERENCED_COLUMN_NAME'],
					$index['NON_UNIQUE']
				);
				if ($tableIndex->isForeignKey()) {
					$this->tableData->setParent($tableIndex->getReferenceTable());
					$rules = $this->load_data(str_replace($this->getPrefix() . '_', '', $this->table), [
						'sql' => <<<SQL
SELECT UPDATE_RULE, DELETE_RULE
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
WHERE  REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME = '{$tableIndex->getName()}';
SQL
					]);

					$onUpdate = $rules[0]['UPDATE_RULE'] ?? null;
					$onDelete = $rules[0]['DELETE_RULE'] ?? null;

					$tableIndex->setOnUpdate($onUpdate);
					$tableIndex->setOnDelete($onDelete);
				}
				$this->tableData->setIndexes($tableIndex);
				$parsed[] = $tableIndex->getName();
			}
		}

		$indexes = $this->load_data(str_replace($this->getPrefix() . '_', '', $this->table), [
			'sql' => <<<SQL
SHOW INDEX FROM {$this->table}
SQL
		]);

		foreach ($indexes as $idx => $index) {
			if (!in_array($index['Key_name'], $parsed)) {

				$nextId = $idx + 1;
				$next = $indexex[$nextId] ?? false;

				$tableIndex = new TableIndex($index['Key_name'], $index['Table'], $index['Column_name']);
				$tableIndex->setType($index['Index_type']);

				while ($next) {
					if ($next['Key_name'] == $index['Key_name']) {
						$tableIndex->setColumn($next['Index_type']);
						$nextId++;
						$next = $indexex[$nextId] ?? false;
					} else {
						$next = false;
					}
				}

				$parsed[] = $index['Key_name'];
			}
		}
	}

	public function getResult(): ParsedTable {
		return $this->tableData;
	}

	/**
	 * @throws JsonException
	 */
	private function parseValues(): void {
		$data = $this->load_data(str_replace($this->getPrefix() . '_', '', $this->table), [
			'table' => str_replace($this->getPrefix() . '_', '', $this->table)
		]);

		foreach ($data as $row) {
			$values = [];

			foreach ($this->tableData->getColumns() as $column) {
				$values[] = $row[$column->getName()];
			}
			$this->tableData->setValues($values);
		}
	}
}
