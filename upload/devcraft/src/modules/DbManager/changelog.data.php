<?php

declare(strict_types=1);

/**
 * История изменений модуля DB Manager.
 *
 * @return \DevCraft\Types\Changelog[]
 */
return [
	[
		'version' => '200.1.3',
		'date'    => '2026-06-15',
		'changes' => [
			'added' => [
				__('Миграция на DevCraft Admin: модуль DbManager, единая точка AJAX devcraft/ajax.php, FileResponse для скачивания.'),
			],
			'changed' => [
				__('Путь экспорта по умолчанию: devcraft/backup'),
				__('Требуется DevCraft Admin ≥ 200.4.0'),
			],
		],
	],
	[
		'version' => '180.1.2',
		'date'    => '2025-01-01',
		'changes' => [
			'changed' => [
				__('Добавлена проверка совместимости при экспорте базы данных'),
			],
			'fixed' => [
				__('Экспорт данных был поправлен'),
			],
		],
	],
	[
		'version' => '180.1.1',
		'date'    => '2024-06-01',
		'changes' => [
			'changed' => [
				__('Обновление документации классов для улучшенного понятия функционала'),
			],
			'fixed' => [
				__('Исправление пути сохранения файлов на Windows'),
			],
		],
	],
	[
		'version' => '180.1.0',
		'date'    => '2024-01-01',
		'changes' => [
			'added' => [
				__('Основной релиз'),
			],
		],
	],
];
