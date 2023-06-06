<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTime;

class Algorithm
{

	private ?string $fullAlgo;

	private StorageDisclosure $latestDisclosure;

	/** @var array<int, StorageDisclosure> */
	private array $disclosures = [];

	/** @var array<string, bool> */
	private array $disclosureTypes = [];


	public function __construct(
		private readonly string $id,
		private readonly string $name,
		private readonly string $alias,
		private readonly bool $salted,
		private readonly bool $stretched,
		private readonly ?DateTime $from,
		private readonly bool $fromConfirmed,
		private readonly AlgorithmAttributes $attributes,
		private readonly ?string $note,
		StorageDisclosure $disclosure,
	) {
		$this->addDisclosure($disclosure);
		$this->fullAlgo = $this->formatFullAlgo($this->name, $this->attributes->getInner(), $this->attributes->getOuter());
	}


	/**
	 * Format full algo, if needed
	 *
	 * @param string $name main algo name
	 * @param list<string>|null $inner
	 * @param list<string>|null $outer
	 * @return string|null String of formatted algos, null if no inner or outer hashes used
	 */
	private function formatFullAlgo(string $name, ?array $inner, ?array $outer): ?string
	{
		if (!$inner && !$outer) {
			return null;
		}

		$result = '';
		$count = 0;
		if ($outer) {
			for ($i = count($outer) - 1; $i >= 0; $i--) {
				$result .= $outer[$i] . '(';
				$count++;
			}
		}
		$result .= $name . '(';
		$count++;
		if ($inner) {
			for ($i = count($inner) - 1; $i >= 0; $i--) {
				$result .= $inner[$i] . '(';
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


	public function getAttributes(): AlgorithmAttributes
	{
		return $this->attributes;
	}


	/**
	 * @return array<string, string>|null
	 */
	public function getParams(): ?array
	{
		return $this->attributes->getParams();
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


	public function getLatestDisclosure(): StorageDisclosure
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
