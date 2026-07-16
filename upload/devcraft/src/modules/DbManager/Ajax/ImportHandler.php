<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Ajax;

use ZipArchive;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Support\DataManager;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\DbManager\Services\BackupPathHelper;

/**
 * AJAX-обработчик импорта SQL из файла резервной копии.
 */
final class ImportHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$file = filter_var_array($request->data, [
			'file_name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		]);
		$settings = DataManager::getConfig('db_manager');

		try {
			$filePath = BackupPathHelper::resolveFile((string) ($file['file_name'] ?? ''), $settings);
			$fileInfo = pathinfo($filePath);

			if(($fileInfo['extension'] ?? '') === 'zip') {
				$zip = new ZipArchive();

				if($zip->open($filePath) !== true) {
					return JsonResponse::fail(__('Ошибка'), __('Не удалось открыть архив'), 'validation', 400);
				}

				$extractPath = DataManager::joinPaths(
					BackupPathHelper::exportDir($settings),
					$fileInfo['filename'],
				);

				if(!is_dir($extractPath)) {
					DataManager::createDir($extractPath);
				}

				$zip->extractTo($extractPath);
				$zip->close();
				$filePath = DataManager::joinPaths($extractPath, "{$fileInfo['filename']}.sql");
			}

			if(($fileInfo['extension'] ?? '') === 'bz2') {
				$bz = bzopen($filePath, 'r');

				if(!$bz) {
					return JsonResponse::fail(__('Ошибка'), __('Не удалось открыть архив bzip2'), 'validation', 400);
				}

				$extractedFilePath = DataManager::joinPaths(
					BackupPathHelper::exportDir($settings),
					"{$fileInfo['filename']}.sql",
				);
				$outFile = fopen($extractedFilePath, 'w');

				if(!$outFile) {
					bzclose($bz);

					return JsonResponse::fail(__('Ошибка'), __('Не удалось создать файл для распаковки'), 'validation', 500);
				}

				while(!feof($bz)) {
					$chunk = bzread($bz, 4096);

					if($chunk === false) {
						fclose($outFile);
						bzclose($bz);

						return JsonResponse::fail(__('Ошибка'), __('Ошибка чтения из архива bzip2'), 'validation', 500);
					}

					fwrite($outFile, $chunk);
				}

				fclose($outFile);
				bzclose($bz);
				$filePath = $extractedFilePath;
			}

			$sqlData = file_get_contents($filePath);

			if($sqlData === false) {
				return JsonResponse::fail(__('Ошибка'), __('Не удалось прочитать SQL-файл'), 'validation', 400);
			}

			$mysql = new \mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
			$mysql->multi_query($sqlData);
			$mysql->close();

			return JsonResponse::toast(__('Восстановление базы данных завершено!'), ['imported' => true]);
		} catch(\Throwable $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 400);
		}
	}

}
