<?php

declare(strict_types=1);

namespace Baraja\Statistic\Entity;


use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

/**
 * @method Translation getName(?string $locale = null)
 * @method void setName(string $label, ?string $locale = null)
 */
#[ORM\Entity]
#[ORM\Table(name: 'core__statistic')]
class Statistic
{
	use TranslateObject;

	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true, options: ['unsigned' => true])]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\Column(type: 'translate')]
	protected Translation $name;

	/** @var Collection<StatisticField> */
	#[ORM\OneToMany(mappedBy: 'statistic', targetEntity: StatisticField::class)]
	protected Collection $fields;

	#[ORM\Column(name: '`sql`', type: 'text')]
	private string $sql;

	#[ORM\Column(type: 'datetime')]
	private \DateTimeInterface $insertedDate;

	#[ORM\Column(type: 'datetime', nullable: true)]
	private ?\DateTimeInterface $lastIndex = null;

	#[ORM\Column(type: 'text', nullable: true)]
	private ?string $resultCache = null;


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


	public function getId(): int
	{
		return $this->id;
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
		foreach ($return as $key => $variable) {
			if (Validators::isNumericInt($variable)) {
				unset($return[$key]);
			}
		}

		return array_values($return);
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
	 * @return Collection<StatisticField>
	 */
	public function getFields(): Collection
	{
		return $this->fields;
	}
}
