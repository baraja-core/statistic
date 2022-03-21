<?php

declare(strict_types=1);

namespace Baraja\Statistic;


use Baraja\Doctrine\EntityManager;
use Baraja\Statistic\Entity\StatisticField;
use Baraja\StructuredApi\BaseEndpoint;
use Nette\Utils\Validators;

final class CmsStatisticEndpoint extends BaseEndpoint
{
	public function __construct(
		private StatisticManager $statisticManager,
		private EntityManager $entityManager,
	) {
	}


	public function actionDefault(): void
	{
		$return = [];
		foreach ($this->statisticManager->getList() as $statistic) {
			$return[] = [
				'id' => $statistic->getId(),
				'name' => $statistic->getName(),
				'lastIndex' => $statistic->getLastIndex(),
			];
		}

		$this->sendJson(
			[
				'statistics' => $return,
			],
		);
	}


	public function actionDetail(int $id): void
	{
		$statistic = $this->statisticManager->getById($id);

		$variables = $statistic->getVariables();
		$usedVariables = array_flip(array_values($variables));

		$fields = [];
		foreach ($statistic->getFields() as $field) {
			$fieldName = $field->getName();
			$valuesSql = $field->getValuesSql();
			$fields[$fieldName] = [
				'id' => $field->getId(),
				'name' => $fieldName,
				'type' => $field->getType(),
				'haystack' => null,
				'values' => $valuesSql !== null
					? $this->formatBootstrapSelectArray(
						[null => '--- select ---']
						+ $this->statisticManager->getPossibleValuesBySql($valuesSql),
					)
					: null,
			];
			if (isset($usedVariables[$fieldName])) {
				unset($usedVariables[$fieldName]);
			}
		}

		$this->sendJson(
			[
				'fieldTypes' => $this->formatBootstrapSelectArray(
					array_combine(StatisticField::TYPES, StatisticField::TYPES),
				),
				'fields' => $fields,
				'variables' => $variables,
				'usedAll' => $usedVariables === [],
				'sql' => $statistic->getSql(),
			],
		);
	}


	public function postCreateStatistic(string $name, string $sql): void
	{
		$this->statisticManager->create($name, $sql);
		$this->entityManager->flush();
		$this->flashMessage('Statistic has been created.', self::FLASH_MESSAGE_SUCCESS);
		$this->sendOk();
	}


	public function postAddField(int $id, string $name, string $type, ?string $valuesSql = null): void
	{
		$statistic = $this->statisticManager->getById($id);
		$this->statisticManager->addField($statistic, $name, $type, $valuesSql);
		$this->entityManager->flush();
		$this->flashMessage('Field has been created.', self::FLASH_MESSAGE_SUCCESS);
		$this->sendOk();
	}


	public function postSaveSql(int $id, string $sql): void
	{
		$statistic = $this->statisticManager->getById($id);
		$statistic->setSql($sql);
		$this->entityManager->flush();
		$this->flashMessage('SQL has been changed.', self::FLASH_MESSAGE_SUCCESS);
		$this->sendOk();
	}


	/**
	 * @param array<string, array{name: string, haystack: string|int|null}> $fields
	 */
	public function postLoadTable(int $id, array $fields): void
	{
		$statistic = $this->statisticManager->getById($id);

		$variables = [];
		foreach ($fields as $field) {
			$value = (string) $field['haystack'];
			$variables[$field['name']] = Validators::isNumeric($value)
				? (float) $value
				: $value;
		}

		$result = $this->statisticManager->executeSql(
			$statistic->getSql(),
			$variables,
		);

		$this->sendJson(
			[
				'header' => isset($result[0])
					? array_keys($result[0])
					: [],
				'body' => $result,
			],
		);
	}
}
