<?php

declare(strict_types=1);

use DevCraft\Types\AdminLink;
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
 * Манифест модуля DB Manager.
 */
return [
	'mod'       => 'db_manager',
	'code'      => 'db_manager',
	'meta'      => [
		'name'        => 'DB Manager',
		'version'     => '200.1.3',
		'description' => __('Работа с базой данных, для правильного экспорта и импорта данных'),
		'icon'        => 'mif-database',
		'docsLink'    => 'https://readme.devcraft.club/latest/dev/db_manager/install/',
		'siteLink'    => 'https://devcraft.club/downloads/db-manager.30/',
		'siteId'      => 30,
		'licLink'     => 'https://devcraft.club/pages/licence-agreement/',
		'author'      => [
			'name'      => 'Maxim Harder',
			'contacts'  => [
				['name' => __('E-Mail'), 'link' => 'mailto:dev@devcraft.club'],
				['name' => __('Telegram'), 'link' => 'https://t.me/MaHarder'],
				['name' => __('Website'), 'link' => 'https://devcraft.club/misc/contact'],
			],
			'donations' => [
				['name' => 'PayPal', 'value' => 'paypal.me/MaximH', 'link' => 'https://paypal.me/MaximH'],
				['name' => 'Ko-Fi', 'value' => 'ko-fi.com/devcraft', 'link' => 'https://ko-fi.com/J3J118N1C'],
				['name' => 'YooMoney', 'value' => '41001454367103', 'link' => 'https://yoomoney.ru/to/41001454367103'],
			],
		],
	],
	'menu'      => [
		AdminLink::page(__('Главная'), 'dashboard', DashboardPage::class, 'mif-home', 'db_manager'),
		AdminLink::page(__('Управление БД'), 'manager', ManagerPage::class, 'mif-database', 'db_manager'),
		AdminLink::page(__('Настройки'), 'settings', SettingsPage::class, 'mif-cog', 'db_manager'),
		AdminLink::page(__('История изменений'), 'changelog', ChangelogPage::class, 'mif-library', 'db_manager'),
	],
	'ajax'      => [
		'controller' => 'admin',
		'methods'    => [
			'settings'      => SettingsHandler::class,
			'send_message'  => SendTelegramHandler::class,
			'export'        => ExportHandler::class,
			'delete_file'   => DeleteFileHandler::class,
			'import'        => ImportHandler::class,
			'download_file' => DownloadFileHandler::class,
		],
	],
	'changelog' => require __DIR__ . '/changelog.data.php',
	'assets'    => [
		'js' => ['db_manager.js'],
	],
];
