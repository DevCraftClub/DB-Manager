<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Services;

use DevCraft\Core\Support\DataManager;

/**
 * Пути к каталогу резервных копий и проверка безопасности имён файлов.
 */
final class BackupPathHelper {

	public const DEFAULT_EXPORT_PATH = 'devcraft/backup';

	/**
	 * Абсолютный путь к каталогу экспорта.
	 */
	public static function exportDir(array $settings): string {
		$relative = trim((string) ($settings['export_path'] ?? self::DEFAULT_EXPORT_PATH));

		if($relative === '') {
			$relative = self::DEFAULT_EXPORT_PATH;
		}

		return DataManager::joinPaths(ROOT_DIR, $relative);
	}

	/**
	 * Возвращает проверенный абсолютный путь к файлу внутри каталога экспорта.
	 *
	 * @throws \RuntimeException При недопустимом имени или выходе за пределы каталога.
	 */
	public static function resolveFile(string $fileName, array $settings): string {
		$basename = basename($fileName);

		if($basename === '' || str_contains($basename, '..')) {
			throw new \RuntimeException(__('Недопустимое имя файла'));
		}

		$exportDir = self::exportDir($settings);
		$filePath  = DataManager::joinPaths($exportDir, $basename);
		$realDir   = realpath($exportDir);

		if($realDir === false || !is_dir($realDir)) {
			throw new \RuntimeException(__('Каталог экспорта не найден'));
		}

		$realFile = realpath($filePath);

		if($realFile === false || !is_file($realFile)) {
			throw new \RuntimeException(__('Файл не найден'));
		}

		$dirPrefix = rtrim($realDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		if(!str_starts_with($realFile, $dirPrefix)) {
			throw new \RuntimeException(__('Доступ к файлу запрещён'));
		}

		return $realFile;
	}

}
