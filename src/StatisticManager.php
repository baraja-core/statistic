<?php

declare(strict_types=1);

namespace Baraja\Statistic;


use Baraja\Doctrine\EntityManager;
use Baraja\Localization\Translation;
use Baraja\Statistic\Entity\Statistic;
use Baraja\Statistic\Entity\StatisticField;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Utils\Validators;

final class StatisticManager
{
	public function __construct(
		private EntityManager $entityManager,
	) {
	}


	/**
	 * @return array<int, Statistic>
	 */
	public function getList(): array
	{
		return $this->entityManager->getRepository(Statistic::class)
			->findAll();
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getById(int $id): Statistic
	{
		return $this->entityManager->getRepository(Statistic::class)
			->createQueryBuilder('s')
			->where('s.id = :id')
			->setParameter('id', $id)
			->setMaxResults(1)
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @return array<int|string, string>
	 */
	public function getPossibleValuesBySql(string $sql): array
	{
		$results = $this->entityManager->getConnection()
			->executeQuery($sql)
			->fetchAllAssociative();

		if (isset($results[0]) && !isset($results[0]['id'], $results[0]['value'])) {
			throw new \InvalidArgumentException('Invalid SQL. Value SQL must return identifier and value.');
		}

		$return = [];
		foreach ($results as $result) {
			assert(array_key_exists('id', $result) && array_key_exists('value', $result));
			$value = (string) $result['value'];
			$return[$result['id']] = str_starts_with($value, 'T:{')
				? (string) (new Translation($value))
				: $value;
		}

		return $return;
	}


	/**
	 * @param array<string, string|int|float|null> $variables
	 * @return array<int, array<string, string>>
	 */
	public function executeSql(string $sql, array $variables): array
	{
		$sql = (string) preg_replace_callback(
			'/:([a-z0-9-]+)/',
			static function (array $match) use ($variables): string {
				$name = $match[1] ?? '';
				if (Validators::isNumericInt($name)) {
					return $match[0] ?? '';
				}
				if (isset($variables[$name]) === false) {
					throw new \InvalidArgumentException('Variable "' . $name . '" is not defined.');
				}
				$value = (string) $variables[$name];
				if ($value === '') {
					throw new \InvalidArgumentException('Value of variable "' . $name . '" is mandatory.');
				}

				return Validators::isNumeric($value)
					? $value
					: '\'' . addslashes($value) . '\'';
			},
			$sql
		);

		$result = $this->entityManager->getConnection()
			->executeQuery($sql)
			->fetchAllAssociative();

		$return = [];
		foreach ($result as $line) {
			$returnLine = [];
			foreach ($line as $lineKey => $lineValue) {
				if (is_string($lineValue) && str_starts_with($lineValue, 'T:{')) {
					$lineValue = (string) (new Translation($lineValue));
				}
				$returnLine[$lineKey] = $lineValue;
			}
			$return[] = $returnLine;
		}

		return $return;
	}


	public function create(string $name, string $sql): Statistic
	{
		$statistic = new Statistic($name, $sql);
		$this->entityManager->persist($statistic);

		return $statistic;
	}


	public function addField(
		Statistic $statistic,
		string $name,
		string $type,
		?string $valuesSql = null,
	): StatisticField {
		$field = new StatisticField($statistic, $name, $type);
		if ($valuesSql === null && $field->getType() === StatisticField::TYPE_ENUM) {
			throw new \InvalidArgumentException('SQL for values is mandatory in case of field type is enum.');
		}
		try {
			$this->entityManager->getRepository(StatisticField::class)
				->createQueryBuilder('f')
				->where('f.statistic = :statisticId')
				->andWhere('f.name = :name')
				->setParameter('statisticId', $statistic->getId())
				->setParameter('name', $field->getName())
				->setMaxResults(1)
				->getQuery()
				->getSingleResult();

			throw new \InvalidArgumentException(
				'Field name "' . $field->getName() . '" already exist for this statistic '
				. '(' . $statistic->getId() . ') "' . $statistic->getName() . '".',
			);
		} catch (NoResultException | NonUniqueResultException) {
			// Silence is golden.
		}

		$field->setValuesSql($valuesSql);
		$this->entityManager->persist($field);

		return $field;
	}
}
