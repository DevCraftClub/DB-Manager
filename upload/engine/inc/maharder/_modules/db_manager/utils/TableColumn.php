<?php

class TableColumn {
	private string  $name;
	private string  $type;
	private ?int    $length    = null;
	private bool    $isNull    = true;
	private bool    $isPrimary = false;
	private mixed   $default   = null;
	private ?string $extra     = null;

	/**
	 * @param string          $name
	 * @param string          $type
	 * @param int|string|null $length
	 * @param bool            $isNull
	 * @param mixed|null      $default
	 * @param string|null     $extra
	 */
	public function __construct(string $name, string $type, int|string|null $length, bool|string $isNull, mixed $default = null, ?string $extra = null) {
		$this->setName($name);
		$this->setType($type);
		$this->setLength($length);
		$this->setIsNull($isNull);
		$this->setDefault($default);
		$this->setExtra($extra);
	}

	public function getName(): string {
		return $this->name;
	}

	public function setName(string $name): TableColumn {
		$this->name = $name;
		return $this;
	}

	public function getType(): string {
		return $this->type;
	}

	public function setType(string $type): TableColumn {
		$this->type = $type;
		return $this;
	}

	public function getLength(): ?int {
		return $this->length;
	}

	public function setLength(string|int|null $length): TableColumn {
		if (!$length && in_array($this->getType(), ['int', 'float', 'double'])) $length = 11;
		if (!$length && in_array($this->getType(), ['tinyint', 'bool', 'boolean'])) $length = 1;
		if (!$length && in_array($this->getType(), ['bigint'])) $length = 20;
		if (!$length && in_array($this->getType(), ['mediumint'])) $length = 9;
		if (!$length && in_array($this->getType(), ['smallint'])) $length = 6;

		$this->length = $length ? (is_string($length) ? (int)$length : $length) : null;
		return $this;
	}

	public function isNull(): bool {
		return $this->isNull;
	}

	public function setIsNull(?string $isNull = null): TableColumn {
		$this->isNull = $isNull && strtolower($isNull) === 'yes';
		return $this;
	}

	public function getDefault(): mixed {
		return $this->default;
	}

	public function setDefault(mixed $default = null): TableColumn {
		$this->default = match ($default) {
			'int', 'bigint', 'mediumint', 'smallint' => (int)$default,
			'float', 'double'                        => (float)$default,
			'bool', 'tinyint'                        => (bool)$default,
			"''", '""'                               => '',
			default                                  => $default
		};
		return $this;
	}

	public function getExtra(): ?string {
		return $this->extra;
	}

	public function setExtra(?string $extra): TableColumn {
		$this->extra = $extra;
		return $this;
	}

	public function isPrimary(): bool {
		return $this->isPrimary;
	}

	public function setIsPrimary(bool $isPrimary): TableColumn {
		$this->isPrimary = $isPrimary;
		return $this;
	}

	public function generateSql() {
		$type = match ($this->getType()) {
			'text', 'longtext', 'datetime', 'mediumtext' => $this->getType(),
			default                                      => "{$this->getType()}({$this->getLength()})"
		};
		$null = $this->isNull() ? 'NULL' : 'NOT NULL';
		if (empty($this->getDefault())) $default = ''; else $default = match ($this->getType()) {
			'int', 'float', 'double', 'bool' => "DEFAULT {$this->getDefault()}",
			default                          => $this->getDefault() ? "DEFAULT {$this->getDefault()}" : null
		};
		$primary = strtolower($this->getExtra()) === 'auto_increment' ? 'AUTO_INCREMENT PRIMARY KEY' : '';

		return "`{$this->getName()}` {$type} {$null} {$default} {$primary}";
	}

}