<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Ajax;

use DevCraft\Core\Http\AjaxRequest;
use DevCraft\Core\Http\JsonResponse;
use DevCraft\Core\Interfaces\AjaxHandlerInterface;
use DevCraft\Core\Interfaces\ResponseInterface;

/**
 * AJAX-обработчик тестовой отправки сообщения в Telegram.
 */
final class SendTelegramHandler implements AjaxHandlerInterface {

	public function handle(AjaxRequest $request): ResponseInterface {
		global $config;

		$messageData = filter_var_array($request->data, [
			'bot'  => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'chat' => FILTER_VALIDATE_INT,
		]);

		$dateNow = (new \DateTime())->format('Y-m-d H:i:s');
		$message = <<<HTML
<b>Тестовое сообщение</b> [by DB Manager]
Отправлено с сайта: <b>{$config['http_home_url']}</b>
<b>Дата отправления</b>: {$dateNow}
HTML;
		$message = str_replace(['<br>', '<br />', '<br/>'], PHP_EOL, $message);
		$turl    = 'https://api.telegram.org/bot' . $messageData['bot'] . '/sendMessage?chat_id=' . $messageData['chat'] . '&text=' . urlencode(
				$message,
			) . '&parse_mode=HTML';

		$antwort = json_decode(trim((string) file_get_contents($turl)), true);

		return JsonResponse::toast(__('Сообщение отправлено'), [
			'telegram' => $antwort,
		]);
	}

}
