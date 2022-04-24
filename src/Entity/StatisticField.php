<?php

declare(strict_types=1);

namespace Baraja\Statistic\Entity;


use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

/**
 * @method Translation getLabel(?string $locale = null)
 * @method void setLabel(string $label, ?string $locale = null)
 */
#[ORM\Entity]
#[ORM\Table(name: 'core__statistic_field')]
class StatisticField
{
	use TranslateObject;

	public const
		TypeText = 'text',
		TypeInt = 'int',
		TypeDatetime = 'datetime',
		TypeEnum = 'enum';

	public const Types = [
		self::TypeText,
		self::TypeInt,
		self::TypeDatetime,
		self::TypeEnum,
	];

	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true, options: ['unsigned' => true])]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\Column(type: 'translate')]
	protected Translation $label;

	#[ORM\ManyToOne(targetEntity: Statistic::class)]
	private Statistic $statistic;

	#[ORM\Column(type: 'string', length: 32)]
	private string $name;

	#[ORM\Column(type: 'string', length: 16)]
	private string $type;

	#[ORM\Column(type: 'text', nullable: true)]
	private ?string $valuesSql = null;


	public function __construct(Statistic $statistic, string $name, string $type)
	{
		$name = trim($name);
		if ($name === '') {
			throw new \InvalidArgumentException('Field name can not be empty.');
		}
		if (!in_array($type, self::Types, true)) {
			throw new \InvalidArgumentException(sprintf('Type "%s" is not valid option. Did you mean "%s"?', $type, implode('", "', self::Types)));
		}
		$this->statistic = $statistic;
		$this->name = Strings::webalize($name);
		$this->setLabel(Strings::firstUpper(str_replace('-', ' ', $name)));
		$this->type = $type;
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getStatistic(): Statistic
	{
		return $this->statistic;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getType(): string
	{
		return $this->type;
	}


	public function setType(string $type): void
	{
		$this->type = $type;
	}


	public function getValuesSql(): ?string
	{
		return $this->valuesSql;
	}


	public function setValuesSql(?string $valuesSql): void
	{
		if ($valuesSql !== null) {
			$valuesSql = Statistic::normalizeSql($valuesSql);
		}
		$this->valuesSql = $valuesSql;
	}
}
