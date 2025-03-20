<?php

/**
 * Класс для работы с индексами таблиц базы данных.
 *
 * Позволяет создавать стандартные, уникальные индексы и внешние ключи,
 * а также генерировать соответствующий SQL-код для создания индекса
 * в базе данных.
 **/
class TableIndex {
	/**
	 * Имя индекса таблицы.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Имя таблицы, для которой создается индекс.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Список колонок, на которые указывает индекс.
	 *
	 * @var array
	 */
	private array $column = [];

	/**
	 * Имя таблицы, на которую ссылается внешний ключ.
	 *
	 * @var string|null
	 */
	private ?string $reference_table = null;

	/**
	 * Имя колонки, на которую ссылается внешний ключ.
	 *
	 * @var string|null
	 */
	private ?string $reference_column = null;

	/**
	 * Флаг, определяющий, является ли индекс уникальным.
	 *
	 * @var bool
	 */
	private bool $isUnique = false;

	/**
	 * Условие, определяющее действие при обновлении связанных данных.
	 *
	 * @var string|null
	 */
	private ?string $onUpdate = null;

	/**
	 * Условие, определяющее действие при удалении связанных данных.
	 *
	 * @var string|null
	 */
	private ?string $onDelete = null;

	/**
	 * Тип индекса.
	 *
	 * @var string|null
	 */
	private ?string $type = null;

	/**
	 * Конструктор класса TableIndex.
	 *
	 * Инициализирует объект индекса таблицы, устанавливая его имя, таблицу,
	 * колонки, ссылочную таблицу и колонку, а также флаг уникальности.
	 *
	 * @param string               $name             Имя индекса.
	 * @param string               $table            Таблица, к которой относится индекс.
	 * @param string|array         $column           Колонка (или список колонок) для индекса.
	 * @param string|null          $reference_table  Таблица, на которую ссылается внешний ключ (если есть).
	 * @param string|null          $reference_column Колонка, на которую ссылается внешний ключ (если есть).
	 * @param bool|int|string|null $unique           Указывает, является ли индекс уникальным.
	 *
	 * @see TableIndex::setName()
	 * @see TableIndex::setTable()
	 * @see TableIndex::setColumn()
	 * @see TableIndex::setReferenceTable()
	 * @see TableIndex::setReferenceColumn()
	 * @see TableIndex::setIsUnique()
	 */
	public function __construct(string $name, string $table, string|array $column, ?string $reference_table = null, ?string $reference_column = null, bool|int|string|null $unique = false) {
		$this->setName($name);
		$this->setTable($table);
		$this->setColumn($column);
		$this->setReferenceTable($reference_table);
		$this->setReferenceColumn($reference_column);
		$this->setIsUnique($unique);
	}

	/**
	 * Генерирует SQL-запрос для создания индекса.
	 *
	 * Метод определяет тип индекса, основываясь на свойстве объекта, и возвращает SQL-запрос
	 * для его создания. Если индекс является первичным ключом, возвращается пустая строка.
	 *
	 * @return string SQL-запрос для создания индекса
	 *
	 * @see TableIndex::getName()
	 * @see TableIndex::isForeignKey()
	 * @see TableIndex::generateForeignKeySql()
	 * @see TableIndex::isUnique()
	 * @see TableIndex::generateUniqueIndexSql()
	 * @see TableIndex::generateStandardIndexSql()
	 */
	public function generateSql(): string {
		if ($this->getName() === 'PRIMARY') {
			return '';
		}

		if ($this->isForeignKey()) {
			return $this->generateForeignKeySql();
		}

		if ($this->isUnique()) {
			return $this->generateUniqueIndexSql();
		}

		return $this->generateStandardIndexSql();
	}

	/**
	 * Возвращает имя текущего индекса.
	 *
	 * @return string Имя индекса.
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Устанавливает имя индекса.
	 *
	 * @param string $name Имя индекса.
	 *
	 * @return TableIndex Текущий объект для цепочки вызовов.
	 */
	public function setName(string $name): TableIndex {
		$this->name = $name;
		return $this;
	}

	/**
	 * Проверяет, является ли индекс внешним ключом.
	 *
	 * Внешний ключ определяется наличием ссылочной таблицы и колонки.
	 *
	 * @return bool Возвращает true, если индекс является внешним ключом, иначе false.
	 *
	 * @see TableIndex::getReferenceTable()
	 * @see TableIndex::getReferenceColumn()
	 */
	public function isForeignKey(): bool {
		return $this->getReferenceTable() !== null && $this->getReferenceColumn() !== null;
	}

	/**
	 * Возвращает имя таблицы, на которую ссылается внешний ключ.
	 *
	 * @return string|null Имя ссылочной таблицы или null, если ссылка отсутствует.
	 */
	public function getReferenceTable(): ?string {
		return $this->reference_table;
	}

