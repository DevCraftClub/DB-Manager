<?php

//===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
// Mod: DB Manager
// File: main.php
// Path: /home/wrw-dev/Dev/Projects/dle171/engine/inc/db_manger.php
// ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  =
// Author: Maxim Harder <dev@devcraft.club> (c) 2025
// Website: https://devcraft.club
// Telegram: http://t.me/MaHarder
// ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  =
// Do not change anything!
//===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===

global $breadcrumbs, $mh, $modVars, $mh_template, $htmlTemplate, $config;

use Symfony\Bridge\Twig\Extension\TranslationExtension;

// заполняем важную и нужную информацию о модуле

$modInfo = [
	'module_name'        => 'DB Manager',
    'module_version'     => '180.1.1',
    'module_description' => __('Работа с базой данных, для правильного экспорта и импорта данных'),
    'module_code'        => 'db_manager',
    'module_id'          => 4,
    'module_icon'        => 'fa-duotone fa-solid fa-database',
    'site_link'          => 'https://devcraft.club',
    'docs_link'          => 'https://devcraft.club',
    'dle_config'         => $config,
    'crowdin_name'       => 'db_manаger',
    'crowdin_stat_id'    => '16830581-763961',

];

// Подключаем классы, функции и основные переменные
require_once DLEPlugins::Check(__DIR__.'/maharder/admin/index.php');

// Подключаем переменные модуля и его функционал
// Используем переменную sites для навигации в модуле
switch ($_GET['sites']) {
	// Главная страница
	default:
		require_once DLEPlugins::Check(MH_ROOT.'/_modules/' . $modInfo['module_code'] . '/module/main.php');
		break;

    case 'changelog':
        require_once DLEPlugins::Check(MH_ROOT.'/_modules/' . $modInfo['module_code'] . '/module/changelog.php');
        break;

    case 'manager':
        require_once DLEPlugins::Check(MH_ROOT.'/_modules/' . $modInfo['module_code'] . '/module/manager.php');
        break;
}

$mh->setLink(new AdminLink('manager', __('Управление базой данных'), '?mod=' . $modInfo['module_code'] . '&sites=manager'), 'manager');

$xtraVariable = [
	'links'       => $mh->getVariables('menu'),
	'breadcrumbs' => $mh->getBreadcrumb(),
	'settings'    => DataManager::getConfig($modInfo['module_code']),
];

$mh->setVars($modInfo);
$mh->setVars($xtraVariable);
$mh->setVars($modVars);

$mh_template->addExtension(new TranslationExtension(MhTranslation::getTranslator()));

// Загружаем шаблон
$template = $mh_template->load($htmlTemplate);

// Отображаем всё на сайте
// При помощи array_merge() можно объединить любое кол-во массивов
echo $template->render($mh->getVariables());
