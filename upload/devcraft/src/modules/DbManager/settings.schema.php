<?php

declare(strict_types=1);

use DevCraft\Core\Enums\FormLayout;
use DevCraft\Form\FormSchemaBuilder;
use DevCraft\Modules\DbManager\Services\BackupPathHelper;

/**
 * Схема настроек модуля DB Manager.
 */
return FormSchemaBuilder::create('db_manager')
                        ->layout(FormLayout::TABS)
                        ->section(__('Основные'))
	                        ->text('export_path', __('Путь до файлов бд'))
		                        ->description(
			                        __('Укажите путь, где будут храниться файлы базы данных.<br>По умолчанию: <code>{path}</code>',
				                        ['{path}' => BackupPathHelper::DEFAULT_EXPORT_PATH]),
		                        )
		                        ->default(BackupPathHelper::DEFAULT_EXPORT_PATH)
	                        ->checkbox('export_to_telegram', __('Включить экспорт в Telegram?'))
		                        ->description(__('Если включено, то новый экспорт автоматически будет экспортирован в телеграм'))
		                        ->default(false)
	                        ->select('export_compatibility', __('Совместимость вывода данных'))
		                        ->description(
			                        __('Вы решаете сами, в каком формате сохранять данные. По умолчанию: Общая совместимость') .
			                        '<ul>' .
			                        __('<li><b>Общая совместимость</b>: Сохраняет данные так, что их можно импортировать и в MySQL и в MariaDB</li>') .
			                        __('<li><b>Текущая база данных</b>: Вычисляет какая база данных используется и генерирует для неё файл экспорта. Совместимость может страдать</li>') .
			                        '</ul>',
		                        )
		                        ->options([
			                        'compatibility' => __('Общая совместимость'),
			                        'current'       => __('Текущая база данных'),
		                        ])
		                        ->default('compatibility')
	                        ->select('key_export', __('Вывод ключей'))
		                        ->description(
			                        __('Куда выводить ключи (UNIQUE, ForeignKey, ...) при генерации скрипта?<br><em><b>По умолчанию:</b> в самый низ, после таблиц</em>'),
		                        )
		                        ->options([
			                        'down'  => __('В самый низ, после всех таблиц (рекомендуется)'),
			                        'after' => __('После самой таблицы'),
		                        ])
		                        ->default('down')
	                        ->select('values_export', __('Вывод данных'))
		                        ->description(
			                        __('Куда выводить данные таблиц при генерации скрипта?<br><em><b>По умолчанию:</b> в самый низ, после таблиц</em>'),
		                        )
		                        ->options([
			                        'down'  => __('В самый низ, после всех таблиц (рекомендуется)'),
			                        'after' => __('После самой таблицы'),
		                        ])
		                        ->default('down')
	                        ->select('values_export_type', __('Генерация данных'))
		                        ->description(
			                        __('Как выводить данные таблиц при генерации скрипта?<br><em><b>По умолчанию:</b> В группе</em>'),
		                        )
		                        ->options([
			                        'group'  => __('В группе (рекомендуется)'),
			                        'single' => __('Каждый по отдельности'),
		                        ])
		                        ->default('group')
	                        ->select('zip_data', __('Архивировать данные?'))
		                        ->description(
			                        __('Если включено, то данные будут архивированы в выбранном формате<br><em><b>По умолчанию:</b> без архивации</em>'),
		                        )
		                        ->options([
			                        'raw'   => __('Без архивации'),
			                        'zip'   => __('Архивировать как ZIP архив'),
			                        'bzip2' => __('Архивировать как BZip2 архив'),
		                        ])
		                        ->default('raw')
                        ->section(__('Настройка бота'))
	                        ->text('tg_token', __('Укажите токен вашего бота'))
		                        ->description(
			                        __('Не давайте доступа к настройкам никому. Как узнать токен бота — <a href="https://readme.devcraft.club/latest/dev/telegramposting/bot/#_2" target="_blank" rel="noopener">здесь</a>.'),
		                        )
		                        ->default('')
	                        ->text('tg_chat', __('Укажите ID канала'))
		                        ->description(
			                        __('Не давайте доступа к настройкам никому. Как узнать ID чата — <a href="https://readme.devcraft.club/latest/dev/telegramposting/bot/#id/" target="_blank" rel="noopener">здесь</a>.'),
		                        )
		                        ->default('')
                        ->build();
