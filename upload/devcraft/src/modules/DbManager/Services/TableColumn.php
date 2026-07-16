<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Services;

/**
 * Класс для представления столбца таблицы в базе данных.
 *
 * Позволяет задавать имя, тип, длину, допустимость NULL, значение по умолчанию,
 * дополнительные параметры и поддержку формирования SQL-запросов.
 **/
class TableColumn {
	/**
	 * Имя колонки таблицы.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Тип данных колонки.
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * Максимальная длина значения колонки.
	 *
	 * Может быть null, если длина не указана конкретно.
	 *
	 * @var int|null
	 */
	private ?int $length = null;

	/**
	 * Признак допускает ли колонка значения NULL.
	 *
	 * @var bool
	 */
	private bool $isNull = true;

	/**
	 * Признак того, является ли колонка первичным ключом.
	 *
	 * @var bool
	 */
	private bool $isPrimary = false;

	/**
	 * Значение по умолчанию для колонки.
	 *
	 * Данный параметр может быть любого типа.
	 *
	 * @var mixed
	 */
	private mixed $default = null;

	/**
	 * Дополнительные характеристики колонки (например, AUTO_INCREMENT).
	 *
	 * @var string|null
	 */
	private ?string $extra = null;

	/**
	 * Конструктор для создания объекта колонки таблицы.
	 *
	 * @param string          $name    Имя колонки.
	 * @param string          $type    Тип данных в колонке (например, int, varchar, text и т.д.).
	 * @param int|string|null $length  Максимальная длина значения, допускаемого в колонке. Может быть null.
	 * @param bool|string     $isNull  Допускается ли значение NULL в колонке. Может передаваться как bool или строка
	 *                                 ('yes'/'no').
	 * @param mixed|null      $default Значение, установленное по умолчанию для колонки.
	 * @param string|null     $extra   Дополнительные атрибуты колонки, такие как AUTO_INCREMENT и т.д.
	 *
	 * @see TableColumn::setName()
	 * @see TableColumn::setType()
	 * @see TableColumn::setLength()
	 * @see TableColumn::setIsNull()
	 * @see TableColumn::setDefault()
	 * @see TableColumn::setExtra()
	 */
	public function __construct(string $name, string $type, int|string|null $length, bool|string $isNull, mixed $default = null, ?string $extra = null) {
		$this->setName($name);
		$this->setType($type);
		$this->setLength($length);
		$this->setIsNull($isNull);
		$this->setDefault($default);
		$this->setExtra($extra);
	}

	/**
	 * Возвращает имя столбца таблицы.
	 *
	 * @return string Имя столбца.
	 * @see TableColumn::setName() Для установки значения имени столбца.
	 * @see ParsedTable::generateSql() Используется при генерации SQL-запроса.
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Устанавливает имя колонки таблицы.
	 *
	 * @param string $name Имя колонки.
	 *
	 * @return TableColumn Возвращает текущий объект класса для возможности каскадного вызова методов.
	 *
	 * @see TableColumn::$name Хранит имя колонки.
	 * @see TableColumn::__construct() Используется для инициализации объекта.
	 */
	public function setName(string $name): TableColumn {
		$this->name = $name;
		return $this;
	}

	/**
	 * Возвращает тип данных колонки.
	 *
	 * Данные из свойства $type описывают тип данных колонки,
	 * например, 'varchar', 'int', 'text'.
	 *
	 * @return string Тип данных колонки.
	 * @see TableColumn::$type Тип данных текущей колонки.
	 * @see TableColumn::setType() Для установки типа данных колонки.
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * Устанавливает тип данных для текущей колонки таблицы.
	 *
	 * Значение, передаваемое в параметре, будет сохранено в приватное свойство $type,
	 * которое используется при генерации SQL-запроса для определения типа данных колонки.
	 *
	 * @param string $type Тип данных для колонки (например, 'int', 'varchar', 'text').
	 *
	 * @return TableColumn Возвращает текущий объект класса для возможности каскадного вызова методов.
	 *
	 * @see TableColumn::$type Хранит тип данных колонки.
	 * @see TableColumn::getType() Для получения значения типа данных.
	 * @see TableColumn::__construct() Используется для инициализации объекта.
	 * @see TableColumn::generateSql() Используется при генерации SQL-запроса.
	 */
	public function setType(string $type): TableColumn {
		$this->type = $type;
		return $this;
	}

	/**
	 * Возвращает длину столбца таблицы, если она задана.
	 *
	 * @return int|null Длина столбца, если она установлена, или null.
	 * @see TableColumn::setLength()
	 */
	public function getLength(): ?int {
		return $this->length;
	}

