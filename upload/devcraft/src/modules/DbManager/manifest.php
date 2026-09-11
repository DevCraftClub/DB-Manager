<?php

declare(strict_types=1);

use DevCraft\Modules\DbManager\DbManagerIdentity;

use DevCraft\Types\AdminLink;
use DevCraft\Types\ModuleManifest;
use DevCraft\Builders\ModuleAssetsBuilder;
use DevCraft\Builders\ModuleManifestBuilder;
use DevCraft\Builders\ModuleAjaxConfigBuilder;
use DevCraft\Modules\DbManager\Pages\DashboardPage;
use DevCraft\Modules\DbManager\Pages\SettingsPage;
use DevCraft\Modules\DbManager\Pages\ManagerPage;
use DevCraft\Modules\DbManager\Pages\ChangelogPage;
use DevCraft\Modules\DbManager\Ajax\SettingsHandler;
use DevCraft\Modules\DbManager\Ajax\ExportHandler;
use DevCraft\Modules\DbManager\Ajax\ImportHandler;
use DevCraft\Modules\DbManager\Ajax\DeleteFileHandler;
use DevCraft\Modules\DbManager\Ajax\DownloadFileHandler;
use DevCraft\Modules\DbManager\Ajax\SendTelegramHandler;

/**
 * Манифест модуля DB Manager (fluent ModuleManifestBuilder).
 *
 * @package    DevCraft
 * @since      200.1.3
 * @subpackage Modules.DbManager
 *
 * @return ModuleManifest
 */
return ModuleManifestBuilder::create()
	->mod(DbManagerIdentity::mod())
	->code(DbManagerIdentity::code())
	->name('DB Manager')
	->version('200.1.4')
	->description(__('Работа с базой данных, для правильного экспорта и импорта данных'))
	->icon('mif-database')
	->docsLink('https://readme.devcraft.club/latest/dev/db_manager/install/')
	->siteLink('https://devcraft.club/downloads/db-manager.30/')
	->siteId(30)
	->menu([
		AdminLink::page(__('Главная'), 'dashboard', DashboardPage::class, 'mif-home', DbManagerIdentity::mod()),
		AdminLink::page(__('Управление БД'), 'manager', ManagerPage::class, 'mif-database', DbManagerIdentity::mod()),
		AdminLink::page(__('Настройки'), 'settings', SettingsPage::class, 'mif-cog', DbManagerIdentity::mod()),
		AdminLink::page(__('История изменений'), 'changelog', ChangelogPage::class, 'mif-library', DbManagerIdentity::mod()),
	])
	->ajax(
		ModuleAjaxConfigBuilder::create('admin')
			->methods([
				'settings'      => SettingsHandler::class,
				'send_message'  => SendTelegramHandler::class,
				'export'        => ExportHandler::class,
				'delete_file'   => DeleteFileHandler::class,
				'import'        => ImportHandler::class,
				'download_file' => DownloadFileHandler::class,
			])
	)
	->changelog(require DLEPlugins::Check(__DIR__ . '/changelog.data.php'))
	->assets(ModuleAssetsBuilder::create()->js('db_manager.js'))
	->build(__DIR__);
