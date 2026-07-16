<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Ajax;

use DevCraft\Core\Application;
use DevCraft\Core\Config\Paths;
use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Support\DataManager;
use DevCraft\Core\Config\DevCraftConfig;
use DevCraft\Core\Admin\SettingsFormService;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;
use DevCraft\Modules\DbManager\Services\BackupPathHelper;

/**
 * AJAX-обработчик сохранения настроек DB Manager с пост-обработкой каталога экспорта.
 */
final class SettingsHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		$plugin = Application::instance()->registry()->forMod($request->mod);
		$schema = $plugin?->settingsSchema();

		if($schema === null) {
			return JsonResponse::fail(
				__('Ошибка'),
				__('Схема настроек недоступна'),
				'validation',
			);
		}

		$configDir  = Paths::config();
		$configFile = $configDir . '/' . $schema->codename . '.json';

		if(is_file($configFile) && !is_writable($configFile)) {
			return JsonResponse::fail(
				__('Ошибка'),
				__('Файл конфигурации недоступен для записи'),
				'validation',
				500,
			);
		}

		if(!is_dir($configDir) && !DataManager::createDir($configDir)) {
			return JsonResponse::fail(
				__('Ошибка'),
				__('Каталог конфигурации недоступен для записи'),
				'validation',
				500,
			);
		}

		$service = new SettingsFormService();
		$result  = $service->validatePartial($request->data, $schema);

		if($result['valid'] === [] && $result['errors'] !== []) {
			return JsonResponse::fail(
				__('Ошибка'),
				__('Все поля недействительны'),
				'validation',
				422,
				['fields' => $result['errors']],
			);
		}

		if($result['valid'] !== []) {
			$existing = DataManager::getConfig($schema->codename);
			$merged   = array_merge($existing, $result['valid']);
			$post     = $this->applyPostSave($merged);

			if($post instanceof JsonResponse) {
				return $post;
			}

			DataManager::saveConfig($schema->codename, $post);
			DevCraftConfig::resetCache();
		}

		if($result['errors'] !== []) {
			return JsonResponse::notify(
				__('Внимание'),
				__('Частичное сохранение завершено с ошибками в полях'),
				JsonResponse::TYPE_WARNING,
				[],
				422,
				false,
				[
					'code'    => 'validation',
					'message' => __('Частичное сохранение завершено с ошибками в полях'),
					'title'   => __('Внимание'),
					'fields'  => $result['errors'],
				],
			);
		}

		if(function_exists('clear_cache')) {
			clear_cache();
		}

		return JsonResponse::toast(__('Сохранено'), ['saved' => true]);
	}

	/**
	 * Нормализует настройки, создаёт каталог экспорта и .htaccess.
	 *
	 * @param array<string, mixed> $settings
	 *
	 * @return array<string, mixed>|JsonResponse
	 */
	private function applyPostSave(array $settings): array|JsonResponse {
		$exportPath = trim((string) ($settings['export_path'] ?? ''));

		if($exportPath === '') {
			$exportPath = BackupPathHelper::DEFAULT_EXPORT_PATH;
		}

		$absolute = DataManager::joinPaths(ROOT_DIR, $exportPath);

		if(!is_dir($absolute)) {
			try {
				DataManager::createDir($absolute);
			} catch(\Throwable $e) {
				return JsonResponse::fail(__('Ошибка'), $e->getMessage(), 'validation', 500);
			}
		}

		$settings['export_path'] = str_replace(ROOT_DIR, '', $absolute);

		$protectFile = DataManager::joinPaths($absolute, '.htaccess');

		if(!is_file($protectFile)) {
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

		$settings['export_compatibility'] = $settings['export_compatibility'] ?? 'compatibility';
		$settings['zip_data']             = $settings['zip_data'] ?? 'raw';
		$settings['key_export']           = $settings['key_export'] ?? 'down';
		$settings['values_export']        = $settings['values_export'] ?? 'down';
		$settings['values_export_type']   = $settings['values_export_type'] ?? 'group';
		$settings['export_to_telegram']   = !empty($settings['export_to_telegram']);

		if($settings['export_to_telegram']) {
			if(empty($settings['tg_token'])) {
				return JsonResponse::fail(
					__('Ошибка'),
					__('Включена опция экспорта в телеграм, но токен телеграм бота не заполнен!'),
					'validation',
					422,
					['fields' => ['tg_token' => __('Поле обязательно')]],
				);
			}

			if(empty($settings['tg_chat'])) {
				return JsonResponse::fail(
					__('Ошибка'),
					__('Включена опция экспорта в телеграм, но ID канала/группы не заполнен!'),
					'validation',
					422,
					['fields' => ['tg_chat' => __('Поле обязательно')]],
				);
			}
		}

		return $settings;
	}

}