	/**
	 * Устанавливает длину для текущего столбца таблицы.
	 * Если длина не указана, назначает значение по умолчанию для соответствующего типа данных столбца.
	 *
	 * @param string|int|null $length Значение длины столбца, либо null для использования длины по умолчанию.
	 *
	 * @return TableColumn Возвращает текущий экземпляр класса для цепочного вызова методов.
	 */
	public function setLength(string|int|null $length): TableColumn {
		if (!$length && in_array($this->getType(), ['int', 'float', 'double'])) $length = 11;
		if (!$length && in_array($this->getType(), ['tinyint', 'bool', 'boolean'])) $length = 1;
		if (!$length && in_array($this->getType(), ['bigint'])) $length = 20;
		if (!$length && in_array($this->getType(), ['mediumint'])) $length = 9;
		if (!$length && in_array($this->getType(), ['smallint'])) $length = 6;

		$this->length = $length ? (is_string($length) ? (int)$length : $length) : null;
		return $this;
	}

	/**
	 * Определяет, разрешено ли значение столбца быть NULL.
	 *
	 * @return bool Возвращает true, если значение может быть NULL, иначе false.
	 * @see TableColumn::setIsNull() Метод для установки значения `isNull`.
	 * @see TableColumn::generateSql() Метод, где используется результат isNull.
	 */
	public function isNull(): bool {
		return $this->isNull;
	}

	/**
	 * Устанавливает, допускает ли колонка значение NULL.
	 *
	 * @param string|null $isNull  Строка, указывающая, допускается ли значение NULL (например, 'yes').
	 *                             Если передана строка 'yes' (регистр неважен), свойство устанавливается в true,
	 *                             иначе в false.
	 *
	 * @return TableColumn Возвращает текущий экземпляр TableColumn.
	 */
	public function setIsNull(bool|string|null $isNull = null): TableColumn {
		if ($isNull === null || $isNull === '') {
			$this->isNull = false;

			return $this;
		}

		if (is_bool($isNull)) {
			$this->isNull = $isNull;

			return $this;
		}

		$this->isNull = strtolower($isNull) === 'yes';

		return $this;
	}

	/**
	 * Возвращает значение по умолчанию для столбца.
	 *
	 * Значение по умолчанию используется в SQL-генерации для определения стандартного значения
	 * для столбца таблицы в случае отсутствия данных.
	 *
	 * @return mixed Значение по умолчанию столбца, заданное методом setDefault().
	 * @see TableColumn::setDefault() Для установки значения по умолчанию.
	 */
	public function getDefault(): mixed {
		return $this->default;
	}

	/**
	 * Устанавливает значение по умолчанию для столбца таблицы.
	 *
	 * Значение может быть приведено к соответствующему типу в зависимости от его начального значения:
	 * - Если это числовой тип (int, bigint, mediumint, smallint), значение приводится к int.
	 * - Если это тип float или double, значение приводится к float.
	 * - Если это логический тип (bool, tinyint), значение приводится к bool.
	 * - Если это пустые строки ('' или ""), устанавливается пустая строка.
	 * - В других случаях используется переданное значение без изменений.
	 *
	 * @param mixed $default Значение по умолчанию.
	 *
	 * @return TableColumn Возвращает текущий экземпляр столбца таблицы для обеспечения цепного вызова методов.
	 */
	public function setDefault(mixed $default = null): TableColumn {
		if ($default === null) {
			$this->default = null;

			return $this;
		}

		if (is_string($default)) {
			if ($this->isTimestampDefault($default)) {
				$this->default = 'CURRENT_TIMESTAMP';

				return $this;
			}

			if (strtoupper($default) === 'NULL') {
				$this->default = null;

				return $this;
			}
		}

		$newDefault = $default;

		if (in_array($this->getType(), ['int', 'bigint', 'mediumint', 'smallint', 'tinyint'], true) && is_numeric($default)) {
			$newDefault = str_contains((string) $default, '.') ? (float) $default : (int) $default;
		} elseif (in_array($this->getType(), ['float', 'double', 'decimal'], true) && is_numeric($default)) {
			$newDefault = $default;
		} elseif ($default === "''" || $default === '""') {
			$newDefault = '';
		}

		if (is_string($newDefault)
		    && !preg_match('/^[\'"].*[\'"]$/', $newDefault)
		    && !is_numeric($newDefault)
		    && $newDefault !== '') {
			$newDefault = "'{$newDefault}'";
		}

		$this->default = $newDefault;

		return $this;
	}

