<?php

class TableIndex {
	private string  $name;
	private string  $table;
	private array   $column           = [];
	private ?string $reference_table  = null;
	private ?string $reference_column = null;
	private bool    $isUnique         = false;
	private ?string $onUpdate         = null;
	private ?string $onDelete         = null;
	private ?string $type             = null;

	/**
	 * @param string               $name
	 * @param string               $table
	 * @param string|array         $column
	 * @param string|null          $reference_table
	 * @param string|null          $reference_column
	 * @param bool|int|string|null $unique
	 */
	public function __construct(string $name, string $table, string|array $column, ?string $reference_table = null, ?string $reference_column = null, bool|int|string|null $unique = false) {
		$this->setName($name);
		$this->setTable($table);
		$this->setColumn($column);
		$this->setReferenceTable($reference_table);
		$this->setReferenceColumn($reference_column);
		$this->setIsUnique($unique);
	}

	public function getName(): string {
		return $this->name;
	}

	public function setName(string $name): TableIndex {
		$this->name = $name;
		return $this;
	}

	public function getTable(): string {
		return $this->table;
	}

	public function setTable(string $table): TableIndex {
		$this->table = $table;
		return $this;
	}

	public function getColumn(): string {
		return implode(',', array_map(fn($column) => "`{$column}`", $this->column));
	}

	public function setColumn(string|array $column): TableIndex {
		if (is_array($column)) {
			foreach ($column as $col) $this->setColumn($col);
		} else {
			$this->column[] = $column;
		}
		return $this;
	}

	public function getReferenceTable(): ?string {
		return $this->reference_table;
	}

	public function setReferenceTable(?string $reference_table): TableIndex {
		$this->reference_table = $reference_table;
		return $this;
	}

	public function getReferenceColumn(): ?string {
		return $this->reference_column;
	}

	public function setReferenceColumn(?string $reference_column): TableIndex {
		$this->reference_column = $reference_column;
		return $this;
	}

	public function isForeignKey(): bool {
		return $this->getReferenceTable() !== null && $this->getReferenceColumn() !== null;
	}

	public function isUnique(): bool {
		return $this->isUnique;
	}

	public function setIsUnique(bool|int|string|null $isUnique): TableIndex {
		$this->isUnique = $isUnique && !filter_var($isUnique, FILTER_VALIDATE_BOOLEAN);
		return $this;
	}

	public function getOnUpdate(): ?string {
		return strtoupper($this->onUpdate);
	}

	public function setOnUpdate(?string $onUpdate): TableIndex {
		$this->onUpdate = $onUpdate;
		return $this;
	}

	public function getOnDelete(): ?string {
		return strtoupper($this->onDelete);
	}

	public function setOnDelete(?string $onDelete): TableIndex {
		$this->onDelete = $onDelete;
		return $this;
	}

	public function getType(): ?string {
		return $this->type;
	}

	public function setType(?string $type): TableIndex {
		$this->type = $type;
		return $this;
	}

	public function generateSql(): string {
		if ($this->getName() === 'PRIMARY') {
			// Пустая строка для PRIMARY ключей
			return '';
		}

		if ($this->isForeignKey()) {
			// Генерация SQL для внешнего ключа
			return $this->generateForeignKeySql();
		}

		if ($this->isUnique()) {
			// Генерация SQL для уникальных индексов
			return $this->generateUniqueIndexSql();
		}

		// Генерация стандартного индекса (по типу)
		return $this->generateStandardIndexSql();
	}

	private function generateStandardIndexSql(): string {
		$type = strtolower($this->getType());
		if ($type === 'fulltext') {
			return "CREATE OR REPLACE FULLTEXT INDEX {$this->getName()} ON `{$this->getTable()}` ({$this->getColumn()});";
		}

		return "CREATE OR REPLACE INDEX {$this->getName()} ON `{$this->getTable()}` ({$this->getColumn()});";
	}

	private function generateForeignKeySql(): string {
		return "ALTER TABLE `{$this->getTable()}` ADD CONSTRAINT {$this->getName()} FOREIGN KEY ({$this->getColumn()}) " . "REFERENCES `{$this->getReferenceTable()}` (`{$this->getReferenceColumn()}`) " . "ON UPDATE {$this->getOnUpdate()} ON DELETE {$this->getOnDelete()};";
	}

	private function generateUniqueIndexSql(): string {
		// Уникальность ключа для одной или нескольких колонок
		if (count($this->column) > 1) {
			return "ALTER TABLE `{$this->getTable()}` ADD CONSTRAINT {$this->getName()} UNIQUE ({$this->getColumn()});";
		}

		return "CREATE UNIQUE INDEX {$this->getName()} ON `{$this->getTable()}` ({$this->getColumn()});";
	}

}