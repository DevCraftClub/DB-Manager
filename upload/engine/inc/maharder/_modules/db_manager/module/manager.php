<?php

global $mh, $modInfo;

$settings = DataManager::getConfig($modInfo['module_code']);

$tables = $mh->load_data('dle_tables', [
	'sql' => 'SHOW TABLES'
]);

$tableInfos = [];

foreach ($tables as $idx => $name) {
	$tableId   = 'Tables_in_' . DBNAME;
	$tableName = $name[$tableId];

	$tableEntries = $mh->load_data("{$tableName}_entries", [
		'sql' => "SELECT COUNT(*) AS entry_count FROM {$tableName};"
	])[0];

	$tableSize = $mh->load_data("{$tableName}_size", [
		'sql' => "SELECT TABLE_NAME, (DATA_LENGTH + INDEX_LENGTH) AS table_size_b FROM information_schema.TABLES WHERE TABLE_NAME = '{$tableName}';"
	])[0];

	$table = new SqlTable($tableName, $tableEntries['entry_count'], $tableSize['table_size_b']);

	$tableInfos[] = $table;
}

$exportedFiles = DataManager::dirToArray(DataManager::joinPaths(ROOT_DIR, $settings['export_path']), '.htaccess', 'index.html', 'index.php');
$exported      = [];

foreach ($exportedFiles as $file) {
	$fileInfo                        = pathinfo($file);
	$exported[$fileInfo['basename']] = [
		'name' => $fileInfo['basename'],
		'ext'  => $fileInfo['extension'],
		'path' => str_replace(ROOT_DIR, '', DataManager::joinPaths($settings['export_path'], $file))
	];
}

sort($exported);

$modVars = [
	'title'    => __('Управление базой данных'),
	'tables'   => $tableInfos,
	'exported' => $exported
];

$mh->setBreadcrumb(new BreadCrumb($modVars['title'], $mh->getLinkUrl('manager')));

$htmlTemplate = 'db_manager/manager.html';
