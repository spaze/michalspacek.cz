<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTime;
use stdClass;

class Algorithm
{

	private string $id;

	private string $name;

	private string $alias;

	private bool $salted;

	private bool $stretched;

	private ?DateTime $from;

	private bool $fromConfirmed;

	private ?stdClass $attributes;

	private ?stdClass $params;

	private ?string $note;

	private ?string $fullAlgo;

	private ?StorageDisclosure $latestDisclosure = null;

	/** @var array<int, StorageDisclosure> */
	private array $disclosures = [];

	/** @var array<string, bool> */
	private array $disclosureTypes = [];


	public function __construct(string $id, string $name, string $alias, bool $salted, bool $stretched, ?DateTime $from, bool $fromConfirmed, ?stdClass $attributes, ?string $note)
	{
		$this->id = $id;
		$this->name = $name;
		$this->alias = $alias;
		$this->salted = $salted;
		$this->stretched = $stretched;
		$this->from = $from;
		$this->fromConfirmed = $fromConfirmed;
		$this->attributes = $attributes;
		$this->params = $attributes->params ?? null;
		$this->note = $note;
		$this->fullAlgo = $this->formatFullAlgo($this->name, $this->attributes);
	}


	/**
	 * Format full algo, if needed
	 *
	 * @param string $name main algo name
	 * @param stdClass|null $attrs attributes
	 * @return string|null String of formatted algos, null if no inner or outer hashes used
	 */
	private function formatFullAlgo(string $name, ?stdClass $attrs = null): ?string
	{
		if (!isset($attrs->inner) && !isset($attrs->outer)) {
			return null;
		}

		$result = '';
		$count = 0;
		if (isset($attrs->outer)) {
			for ($i = count($attrs->outer) - 1; $i >= 0; $i--) {
				$result .= $attrs->outer[$i] . '(';
				$count++;
			}
		}
		$result .= $name . '(';
		$count++;
		if (isset($attrs->inner)) {
			for ($i = count($attrs->inner) - 1; $i >= 0; $i--) {
				$result .= $attrs->inner[$i] . '(';
				$count++;
			}
		}
		return $result . 'password' . str_repeat(')', $count);
	}


	public function getId(): string
	{
		return $this->id;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getAlias(): string
	{
		return $this->alias;
	}


	public function isSalted(): bool
	{
		return $this->salted;
	}


	public function isStretched(): bool
	{
		return $this->stretched;
	}


	public function getFrom(): ?DateTime
	{
		return $this->from;
	}


	public function isFromConfirmed(): bool
	{
		return $this->fromConfirmed;
	}


	public function getAttributes(): ?stdClass
	{
		return $this->attributes;
	}


	public function getParams(): ?stdClass
	{
		return $this->params;
	}


	public function getNote(): ?string
	{
		return $this->note;
	}


	public function getFullAlgo(): ?string
	{
		return $this->fullAlgo;
	}


	public function addDisclosure(StorageDisclosure $disclosure): void
	{
		$this->disclosures[] = $disclosure;
		$this->latestDisclosure = $disclosure;
		$this->disclosureTypes[$disclosure->getTypeAlias()] = true;
	}


	/**
	 * @return array<int, StorageDisclosure>
	 */
	public function getDisclosures(): array
	{
		return $this->disclosures;
	}


	public function getLatestDisclosure(): ?StorageDisclosure
	{
		return $this->latestDisclosure;
	}


	public function hasDisclosureType(string $disclosure): bool
	{
		return isset($this->disclosureTypes[$disclosure]);
	}


	/**
	 * @return array<int, string>
	 */
	public function getDisclosureTypes(): array
	{
		return array_keys($this->disclosureTypes);
	}

}
