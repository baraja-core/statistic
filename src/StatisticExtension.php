<?php

declare(strict_types=1);

namespace Baraja\Statistic;


use Baraja\Doctrine\ORM\DI\OrmAnnotationsExtension;
use Baraja\Plugin\Component\VueComponent;
use Baraja\Plugin\PluginComponentExtension;
use Baraja\Plugin\PluginManager;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;

final class StatisticExtension extends CompilerExtension
{
	/**
	 * @return string[]
	 */
	public static function mustBeDefinedBefore(): array
	{
		return [OrmAnnotationsExtension::class];
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		PluginComponentExtension::defineBasicServices($builder);
		OrmAnnotationsExtension::addAnnotationPathToManager(
			$builder,
			'Baraja\Statistic\Entity',
			__DIR__ . '/Entity',
		);

		$builder->addDefinition($this->prefix('statisticManager'))
			->setFactory(StatisticManager::class);

		/** @var ServiceDefinition $pluginManager */
		$pluginManager = $this->getContainerBuilder()->getDefinitionByType(PluginManager::class);
		$pluginManager->addSetup(
			'?->addComponent(?)',
			[
				'@self',
				[
					'key' => 'statisticDefault',
					'name' => 'cms-statistic-default',
					'implements' => StatisticPlugin::class,
					'componentClass' => VueComponent::class,
					'view' => 'default',
					'source' => __DIR__ . '/../template/default.js',
					'position' => 100,
					'tab' => 'Statistic',
					'params' => [],
				],
			]
		);
		$pluginManager->addSetup(
			'?->addComponent(?)', [
				'@self',
				[
					'key' => 'statisticTable',
					'name' => 'cms-statistic-table',
					'implements' => StatisticPlugin::class,
					'componentClass' => VueComponent::class,
					'view' => 'detail',
					'source' => __DIR__ . '/../template/table.js',
					'position' => 100,
					'tab' => 'Table',
					'params' => [
						'id',
					],
				],
			]
		);
	}
}
