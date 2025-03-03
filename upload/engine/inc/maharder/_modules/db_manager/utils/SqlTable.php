<?php

class SqlTable {
	private string $name;
	private int    $entries;
	private int    $size;

	/**
	 * @param string $name
	 * @param int    $entries
	 * @param int    $size
	 */
	public function __construct(string $name, int $entries, int $size) {
		$this->name    = $name;
		$this->entries = $entries;
		$this->size    = $size;
	}

	public function getName(): string {
		return $this->name;
	}

	public function setName(string $name): SqlTable {
		$this->name = $name;
		return $this;
	}

	public function getEntries(): int {
		return $this->entries;
	}

	public function setEntries(int $entries): SqlTable {
		$this->entries = $entries;
		return $this;
	}

	public function getFormattedSize(): string {
		return match (true) {
			$this->size >= 1024 ** 3 => round($this->size / (1024 ** 3), 2) . ' GB',
			$this->size >= 1024 ** 2 => round($this->size / (1024 ** 2), 2) . ' MB',
			$this->size >= 1024      => round($this->size / 1024, 2) . ' KB',
			default                  => $this->size . ' bytes',
		};
	}

	public function getSize(): int {
		return $this->size;
	}

	public function setSize(int $size): SqlTable {
		$this->size = $size;
		return $this;
	}

}