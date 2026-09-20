<?php

declare(strict_types=1);

use DevCraft\Builders\ChangelogBuilder;

/**
 * Журнал изменений DB Manager (fluent ChangelogBuilder).
 *
 * @return list<\DevCraft\Types\Changelog>
 */
return [
	ChangelogBuilder::create('200.1.4')
		->date('2026-09-20')
		->changed([
			__('Манифест и журнал изменений переведены на fluent `ModuleManifestBuilder` / `ChangelogBuilder`.'),
			__('install.xml: иконка — путь к Public/icon.png, allow_groups 1,2, notice — сайт автора и документация.'),
			__('Ссылка на документацию — гайд версии 200.1.4.'),
		])
		->removed([
			__('Дубли локалей locales/*/db-manager.xliff — оставлен db_manager.xliff.'),
		])
		->build(),
	ChangelogBuilder::create('200.1.3')
		->date('2026-06-15')
		->added([
			__('Миграция на DevCraft Admin: модуль DbManager, единая точка AJAX devcraft/ajax.php, FileResponse для скачивания.'),
		])
		->changed([
			__('Путь экспорта по умолчанию: devcraft/backup'),
			__('Требуется DevCraft Admin ≥ 200.4.0'),
		])
		->build(),
	ChangelogBuilder::create('180.1.2')
		->date('2025-01-01')
		->changed([
			__('Добавлена проверка совместимости при экспорте базы данных'),
		])
		->fixed([
			__('Экспорт данных был поправлен'),
		])
		->build(),
	ChangelogBuilder::create('180.1.1')
		->date('2024-06-01')
		->changed([
			__('Обновление документации классов для улучшенного понятия функционала'),
		])
		->fixed([
			__('Исправление пути сохранения файлов на Windows'),
		])
		->build(),
	ChangelogBuilder::create('180.1.0')
		->date('2024-01-01')
		->added([
			__('Основной релиз'),
		])
		->build(),
];
