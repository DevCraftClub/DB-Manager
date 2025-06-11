<?php

/**
 * Класс ParsedTable представляет собой структуру таблицы с метаданными,
 * включающими в себя столбцы таблицы и методы для её обработки.
 */
class ParsedTable {
	/**
	 * @var string $name Название таблицы.
	 */
	private string $name;

	/**
	 * @var array $columns Коллекция колонок таблицы.
	 * @see ParsedTable::setColumns
	 * @see ParsedTable::getColumns
	 */
	private array $columns = [];

	/**
	 * @var array $indexes Список индексов таблицы.
	 * @see ParsedTable::setIndexes
	 * @see ParsedTable::getIndexes
	 */
	private array $indexes = [];

	/**
	 * @var array $parent Родительские элементы таблицы, если имеются.
	 * @see ParsedTable::setParent
	 * @see ParsedTable::getParent
	 */
	private array $parent = [];

	/**
	 * @var array $values Набор значений для вставки в таблицу.
	 * @see ParsedTable::setValues
	 * @see ParsedTable::getValues
	 */
	private array $values = [];

	/**
	 * @var string $collation Колляция таблицы.
	 * @see ParsedTable::setCollation
	 * @see ParsedTable::getCollation
	 */
	private string $collation;

	/**
	 * @var string $charset Кодировка таблицы.
	 * @see ParsedTable::setCharset
	 * @see ParsedTable::getCharset
	 */
	private string $charset;

	/**
	 * @var string $engine Движок базы данных для таблицы.
	 * @see ParsedTable::setEngine
	 * @see ParsedTable::getEngine
	 */
	private string $engine;

	/**
	 * Конструктор класса.
	 *
	 * @param string            $name    Имя таблицы.
	 * @param array|TableColumn $columns Массив столбцов таблицы либо одиночный объект TableColumn.
	 * @param array|TableIndex  $indexes Массив индексов таблицы либо одиночный объект TableIndex.
	 * @param array|string|null $parent  Родительская таблица либо имя родительской таблицы (если применимо).
	 *
	 * @see TableColumn
	 * @see TableIndex
	 */
	public function __construct(string $name, array|TableColumn $columns = [], array|TableIndex $indexes = [], array|string|null $parent = null) {
		$this->setName($name);
		$this->setColumns($columns);
		$this->setIndexes($indexes);
		$this->setParent($parent);
	}

	/**
	 * Возвращает список имен родительских таблиц.
	 *
	 * Метод используется для получения зависимостей таблицы в виде массива строк,
	 * каждая из которых является именем родительской таблицы.
	 *
	 * @return array Список имен родительских таблиц.
	 * @see ParsedTable::setParent()
	 */
	public function getParent(): array {
		return $this->parent;
	}

	/**
	 * Устанавливает родительский элемент или элементы для текущего объекта.
	 *
	 * @param array|string|null $parent Родительский элемент или массив родительских элементов.
	 *                                  Может быть `null` для удаления родительских элементов.
	 *
	 * @return ParsedTable Возвращает текущий объект для последовательного вызова методов.
	 * @see ParsedTable::$parent
	 */
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

	/**
	 * Генерирует SQL-строку для вставки данных в таблицу, основываясь на текущих колонках и значениях таблицы.
	 *
	 * @param bool $grouped Определяет, должна ли быть возвращена одна строка INSERT для всех значений
	 *                      (если true) или отдельные строки INSERT для каждой записи (если false).
	 *
	 * @return string SQL-запрос для вставки данных. Возвращает пустую строку, если нет значений для вставки.
	 *
	 * @see ParsedTable::getColumns() Метод получения колонок таблицы.
	 * @see ParsedTable::getValues() Метод получения значений таблицы.
	 * @see ParsedTable::prepareRowValues() Метод подготовки значений строки.
	 * @see ParsedTable::getName() Метод получения имени таблицы.
	 */
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

	/**
	 * Возвращает массив значений, связанных с текущим объектом ParsedTable.
	 *
	 * @return array Массив значений.
	 *
	 * @see ParsedTable::$values
	 * @see ParsedTable::getSqlValues()
	 * @see ParsedTable::prepareRowValues()
	 */
	public function getValues(): array {
		return $this->values;
	}

	/**
	 * Устанавливает значения и добавляет их в массив значений.
	 *
	 * @param array $values Массив значений для добавления.
	 *
	 * @return ParsedTable Возвращает текущий экземпляр класса ParsedTable.
	 */
	public function setValues(array $values): ParsedTable {
		$this->values[] = $values;
		return $this;
	}

