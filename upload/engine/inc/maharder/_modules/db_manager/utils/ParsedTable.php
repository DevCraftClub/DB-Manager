<?php

class ParsedTable {
	private string $name;
	private array  $columns = [];
	private array  $indexes = [];
	private array  $parent  = [];
	private array  $values  = [];
	private string $collation;
	private string $charset;
	private string $engine;

	/**
	 * @param string            $name
	 * @param array|TableColumn $columns
	 * @param array|TableIndex  $indexes
	 * @param array|string|null $parent
	 */
	public function __construct(string $name, array|TableColumn $columns = [], array|TableIndex $indexes = [], array|string|null $parent = null) {
		$this->setName($name);
		$this->setColumns($columns);
		$this->setIndexes($indexes);
		$this->setParent($parent);
	}

	public function getName(): string {
		return $this->name;
	}

	public function setName(string $name): ParsedTable {
		$this->name = $name;
		return $this;
	}

	public function getColumns(): array {
		return $this->columns;
	}

	public function setColumns(array|TableColumn $columns): ParsedTable {
		if (is_array($columns)) {
			foreach ($columns as $column) $this->setColumns($column);
		} else {
			$this->columns[] = $columns;
		}
		return $this;
	}

	public function getIndexes(): array {
		return $this->indexes;
	}

	public function setIndexes(array|TableIndex $indexes): ParsedTable {
		if (is_array($indexes)) {
			foreach ($indexes as $index) $this->setIndexes($index);
		} else {
			$this->indexes[] = $indexes;
		}
		return $this;
	}

	public function getParent(): array {
		return $this->parent;
	}

	public function setParent(array|string|null $parent): ParsedTable {
		if ($parent) {
			if (is_array($parent)) {
				foreach ($parent as $p) $this->setParent($p);
			} else {
				$this->parent[] = $parent;
			}
		}

		return $this;
	}

	public function getCollation(): string {
		return $this->collation;
	}

	public function setCollation(string $collation): ParsedTable {
		$this->collation = $collation;
		return $this;
	}

	public function getCharset(): string {
		return $this->charset;
	}

	public function setCharset(string $charset): ParsedTable {
		$this->charset = $charset;
		return $this;
	}

	public function getEngine(): string {
		return $this->engine;
	}

	public function setEngine(string $engine): ParsedTable {
		$this->engine = $engine;
		return $this;
	}

	public function getValues(): array {
		return $this->values;
	}

	public function setValues(array $values): ParsedTable {
		$this->values[] = $values;
		return $this;
	}

	private function getColumnData(string $name): ?TableColumn {
		// Если columns — массив объектов TableColumn, а ключи не обработаны, можно искать через first(),
		// или вручную:
		foreach ($this->getColumns() as $column) {
			if ($column->getName() === str_replace("`", '', $name)) {
				return $column; // Получаем сразу целевой элемент
			}
		}

		return null; // Возвращаем null, если колонка не найдена
	}

	public function getSqlValues(bool $grouped = false): string {
		if (empty($this->getValues())) {
			return '';
		}

		$columns   = array_map(fn($column) => "`{$column->getName()}`", $this->getColumns());
		$colString = implode(', ', $columns);

		$valueStrings = array_map(
			fn($value) => $this->prepareRowValues($value, $columns),
			$this->getValues()
		);

		if ($grouped) {
			$valueString = implode(', ', $valueStrings);
			return "INSERT INTO `{$this->getName()}` ({$colString}) VALUES {$valueString};";
		}

		return implode(
			PHP_EOL,
			array_map(
				fn($row) => "INSERT INTO `{$this->getName()}` ({$colString}) VALUES {$row};",
				$valueStrings
			)
		);
	}

	private function prepareRowValues(array $value, array $columns): string {
		$values = array_map(
			fn($col, $idx) => $this->mapValueToSql($value[$idx], $this->getColumnData($col)->getType()),
			$columns,
			array_keys($columns)
		);

		return '(' . implode(', ', $values) . ')';
	}

	private function mapValueToSql(mixed $value, string $type): bool|int|float|string|null {
		if (is_null($value)) return 'NULL';
		return match ($type) {
			'int', 'mediumint', 'bigint', 'float', 'smallint', 'tinyint' => filter_var($value, FILTER_VALIDATE_INT),
			'bool', 'boolean'                                            => filter_var($value, FILTER_VALIDATE_BOOL),
			default                                                      => "'" . addslashes($value) . "'"
		};
	}

	public function generateSql(bool $indexes = false): string {
		// Использование HEREDOC для улучшения читаемости и снижения количества конкатенаций
		$sql = <<<SQL
DROP TABLE IF EXISTS `{$this->getName()}`;
CREATE OR REPLACE TABLE `{$this->getName()}` (
SQL;

		$columnsSql = array_map(
			fn($column) => "\t" . trim($column->generateSql()),
			$this->getColumns()
		);

		// Объединение колонок через implode для читаемости
		$sql .= PHP_EOL . implode("," . PHP_EOL, $columnsSql) . PHP_EOL;

		$sql .= <<<SQL
) ENGINE={$this->getEngine()} DEFAULT CHARSET={$this->getCharset()} COLLATE={$this->getCollation()};
SQL;

		// Если индексы включены, добавляем их обработку
		if ($indexes && !empty($this->getIndexes())) {
			$indexesSql = array_map(
				fn($index) => $index->generateSql(),
				$this->getIndexes()
			);

			$sql .= PHP_EOL . PHP_EOL . implode(PHP_EOL, $indexesSql) . PHP_EOL;
		}

		$sql .= PHP_EOL;

		return $sql;
	}

}