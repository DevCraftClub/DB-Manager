<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Pages;

use DevCraft\Core\Abstracts\AbstractPage;

/**
 * Страница истории изменений модуля DB Manager.
 */
final class ChangelogPage extends AbstractPage {

	public function handle(): array {
		$pageName = __('История изменений');

		$this->addBreadcrumb($pageName);

		return [
			'view' => 'dbmanager/changelog.twig',
			'data' => [
				'page_title' => $pageName,
			],
		];
	}

}
