<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Ajax;

use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Support\DataManager;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\DbManager\Services\BackupPathHelper;

/**
 * AJAX-обработчик удаления файла резервной копии.
 */
final class DeleteFileHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$file = filter_var_array($request->data, [
			'file_name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		]);
		$settings = DataManager::getConfig('db_manager');

		try {
			$filePath = BackupPathHelper::resolveFile((string) ($file['file_name'] ?? ''), $settings);
			unlink($filePath);

			return JsonResponse::toast(__('Удаление прошло успешно'), ['deleted' => true]);
		} catch(\Throwable $e) {
			return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 400);
		}
	}

}