	/**
	 * Устанавливает имя таблицы, на которую ссылается внешний ключ.
	 *
	 * @param string|null $reference_table Имя ссылочной таблицы.
	 *
	 * @return TableIndex Текущий объект для цепочки вызовов.
	 */
	public function setReferenceTable(?string $reference_table): TableIndex {
		$this->reference_table = $reference_table;
		return $this;
	}

	/**
	 * Возвращает имя колонки, на которую ссылается внешний ключ.
	 *
	 * @return string|null Имя ссылочной колонки или null, если ссылка отсутствует.
	 */
	public function getReferenceColumn(): ?string {
		return $this->reference_column;
	}

	/**
	 * Устанавливает имя колонки, на которую ссылается внешний ключ.
	 *
	 * @param string|null $reference_column Имя ссылочной колонки.
	 *
	 * @return TableIndex Текущий объект для цепочки вызовов.
	 */
	public function setReferenceColumn(?string $reference_column): TableIndex {
		$this->reference_column = $reference_column;
		return $this;
	}

	/**
	 * Генерирует SQL-запрос для создания внешнего ключа.
	 *
	 * Формируется запрос вида:
	 * ALTER TABLE `таблица` ADD CONSTRAINT имя FOREIGN KEY (колонка[и]) REFERENCES `ссылка_на_таблицу`
	 * (`ссылка_на_колонку[и]`) ON UPDATE действие ON DELETE действие;
	 *
	 * @return string Сформированный SQL-запрос для внешнего ключа.
	 *
	 * @see TableIndex::getTable() Для получения имени таблицы.
	 * @see TableIndex::getName() Для получения имени ограничения.
	 * @see TableIndex::getColumn() Для получения колонок внешнего ключа.
	 * @see TableIndex::getReferenceTable() Для получения таблицы, на которую ссылается внешний ключ.
	 * @see TableIndex::getReferenceColumn() Для получения колонок, на которые ссылается внешний ключ.
	 * @see TableIndex::getOnUpdate() Для получения действия при обновлении.
	 * @see TableIndex::getOnDelete() Для получения действия при удалении.
	 * @see TableIndex::generateSql() Метод, где используется генерация внешнего ключа.
	 */
	private function generateForeignKeySql(): string {
		return "ALTER TABLE `{$this->getTable()}` ADD CONSTRAINT {$this->getName()} FOREIGN KEY ({$this->getColumn()}) " . "REFERENCES `{$this->getReferenceTable()}` (`{$this->getReferenceColumn()}`) " . "ON UPDATE {$this->getOnUpdate()} ON DELETE {$this->getOnDelete()};";
	}

	/**
	 * Возвращает название таблицы, к которой относится индекс.
	 *
	 * @return string Название таблицы.
	 * @see TableIndex::$table
	 * @see TableIndex::setTable()
	 */
	public function getTable(): string {
		return $this->table;
	}

	/**
	 * Устанавливает имя таблицы.
	 *
	 * @param string $table Имя таблицы.
	 *
	 * @return TableIndex Экземпляр текущего объекта для цепочки вызовов.
	 */
	public function setTable(string $table): TableIndex {
		$this->table = $table;
		return $this;
	}

	/**
	 * Возвращает строку с перечислением колонок индекса, заключенных в обратные кавычки,
	 * разделенных запятой.
	 *
	 * Например, для колонок ['id', 'name'] будет возвращена строка "`id`,`name`".
	 *
	 * @return string Строка с колонками индекса, форматированными для SQL-запроса.
	 *
	 * @see TableIndex::setColumn() Для задания колонок индекса.
	 * @see TableIndex::generateForeignKeySql() Для использования метода в генерации SQL для внешнего ключа.
	 * @see TableIndex::generateUniqueIndexSql() Для использования метода в генерации SQL для уникального индекса.
	 * @see TableIndex::generateStandardIndexSql() Для использования метода в генерации SQL для стандартного индекса.
	 */
	public function getColumn(): string {
		return implode(',', array_map(fn($column) => "`{$column}`", $this->column));
	}

	/**
	 * Устанавливает одну или несколько колонок, которые будут использоваться индексом таблицы.
	 *
	 * @param string|array $column Колонка или массив колонок для добавления в индекс.
	 *
	 * @return TableIndex Возвращает текущий экземпляр для цепочки вызовов.
	 */
	public function setColumn(string|array $column): TableIndex {
		if (is_array($column)) {
			foreach ($column as $col) $this->setColumn($col);
		} else {
			$this->column[] = $column;
		}
		return $this;
	}