	/**
	 * Подготавливает значения строки для включения в SQL-запрос.
	 *
	 * Метод преобразует массив значений строки в строку SQL-значений
	 * с учетом типов соответствующих столбцов.
	 *
	 * @param array $value   Массив значений для строки, упорядоченных в соответствии с $columns.
	 * @param array $columns Массив имён столбцов таблицы.
	 *                       Каждый столбец должен быть доступен через метод {@see ParsedTable::getColumnData()}.
	 *
	 * @return string Строка, представляющая значения строки в формате SQL, заключённая в скобки.
	 *
	 * @see ParsedTable::mapValueToSql Используется для преобразования значения в SQL-формат.
	 * @see ParsedTable::getColumnData Используется для получения данных о типе столбца.
	 */
	private function prepareRowValues(array $value, array $columns): string {
		$values = array_map(
			fn($col, $idx) => $this->mapValueToSql($value[$idx], $this->getColumnData($col)->getType()),
			$columns,
			array_keys($columns)
		);

		return '(' . implode(', ', $values) . ')';
	}

	/**
	 * Преобразует значение в SQL-совместимый формат на основе заданного типа данных.
	 *
	 * @param mixed  $value Значение, которое необходимо преобразовать.
	 * @param string $type  Тип данных, к которому должно быть приведено значение.
	 *
	 * @return bool|int|float|string|null SQL-совместимое значение:
	 *                                    - 'NULL' для null-значений;
	 *                                    - числовое значение для числовых типов;
	 *                                    - логическое значение для булевых типов;
	 *                                    - экранированная строка для других типов.
	 */

	private function mapValueToSql(mixed $value, string $type): bool|int|float|string|null {
		if (is_null($value)) return 'NULL';
		return match ($type) {
			'int', 'mediumint', 'bigint', 'float', 'smallint', 'tinyint' => filter_var($value, FILTER_VALIDATE_INT),
			'bool', 'boolean'                                            => filter_var($value, FILTER_VALIDATE_BOOL),
			default                                                      => "'" . addslashes($value) . "'"
		};
	}

	/**
	 * Возвращает объект столбца таблицы, если его имя совпадает с указанным.
	 *
	 * @param string $name Имя столбца, для которого требуется получить данные.
	 *
	 * @return TableColumn|null Объект TableColumn, если столбец найден, или null, если столбец с указанным именем
	 *                          отсутствует.
	 * @see ParsedTable::getColumns() Используется для получения списка всех столбцов таблицы.
	 * @see TableColumn::getName() Используется для сравнения имени столбца.
	 */
	private function getColumnData(string $name): ?TableColumn {
		foreach ($this->getColumns() as $column) {
			if ($column->getName() === str_replace("`", '', $name)) {
				return $column;
			}
		}

		return null;
	}

	/**
	 * Возвращает список колонок таблицы.
	 *
	 * @return array Массив объектов, представляющих колонки таблицы.
	 * @see ParsedTable::$columns
	 * @see TableColumn
	 */
	public function getColumns(): array {
		return $this->columns;
	}

	/**
	 * Устанавливает столбцы для таблицы.
	 *
	 * Если передан массив объектов `TableColumn`, то каждый из них рекурсивно добавляется
	 * в текущий список столбцов. Если передан одиночный объект `TableColumn`, он добавляется
	 * напрямую.
	 *
	 * @param array|TableColumn $columns Массив объектов `TableColumn` или одиночный объект `TableColumn`.
	 *
	 * @return ParsedTable Текущий экземпляр `ParsedTable` для поддержки цепочки вызовов.
	 *
	 * @see TableColumn
	 */
	public function setColumns(array|TableColumn $columns): ParsedTable {
		if ($columns instanceof TableColumn) {
			$this->columns[] = $columns;
			return $this;
		}

		foreach ($columns as $column) {
			if ($column instanceof TableColumn) {
				$this->columns[] = $column;
			}
		}

		return $this;
	}