	/**
	 * Возвращает текущий параметр "extra" столбца таблицы.
	 *
	 * Значение "extra" может быть использовано для указания дополнительных опций
	 * столбца, таких как "AUTO_INCREMENT".
	 *
	 * @return string|null Строка с дополнительными параметрами столбца или null, если они не установлены.
	 * @see TableColumn::setExtra() Для установки значения "extra".
	 * @see TableColumn::generateSql() Метод, где используется это значение.
	 */
	public function getExtra(): ?string {
		return $this->extra;
	}

	/**
	 * Устанавливает дополнительный параметр для столбца таблицы.
	 *
	 * @param string|null $extra Дополнительный параметр.
	 *
	 * @return TableColumn Текущий экземпляр для цепочки вызовов.
	 */
	public function setExtra(?string $extra): TableColumn {
		$this->extra = $extra;
		return $this;
	}

	/**
	 * Возвращает, является ли текущий объект первичным ключом.
	 *
	 * @return bool Возвращает true, если объект является первичным ключом, в противном случае - false.
	 * @see TableColumn::$isPrimary
	 */
	public function isPrimary(): bool {
		return $this->isPrimary;
	}

	/**
	 * Устанавливает флаг, указывающий, является ли колонка первичным ключом.
	 *
	 * @param bool $isPrimary Флаг, указывающий, является ли колонка первичным ключом.
	 *
	 * @return TableColumn Возвращает текущий экземпляр класса для возможности цепочки вызовов.
	 */
	public function setIsPrimary(bool $isPrimary): TableColumn {
		$this->isPrimary = $isPrimary;
		return $this;
	}

	/**
	 * Генерирует SQL-выражение для описания столбца таблицы на основе его свойств.
	 *
	 * @return string SQL-выражение, описывающее столбец таблицы.
	 *
	 * @see TableColumn::getName() Получение имени столбца.
	 * @see TableColumn::getType() Получение типа столбца.
	 * @see TableColumn::getLength() Получение длины столбца.
	 * @see TableColumn::isNull() Проверка, допускаются ли значения NULL для данного столбца.
	 * @see TableColumn::getDefault() Получение значения по умолчанию для столбца.
	 * @see TableColumn::getExtra() Получение дополнительных опций столбца, например, автоинкремента.
	 */
	public function generateSql(): string {
		$type = match ($this->getType()) {
			'text', 'longtext', 'mediumtext', 'datetime', 'date', 'time', 'timestamp', 'year',
			'blob', 'tinyblob', 'mediumblob', 'longblob', 'json' => $this->getType(),
			'float', 'double', 'decimal' => $this->getLength() !== null
				? "{$this->getType()}({$this->getLength()})"
				: $this->getType(),
			default => "{$this->getType()}({$this->getLength()})",
		};
		$null    = $this->isNull() ? 'NULL' : 'NOT NULL';
		$default = $this->formatDefaultClause();
		$extra   = $this->getExtra();
		$primary = $extra !== null && strtolower($extra) === 'auto_increment' ? 'AUTO_INCREMENT PRIMARY KEY' : '';

		return trim(preg_replace('/\s+/', ' ', "`{$this->getName()}` {$type} {$null} {$default} {$primary}"));
	}

	/**
	 * Формирует SQL-фрагмент DEFAULT для столбца.
	 */
	private function formatDefaultClause(): string {
		$value = $this->getDefault();

		if ($value === null || $value === '') {
			return '';
		}

		if (is_string($value) && $this->isTimestampDefault($value)) {
			return 'DEFAULT CURRENT_TIMESTAMP';
		}

		if (is_numeric($value)
		    || in_array($this->getType(), ['int', 'bigint', 'mediumint', 'smallint', 'tinyint', 'float', 'double', 'decimal'], true)) {
			return "DEFAULT {$value}";
		}

		if (is_string($value) && preg_match('/^[\'"].*[\'"]$/', $value)) {
			return "DEFAULT {$value}";
		}

		if (is_string($value)) {
			return "DEFAULT '{$value}'";
		}

		return "DEFAULT {$value}";
	}

	/**
	 * Проверяет, является ли значение по умолчанию CURRENT_TIMESTAMP/NOW.
	 */
	private function isTimestampDefault(string $value): bool {
		$normalized = strtoupper(preg_replace('/\(\)$/', '', trim($value, "'\"")));

		return in_array($normalized, ['CURRENT_TIMESTAMP', 'NOW'], true);
	}

}
