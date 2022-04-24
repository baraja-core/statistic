<?php

declare(strict_types=1);

namespace Baraja\Statistic;


use Baraja\Plugin\BasePlugin;

final class StatisticPlugin extends BasePlugin
{
	public function __construct(
		private StatisticManager $statisticManager,
	) {
	}


	public function getName(): string
	{
		return 'Statistic';
	}


	public function actionDetail(int $id): void
	{
		$statistic = $this->statisticManager->getById($id);
		$this->setTitle(sprintf('(%d) %s', $id, (string) $statistic->getName()));
	}
}
