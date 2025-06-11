<?php

/**
 * Класс SqlTable представляет таблицу базы данных с ее основными характеристиками.
 */
class SqlTable {
	/**
	 * Наименование таблицы.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Количество записей в таблице.
	 *
	 * @var int
	 */
	private int $entries;

	/**
	 * Размер таблицы в байтах.
	 *
	 * @var int
	 */
	private int $size;

	/**
	 * Конструктор класса SqlTable.
	 *
	 * @param string $name    Наименование таблицы.
	 * @param int    $entries Количество записей в таблице.
	 * @param int    $size    Размер таблицы в байтах.
	 *
	 * @see SqlTable::$name
	 * @see SqlTable::$entries
	 * @see SqlTable::$size
	 */

	public function __construct(string $name, int $entries, int $size) {
		$this->setName($name);
		$this->setEntries($entries);
		$this->setSize($size);
	}

	/**
	 * Возвращает наименование таблицы.
	 *
	 * @return string Наименование таблицы.
	 *
	 * @see SqlTable::$name
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Устанавливает наименование таблицы.
	 *
	 * @param string $name Наименование таблицы.
	 *
	 * @return SqlTable Экземпляр текущего объекта.
	 *
	 * @see SqlTable::$name
	 */
	public function setName(string $name): SqlTable {
		$this->name = $name;
		return $this;
	}

	/**
	 * Возвращает количество записей в таблице.
	 *
	 * @return int Количество записей в таблице.
	 *
	 * @see SqlTable::$entries
	 */
	public function getEntries(): int {
		return $this->entries;
	}

	/**
	 * Устанавливает количество записей в таблице.
	 *
	 * @param int $entries Количество записей в таблице.
	 *
	 * @return SqlTable Экземпляр текущего объекта.
	 *
	 * @see SqlTable::$entries
	 */
	public function setEntries(int $entries): SqlTable {
		$this->entries = $entries;
		return $this;
	}

	/**
	 * Возвращает форматированный размер таблицы в удобочитаемом виде.
	 *
	 * Размер возвращается с единицами измерения: байты (bytes), килобайты (KB), мегабайты (MB) или гигабайты (GB),
	 * в зависимости от величины значения свойства `$size`.
	 *
	 * @return string Форматированный размер таблицы (например, "1.5 GB", "200 MB", "512 KB" или "128 bytes").
	 *
	 * @see SqlTable::$size
	 */
	public function getFormattedSize(): string {
		return match (true) {
			$this->size >= 1024 ** 3 => round($this->size / (1024 ** 3), 2) . ' GB',
			$this->size >= 1024 ** 2 => round($this->size / (1024 ** 2), 2) . ' MB',
			$this->size >= 1024      => round($this->size / 1024, 2) . ' KB',
			default                  => $this->size . ' bytes',
		};
	}

	/**
	 * Возвращает текущий размер таблицы.
	 *
	 * @return int Размер таблицы.
	 *
	 * @see SqlTable
	 * @see SqlTable::setSize
	 */
	public function getSize(): int {
		return $this->size;
	}

	/**
	 * Устанавливает размер таблицы в байтах.
	 *
	 * @param int $size Размер таблицы в байтах.
	 *
	 * @return SqlTable Экземпляр текущего объекта.
	 *
	 * @see SqlTable::$size
	 * @see SqlTable::getSize()
	 */
	public function setSize(int $size): SqlTable {
		$this->size = $size;
		return $this;
	}

}