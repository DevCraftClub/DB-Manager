<?php

declare(strict_types=1);

/**
 * Класс для парсинга данных из SQL таблиц.
 *
 * Используется для обработки и извлечения данных из SQL таблиц с учетом различных форматов и настроек.
 */
class SqlTableParser {
	use DataLoader;

	/**
	 * Хранит данные, полученные после парсинга таблицы.
	 *
	 * @var ParsedTable $tableData Объект, представляющий данные таблицы.
	 */
	private ParsedTable $tableData;

	/**
	 * Конструктор класса SqlTableParser.
	 *
	 * Создаёт объект для обработки таблицы с указанным именем и схемой.
	 * При её создании инициализирует данные таблицы, выполняя парсинг структуры таблицы
	 * и её значений.
	 *
	 * @param string  $table  Имя таблицы для обработки.
	 * @param string  $schema Имя схемы, к которой относится таблица.
	 *
	 * @throws JsonException Возникает при ошибке обработки данных в формате JSON.
	 *
	 * @see ParsedTable Конструктор класса ParsedTable.
	 * @see SqlTableParser::parse() Выполняет парсинг структуры таблицы.
	 * @see SqlTableParser::parseValues() Выполняет обработку значений таблицы.
	 * @global string $table  Глобальная переменная для хранения имени таблицы.
	 */
	public function __construct(private string $table, private string $schema) {
		$this->tableData = new ParsedTable($this->table);
		$this->parse();
		$this->parseValues();
	}

	/**
	 * Выполняет парсинг структуры таблицы базы данных.
	 *
	 * Метод извлекает информацию о столбцах, индексах, кодировке, механизме хранения и кодировке таблицы.
	 * - Парсит столбцы таблицы и добавляет их в объект `tableData`.
	 * - Загружает информацию об индексах и обрабатывает их, включая внешние ключи.
	 * - Извлекает и задаёт уникальную кодировку, механизм хранения и набор символов таблицы.
	 *
	 * @throws JsonException Если данные, полученные при загрузке таблицы, не могут быть обработаны.
	 *
	 * @see SqlTableParser::parseColumns() Для обработки столбцов таблицы.
	 * @see SqlTableParser::parseIndex() Для обработки индексов таблицы.
	 * @see TableColumn Для представления столбцов таблицы.
	 * @see TableIndex Для представления индексов таблицы.
	 * @see SqlTableParser::tableData Объект для хранения данных о таблице.
	 * @global string $schema Схема базы данных, в которой находится таблица.
	 * @global string $table  Имя таблицы для парсинга.
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
		$tableEngine    = isset($collation[0]['ENGINE']) ? $collation[0]['ENGINE'] : 'InnoDB';

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

		$tableCharset = isset($charset[0]['CHARACTER_SET_NAME']) ? $charset[0]['CHARACTER_SET_NAME'] : COLLATE;

		$this->tableData->setCollation($tableCollation);
		$this->tableData->setEngine($tableEngine);
		$this->tableData->setCharset($tableCharset);
	}

	/**
	 * Обрабатывает массив колонок и преобразует каждую запись из массива в объект TableColumn.
	 * Затем добавляет их в таблицу через объект tableData.
	 *
	 * @param array        $columns   Массив колонок, каждая из которых должна содержать:
	 *                                - 'COLUMN_NAME' (string) Название колонки.
	 *                                - 'DATA_TYPE' (string) Тип данных колонки.
	 *                                - 'CHARACTER_MAXIMUM_LENGTH' (int|string|null) Длина данных или null.
	 *                                - 'IS_NULLABLE' (bool|string) Указывает, допускает ли колонка NULL.
	 *                                - 'COLUMN_DEFAULT' (mixed|null) Значение по умолчанию.
	 *                                - 'EXTRA' (string|null) Дополнительные свойства, такие как "auto_increment".
	 *
	 * @return void
	 *
	 * @see TableColumn Конструктор класса TableColumn.
	 * @see TableColumn::setIsPrimary() Устанавливает признак первичного ключа.
	 * @see ParsedTable::setColumns() Добавляет колонку или массив колонок в объект ParsedTable.
	 * @global ParsedTable $tableData Объект хранения данных о таблице.
	 */
	private function parseColumns(array $columns): void {
		foreach ($columns as $column) {
			$tableColumn = new TableColumn(
				$column['COLUMN_NAME'],
				$column['DATA_TYPE'],
				$column['CHARACTER_MAXIMUM_LENGTH'],
				$column['IS_NULLABLE'],
				$column['COLUMN_DEFAULT'],
				$column['EXTRA']
			);
			$tableColumn->setIsPrimary($column['EXTRA'] == 'auto_increment');
			$this->tableData->setColumns($tableColumn);
		}

	}

	/**
	 * Парсит индексы из массива и добавляет их к данным таблицы.
	 *
	 * @param array $keys Массив данных об индексах, содержащий информацию
	 *                    о названии ограничения, таблице, столбце, ссылочной таблице,
	 *                    ссылочном столбце и уникальности индекса.
	 *
	 * @return void
	 *
	 * @see TableIndex Конструктор и методы класса используются для создания и настройки индексов.
	 * @see TableIndex::isForeignKey() Определяет, является ли индекс внешним ключом.
	 * @see TableIndex::setOnUpdate() Устанавливает правило обновления для внешнего ключа.
	 * @see TableIndex::setOnDelete() Устанавливает правило удаления для внешнего ключа.
	 * @see TableIndex::getName() Возвращает имя индекса.
	 * @see TableIndex::getReferenceTable() Возвращает имя ссылочной таблицы.
	 * @see TableIndex::setType() Устанавливает тип индекса.
	 * @see TableIndex::setColumn() Добавляет или изменяет столбец индекса.
	 * @see SqlTableParser::$tableData Данные таблицы, к которым добавляются индексы.
	 * @see SqlTableParser::$table Имя текущей таблицы.
	 * @see SqlTableParser::$schema Схема базы данных.
	 */
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
				$next   = $indexex[$nextId] ?? false;

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

	/**
	 * Разбирает и устанавливает значения для текущей таблицы.
	 *
	 * Метод загружает данные таблицы, удаляя префикс из её имени.
	 * Затем выполняется итерация по полученным строкам данных,
	 * а значения извлекаются на основе имен колонок, предоставленных методом `getColumns()`.
	 * После этого значения передаются в объект `tableData` для обработки и сохранения.
	 *
	 * @throws JsonException Выбрасывается, если возникают ошибки обработки JSON в методе загрузки данных `load_data()`.
	 *
	 * @see SqlTableParser::table Описание переменной таблицы.
	 * @see SqlTableParser::tableData Объект, который используется для хранения разобранных значений.
	 * @see ParsedTable::getColumns() Метод, возвращающий список колонок текущей таблицы.
	 * @see ParsedTable::setValues() Метод, сохраняющий значения в объекте таблицы данных.
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

	/**
	 * Возвращает результат в виде объекта ParsedTable.
	 *
	 * @return ParsedTable Объект с данными таблицы.
	 */
	public function getResult(): ParsedTable {
		return $this->tableData;
	}
}
