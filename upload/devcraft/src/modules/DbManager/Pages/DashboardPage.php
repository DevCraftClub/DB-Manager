<?php

declare(strict_types=1);

namespace DevCraft\Modules\DbManager\Pages;

use DevCraft\Core\Application;
use DevCraft\Core\Abstracts\AbstractPage;

/**
 * Главная страница (панель) модуля DB Manager.
 */
final class DashboardPage extends AbstractPage {

	public function handle(): array {
		$registry = Application::instance()->registry();
		$plugin   = $registry->forMod('db_manager');
		$meta     = $plugin?->meta() ?? [];
		$context  = $this->adminContext();

		$changelog = $plugin?->changelog() ?? [];
		$latest    = isset($changelog[0]) ? $changelog[0]->toArray() : NULL;

		if($latest !== NULL) {
			$latest['teaser_items'] = $changelog[0]->teaserItems(3);
		}

		$menu = [];

		foreach($context->menu() as $link) {
			if($link->type !== 'link' || $link->action === NULL || $link->action === 'dashboard') {
				continue;
			}

			$menu[] = [
				'name'   => $link->name,
				'link'   => $link->link,
				'icon'   => $link->extra,
				'action' => $link->action,
			];
		}

		return [
			'view' => 'dbmanager/dashboard.twig',
			'data' => [
				'page_title' => (string) ($meta['name'] ?? 'DB Manager'),
				'dashboard'  => [
					'app'              => [
						'name'        => (string) ($meta['name'] ?? 'DB Manager'),
						'version'     => (string) ($meta['version'] ?? '0.0.0'),
						'description' => (string) ($meta['description'] ?? ''),
						'icon'        => (string) ($meta['icon'] ?? ''),
						'docs_link'   => (string) ($meta['docsLink'] ?? ''),
						'site_link'   => (string) ($meta['siteLink'] ?? ''),
						'site_id'     => (int) ($meta['siteId'] ?? 0),
						'code'        => (string) ($meta['module_code'] ?? 'db_manager'),
					],
					'author'           => $context->author()->toArray(),
					'lic_link'         => $context->licLink(),
					'menu'             => $menu,
					'changelog_latest' => $latest,
					'changelog_url'    => '?mod=db_manager&action=changelog',
				],
			],
		];
	}

}
