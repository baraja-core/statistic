<?php

declare(strict_types=1);

namespace Baraja\Statistic\Entity;


use Baraja\Doctrine\Identifier\IdentifierUnsigned;
use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Nette\Utils\Strings;

/**
 * @method Translation getLabel(?string $locale = null)
 * @method void setLabel(string $label, ?string $locale = null)
 */
#[Entity]
#[Table(name: 'core__statistic_field')]
final class StatisticField
{
	use IdentifierUnsigned;
	use TranslateObject;

	public const
		TYPE_TEXT = 'text',
		TYPE_INT = 'int',
		TYPE_DATETIME = 'datetime',
		TYPE_ENUM = 'enum';

	public const TYPES = [
		self::TYPE_TEXT,
		self::TYPE_INT,
		self::TYPE_DATETIME,
		self::TYPE_ENUM,
	];

	#[Column(type: 'translate')]
	protected Translation $label;

	#[ManyToOne(targetEntity: Statistic::class)]
	private Statistic $statistic;

	#[Column(type: 'string', length: 32)]
	private string $name;

	#[Column(type: 'string', length: 16)]
	private string $type;

	#[Column(type: 'text', nullable: true)]
	private ?string $valuesSql = null;


	public function __construct(Statistic $statistic, string $name, string $type)
	{
		$name = trim($name);
		if ($name === '') {
			throw new \InvalidArgumentException('Field name can not be empty.');
		}
		if (!in_array($type, self::TYPES, true)) {
			throw new \InvalidArgumentException(
				'Type "' . $type . '" is not valid option. '
				. 'Did you mean "' . implode('", "', self::TYPES) . '"?',
			);
		}
		$this->statistic = $statistic;
		$this->name = Strings::webalize($name);
		$this->setLabel(Strings::firstUpper(str_replace('-', ' ', $name)));
		$this->type = $type;
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
