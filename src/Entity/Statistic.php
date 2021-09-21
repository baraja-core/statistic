<?php

declare(strict_types=1);

namespace Baraja\Statistic\Entity;


use Baraja\Doctrine\Identifier\IdentifierUnsigned;
use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Nette\Utils\Strings;

/**
 * @method Translation getName(?string $locale = null)
 * @method void setName(string $label, ?string $locale = null)
 */
#[Entity]
#[Table(name: 'core__statistic')]
final class Statistic
{
	use IdentifierUnsigned;
	use TranslateObject;

	#[Column(type: 'translate')]
	private Translation $name;

	#[Column(name: '`sql`', type: 'text')]
	private string $sql;

	#[Column(type: 'datetime')]
	private \DateTimeInterface $insertedDate;

	#[Column(type: 'datetime', nullable: true)]
	private ?\DateTimeInterface $lastIndex = null;

	#[Column(type: 'text', nullable: true)]
	private ?string $resultCache = null;

	/** @var StatisticField[]|Collection */
	#[OneToMany(mappedBy: 'statistic', targetEntity: StatisticField::class)]
	private $fields;


	public function __construct(string $name, string $sql)
	{
		$this->setName($name);
		$this->setSql($sql);
		$this->insertedDate = new \DateTimeImmutable;
	}


	public static function normalizeSql(string $sql): string
	{
		$sql = trim(Strings::normalize($sql));
		if ($sql === '') {
			throw new \InvalidArgumentException('SQL can not be empty.');
		}
		if (!str_contains($sql, 'SELECT ')) {
			throw new \InvalidArgumentException('Statistic SQL must start with "SELECT" keyword.');
		}
		$return = '';
		foreach (explode("\n", $sql) as $line) { // rtrim all lines
			$return .= ($return !== '' ? "\n" : '') . rtrim($line);
		}

		return $return;
	}


	public function getSql(): string
	{
		return $this->sql;
	}


	public function setSql(string $sql): void
	{
		$this->sql = self::normalizeSql($sql);
	}


	/**
	 * @return array<int, string>
	 */
	public function getVariables(): array
	{
		$return = [];
		if (preg_match_all('/:([a-z0-9-]+)/', $this->getSql(), $match) > 0) {
			$return = $match[1] ?? [];
		}

		return $return;
	}


	public function getInsertedDate(): \DateTimeInterface
	{
		return $this->insertedDate;
	}


	public function getLastIndex(): ?\DateTimeInterface
	{
		return $this->lastIndex;
	}


	public function setLastIndex(?\DateTimeInterface $lastIndex): void
	{
		$this->lastIndex = $lastIndex;
	}


	public function getResultCache(): ?string
	{
		return $this->resultCache;
	}


	public function setResultCache(?string $resultCache): void
	{
		$this->resultCache = $resultCache;
	}


	/**
	 * @return StatisticField[]|Collection
	 */
	public function getFields()
	{
		return $this->fields;
	}
}