	/**
	 * Возвращает имя таблицы.
	 *
	 * @return string Имя таблицы.
	 * @see ParsedTable::$name
	 * @see ParsedTable::generateSql()
	 * @see ParsedTable::getSqlValues()
	 * @see sortTablesByDependency()
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Устанавливает имя таблицы.
	 *
	 * @param string $name Новое имя таблицы.
	 *
	 * @return ParsedTable Возвращает текущий экземпляр объекта для цепочки вызовов.
	 *
	 * @see ParsedTable::$name
	 * @see ParsedTable::getName()
	 * @see ParsedTable::__construct()
	 */
	public function setName(string $name): ParsedTable {
		$this->name = $name;
		return $this;
	}

	/**
	 * Генерирует SQL-запрос для создания таблицы.
	 *
	 * @param bool $indexes Указывает, нужно ли включать индексы в генерацию SQL-запроса.
	 *
	 * @return string Сгенерированный SQL-запрос для создания таблицы.
	 *
	 * @see ParsedTable::getName() Используется для получения имени таблицы.
	 * @see ParsedTable::getColumns() Получение списка колонок для построения SQL-запроса.
	 * @see ParsedTable::getEngine() Используется для получения типа механизма таблицы.
	 * @see ParsedTable::getCharset() Используется для получения кодировки таблицы.
	 * @see ParsedTable::getCollation() Используется для получения правила сравнения строк в таблице.
	 * @see ParsedTable::getIndexes() Используется для получения индексов, если они включены.
	 * @see TableColumn::generateSql() Используется для генерации SQL каждой отдельной колонки.
	 */
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

	/**
	 * Возвращает тип движка базы данных, который используется для таблицы.
	 *
	 * Метод используется при генерации SQL-запроса, например, в методе {@see ParsedTable::generateSql()}.
	 *
	 * @return string Тип движка базы данных (например, "InnoDB").
	 * @see ParsedTable::generateSql()
	 */
	public function getEngine(): string {
		return $this->engine;
	}

	/**
	 * Устанавливает движок для таблицы.
	 *
	 * @param string $engine Название движка таблицы.
	 *
	 * @return ParsedTable Экземпляр текущего объекта для вызовов по цепочке.
	 */
	public function setEngine(string $engine): ParsedTable {
		$this->engine = $engine;
		return $this;
	}

	/**
	 * Получает текущую кодировку таблицы.
	 *
	 * @return string Кодировка таблицы.
	 * @see ParsedTable::$charset
	 */
	public function getCharset(): string {
		return $this->charset;
	}

	/**
	 * Устанавливает кодировку для текущего объекта.
	 *
	 * @param string $charset Кодировка, которая будет применена.
	 *
	 * @return ParsedTable Возвращает текущий экземпляр объекта для цепочки вызовов.
	 */
	public function setCharset(string $charset): ParsedTable {
		$this->charset = $charset;
		return $this;
	}

	/**
	 * Возвращает установленную настройку сортировки (collation) для таблицы.
	 *
	 * @return string Установленная настройка сортировки.
	 * @see ParsedTable::generateSql()
	 *
	 * @see ParsedTable::$collation
	 */
	public function getCollation(): string {
		return $this->collation;
	}

	/**
	 * Устанавливает значение коллации для таблицы.
	 *
	 * @param string $collation Значение коллации.
	 *
	 * @return ParsedTable Экземпляр текущего объекта для цепочки вызовов.
	 */
	public function setCollation(string $collation): ParsedTable {
		$this->collation = $collation;
		return $this;
	}

	/**
	 * Возвращает список индексов, связанных с таблицей.
	 *
	 * @return array Список индексов.
	 * @see ParsedTable::$indexes
	 */
	public function getIndexes(): array {
		return $this->indexes;
	}

	/**
	 * Устанавливает индексы для таблицы.
	 *
	 * Принимает либо одиночный объект типа TableIndex, либо массив из таких объектов.
	 * В случае, если аргументом передаётся массив, проводится проверка каждого элемента массива
	 * на принадлежность к типу TableIndex. Только объекты этого типа добавляются в список индексов.
	 *
	 * @param array|TableIndex $indexes Массив объектов типа TableIndex или одиночный объект TableIndex.
	 *
	 * @return ParsedTable Возвращает текущий экземпляр ParsedTable.
	 *
	 * @see TableIndex
	 */
	public function setIndexes(array|TableIndex $indexes): ParsedTable {
		if ($indexes instanceof TableIndex) {
			$this->indexes[] = $indexes;
			return $this;
		}

		foreach ($indexes as $index) {
			if ($index instanceof TableIndex) {
				$this->indexes[] = $index;
			}
		}

		return $this;
	}

}
