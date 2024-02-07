<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use DateTime;
use MichalSpacekCz\Pulse\Passwords\Algorithms\PasswordHashingAlgorithm;

class StorageAlgorithm
{

	private ?string $fullAlgo;

	private StorageDisclosure $latestDisclosure;

	/** @var array<int, StorageDisclosure> */
	private array $disclosures = [];

	/** @var array<string, bool> */
	private array $disclosureTypes = [];


	public function __construct(
		private readonly string $id,
		private readonly PasswordHashingAlgorithm $hashingAlgorithm,
		private readonly ?DateTime $from,
		private readonly bool $fromConfirmed,
		private readonly StorageAlgorithmAttributes $attributes,
		private readonly ?string $note,
		StorageDisclosure $disclosure,
	) {
		$this->addDisclosure($disclosure);
		$this->fullAlgo = $this->formatFullAlgo($this->hashingAlgorithm->getName(), $this->attributes->getInner(), $this->attributes->getOuter());
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
		if (($inner === null || $inner === []) && ($outer === null || $outer === [])) {
			return null;
		}

		$result = '';
		$count = 0;
		if ($outer !== null) {
			for ($i = count($outer) - 1; $i >= 0; $i--) {
				$result .= $outer[$i] . '(';
				$count++;
			}
		}
		$result .= $name . '(';
		$count++;
		if ($inner !== null) {
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
		return $this->hashingAlgorithm->getName();
	}


	public function getAlias(): string
	{
		return $this->hashingAlgorithm->getAlias();
	}


	public function isSalted(): bool
	{
		return $this->hashingAlgorithm->isSalted();
	}


	public function isStretched(): bool
	{
		return $this->hashingAlgorithm->isStretched();
	}


	public function getFrom(): ?DateTime
	{
		return $this->from;
	}


	public function isFromConfirmed(): bool
	{
		return $this->fromConfirmed;
	}


	public function getAttributes(): StorageAlgorithmAttributes
	{
		return $this->attributes;
	}


	/**
	 * @return array<string, string|int>|null
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


	final public function addDisclosure(StorageDisclosure $disclosure): void
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