	/**
	 * Возвращает действие для события "ON UPDATE" в верхнем регистре.
	 *
	 * @return string|null Действие для события "ON UPDATE" или null.
	 */
	public function getOnUpdate(): ?string {
		return strtoupper($this->onUpdate);
	}

	/**
	 * Устанавливает действие для события "ON UPDATE".
	 *
	 * @param string|null $onUpdate Действие для события "ON UPDATE".
	 *
	 * @return TableIndex Текущий экземпляр класса для цепочного вызова.
	 */
	public function setOnUpdate(?string $onUpdate): TableIndex {
		$this->onUpdate = $onUpdate;
		return $this;
	}

	/**
	 * Возвращает действие для события "ON DELETE" в верхнем регистре.
	 *
	 * @return string|null Действие для события "ON DELETE" или null.
	 */
	public function getOnDelete(): ?string {
		return strtoupper($this->onDelete);
	}

	/**
	 * Устанавливает действие для события "ON DELETE".
	 *
	 * @param string|null $onDelete Действие для события "ON DELETE".
	 *
	 * @return TableIndex Текущий экземпляр класса для цепочного вызова.
	 */
	public function setOnDelete(?string $onDelete): TableIndex {
		$this->onDelete = $onDelete;
		return $this;
	}

	/**
	 * Определяет, является ли индекс уникальным.
	 *
	 * @return bool True, если индекс уникальный; иначе false.
	 */
	public function isUnique(): bool {
		return $this->isUnique;
	}

	/**
	 * Устанавливает признак уникальности индекса.
	 *
	 * Метод задаёт, является ли индекс уникальным, на основе переданного значения.
	 * Значение проверяется с использованием фильтрации через `FILTER_VALIDATE_BOOLEAN`.
	 *
	 * @param bool|int|string|null $isUnique Значение, определяющее уникальность индекса.
	 *                                       Может быть булевым, целым числом, строкой или null.
	 *
	 * @return TableIndex Возвращает текущий экземпляр класса для цепочки вызовов.
	 */
	public function setIsUnique(bool|int|string|null $isUnique): TableIndex {
		$this->isUnique = $isUnique && !filter_var($isUnique, FILTER_VALIDATE_BOOLEAN);
		return $this;
	}

	/**
	 * Генерирует SQL-запрос для создания уникального индекса.
	 *
	 * В зависимости от количества колонок формирует соответствующий SQL-запрос:
	 * - Если колонок больше одной, создаёт уникальное ограничение с использованием `ALTER TABLE`.
	 * - Если колонка одна, создаёт уникальный индекс с использованием `CREATE UNIQUE INDEX`.
	 *
	 * @return string Сгенерированный SQL-запрос для создания уникального индекса или ограничения.
	 *
	 * @see TableIndex::getTable() Используется для получения имени таблицы.
	 * @see TableIndex::getName() Используется для получения имени индекса.
	 * @see TableIndex::getColumn() Используется для получения списка колонок.
	 */
	private function generateUniqueIndexSql(): string {
		// Уникальность ключа для одной или нескольких колонок
		if (count($this->column) > 1) {
			return "ALTER TABLE `{$this->getTable()}` ADD CONSTRAINT {$this->getName()} UNIQUE ({$this->getColumn()});";
		}

		return "CREATE UNIQUE INDEX {$this->getName()} ON `{$this->getTable()}` ({$this->getColumn()});";
	}

	/**
	 * Генерирует SQL-запрос для создания стандартного индекса.
	 *
	 * Если тип индекса равен "fulltext", генерируется запрос для создания полнотекстового индекса.
	 * В противном случае генерируется запрос для создания обычного индекса.
	 *
	 * @return string SQL-запрос для создания индекса.
	 * @see TableIndex::getType()
	 * @see TableIndex::getName()
	 * @see TableIndex::getTable()
	 * @see TableIndex::getColumn()
	 */
	private function generateStandardIndexSql(): string {
		$type = strtolower($this->getType());
		if ($type === 'fulltext') {
			return "CREATE OR REPLACE FULLTEXT INDEX {$this->getName()} ON `{$this->getTable()}` ({$this->getColumn()});";
		}

		return "CREATE OR REPLACE INDEX {$this->getName()} ON `{$this->getTable()}` ({$this->getColumn()});";
	}

	/**
	 * Получает текущий тип индекса.
	 *
	 * @return string|null Текущий тип индекса или null, если тип не установлен.
	 */
	public function getType(): ?string {
		return $this->type;
	}

	/**
	 * Устанавливает тип индекса.
	 *
	 * @param string|null $type Новый тип индекса или null для сброса.
	 *
	 * @return TableIndex Возвращает текущий экземпляр для цепочки вызовов.
	 */
	public function setType(?string $type): TableIndex {
		$this->type = $type;
		return $this;
	}

}
