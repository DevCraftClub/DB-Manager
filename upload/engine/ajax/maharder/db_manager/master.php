<?php

//===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===
// Mod: DB Manager
// File: main.php
// Path: engine/ajax/maharder/db_manger/master.php
// ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  =
// Author: Maxim Harder <dev@devcraft.club> (c) 2025
// Website: https://devcraft.club
// Telegram: http://t.me/MaHarder
// ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  =
// Do not change anything!
//===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===  ===

global $method, $data, $config, $parsedData;
if (!defined('DATALIFEENGINE')) {
	header('HTTP/1.1 403 Forbidden');
	header('Location: ../../../../');
	exit('Hacking attempt!');
}

if (!$method) {
	exit();
}

switch ($method) {
	case 'settings':

		$settingsData = filter_var_array($parsedData, [
			'export_path'        => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'export_to_telegram' => FILTER_VALIDATE_BOOL,
			'key_export'         => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'values_export'      => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'values_export_type' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'zip_data'           => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'tg_token'           => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'tg_chat'            => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		]);

		if (empty($settingsData['export_path'])) {
			$exportPath = DataManager::joinPaths(MH_ROOT, '/_backup');
		} else {
			$exportPath = DataManager::joinPaths(ROOT_DIR, $settingsData['export_path']);
		}
		if (!is_dir($exportPath)) {
			try {
				DataManager::createDir($exportPath);
			} catch (JsonException|Throwable $e) {
				echo (new ErrorResponseAjax())->setData($e)->send();
				exit;
			}
		}

		$settingsData['export_path'] = str_replace(ROOT_DIR, '', $exportPath);

		$protectFile = DataManager::joinPaths($exportPath, '.htaccess');
		if (!is_file($protectFile)) {
			$htaccess = <<<HTACCESS
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
	Order allow,deny
	Deny from all
</IfModule>
HTACCESS;
			file_put_contents($protectFile, $htaccess);
			chmod($protectFile, 0644);
		}

		if (empty($settingsData['zip_data'])) {
			$settingsData['zip_data'] = 'raw';
		}

		if (empty($settingsData['key_export'])) {
			$settingsData['key_export'] = 'down';
		}

		if (empty($settingsData['values_export'])) {
			$settingsData['values_export'] = 'down';
		}

		if (empty($settingsData['values_export_type'])) {
			$settingsData['values_export_type'] = 'group';
		}

		$settingsData['export_to_telegram'] = !is_null($settingsData['export_to_telegram']);

		if ($settingsData['export_to_telegram']) {
			if (empty($settingsData['tg_token'])) {
				echo (new ErrorResponseAjax())->setData(
					__('Включена опция экспорта в телеграм, но токен телеграм бота не заполнен!')
				)->setMeta('tg_token')->send();
				exit;
			}
			if (empty($settingsData['tg_chat'])) {
				echo (new ErrorResponseAjax())->setData(
					__('Включена опция экспорта в телеграм, но ID канала/группы не заполнен!')
				)->setMeta('tg_chat')->send();
				exit;
			}
		}

		DataManager::saveConfig('db_manager', $settingsData);
		clear_cache();

		echo (new SuccessResponseAjax())
			->setData(__('Настройки сохранены'))->setRedirect($_SERVER['HTTP_REFERER'])->send();

		break;

	case 'send_message':
		$date_now    = (new \DateTime())->format('Y-m-d H:i:s');
		$messageData = filter_var_array($parsedData, [
			'bot'  => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'chat' => FILTER_VALIDATE_INT,
		]);

		$message = <<<HTML
<b>Тестовое сообщение</b> [by DB Manager]
Отправлено с сайта: <b>{$config['http_home_url']}</b>
<b>Дата отправления</b>: {$date_now}
HTML;
		$message = str_replace(['<br>', '<br />', '<br/>'], PHP_EOL, $message);
		$turl    = "https://api.telegram.org/bot" . $messageData['bot'] . "/sendMessage?chat_id=" . $messageData['chat'] . "&text=" . urlencode(
				$message
			) . "&parse_mode=HTML";

		$antwort = json_decode(trim(file_get_contents($turl)), true);

		echo (new SuccessResponseAjax())->setData(__('Сообщение отправлено'))->setMeta(
			json_encode($antwort, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
		)->send();
		break;

	case 'export':
		require_once DLEPlugins::Check(__DIR__ . '/_export.php');
		break;

	case 'delete_file':
		$file     = filter_var_array($parsedData, [
			'file_name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS
		]);
		$settings = DataManager::getConfig('db_manager');

		try {
			unlink(DataManager::joinPaths(ROOT_DIR, $settings['export_path'], $file['file_name']));
			echo (new SuccessResponseAjax())->setData(__('Удаление прошло успешно'))->setRedirect(
				$_SERVER['HTTP_REFERER'] . '?mod=db_manager&sites=manager'
			)->send();
		} catch (Exception|Throwable $e) {
			echo (new ErrorResponseAjax())->setData($e->getMessage())->send();
		}
		exit;

		break;

	case 'download_file':
		$file     = filter_var_array($parsedData, [
			'file_name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS
		]);
		$settings = DataManager::getConfig('db_manager');

		try {
			$filePath = DataManager::joinPaths(ROOT_DIR, $settings['export_path'], $file['file_name']);

			// Ensure the file exists
			if (!file_exists($filePath)) {
				echo (new ErrorResponseAjax())->setData(__('Файл не найден'))->send();
				exit;
			}

			// Set headers for file download
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filePath));

			if (ob_get_length()) {
				ob_end_clean();
			}

			// Read and output the file
			readfile($filePath);
			exit;

		} catch (Exception|Throwable $e) {
			echo (new ErrorResponseAjax())->setData($e->getMessage())->send();
		}
		break;

	case 'import':
		$file     = filter_var_array($parsedData, [
			'file_name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS
		]);
		$settings = DataManager::getConfig('db_manager');
		$filePath = DataManager::joinPaths(ROOT_DIR, $settings['export_path'], $file['file_name']);

		try {

			$fileInfo   = pathinfo($filePath);
			$deleteData = [];

			if ($fileInfo['extension'] === 'zip') {
				$zip = new ZipArchive();
				if ($zip->open($filePath) === true) {
					$extractPath = DataManager::joinPaths(
						ROOT_DIR,
						$settings['export_path'],
						$fileInfo['filename']
					);
					if (!is_dir($extractPath)) {
						DataManager::createDir($extractPath);
					}

					$zip->extractTo($extractPath);
					$zip->close();
					$filePath = DataManager::joinPaths($extractPath, "{$fileInfo['filename']}.sql");

					$deleteData[] = $extractPath;
					$deleteData[] = $filePath;
				} else {
					echo (new ErrorResponseAjax())->setData(__('Не удалось открыть архив'))->send();
				}
				exit;
			}

			if ($fileInfo['extension'] === 'bzip2') {

				$bz = bzopen($filePath, 'r');
				if (!$bz) {
					echo (new ErrorResponseAjax())->setData(__('Не удалось открыть архив bzip2'))->send();
					exit;
				}

				$extractedFilePath = DataManager::joinPaths(
					ROOT_DIR,
					$settings['export_path'],
					"{$fileInfo['filename']}.sql"
				);

				$outFile = fopen($extractedFilePath, 'w');
				if (!$outFile) {
					bzclose($bz);
					echo (new ErrorResponseAjax())->setData(__('Не удалось создать файл для распаковки'))->send();
					exit;
				}

				while (!feof($bz)) {
					$chunk = bzread($bz, 4096); // Read in chunks for performance
					if ($chunk === false) {
						fclose($outFile);
						bzclose($bz);
						echo (new ErrorResponseAjax())->setData(__('Ошибка чтения из архива bzip2'))->send();
						exit;
					}
					fwrite($outFile, $chunk);
				}

				fclose($outFile);
				bzclose($bz);

				$deleteData[] = $extractedFilePath;
				$filePath     = $extractedFilePath;

			}

			$sqlData = file_get_contents($filePath);

			$mysql = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
			$mysql->multi_query($sqlData);
			$mysql->close();

			echo (new SuccessResponseAjax())->setData(__('Восстановление базы данных завершено!'))->setRedirect($_SERVER['HTTP_REFERER'])->send();
		} catch (Exception|Throwable $e) {
			echo (new ErrorResponseAjax())->setData($e->getMessage())->send();
		}

		break;
}
