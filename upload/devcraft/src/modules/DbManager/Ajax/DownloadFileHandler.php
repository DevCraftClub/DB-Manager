<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Ajax;

use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\FileResponse;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Support\DataManager;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\DbManager\Services\BackupPathHelper;

/**
 * AJAX-обработчик скачивания файла резервной копии.
 */
final class DownloadFileHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$file = filter_var_array($request->data, [
			'file_name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		]);
		$settings = DataManager::getConfig('db_manager');

		try {
			$filePath = BackupPathHelper::resolveFile((string) ($file['file_name'] ?? ''), $settings);

			return new FileResponse($filePath);
		} catch(\Throwable $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'not_found', 404);
		}
	}

}
