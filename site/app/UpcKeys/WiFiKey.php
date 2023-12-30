<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use JsonSerializable;
use Override;

class WiFiKey implements JsonSerializable
{

	public function __construct(
		private readonly string $serial,
		private readonly string $serialPrefix,
		private readonly ?string $oui,
		private readonly ?string $mac,
		private readonly string $key,
		private readonly WiFiBand $type,
	) {
	}


	public function getSerial(): string
	{
		return $this->serial;
	}


	public function getSerialPrefix(): string
	{
		return $this->serialPrefix;
	}


	public function getOui(): ?string
	{
		return $this->oui;
	}


	public function getMac(): ?string
	{
		return $this->mac;
	}


	public function getKey(): string
	{
		return $this->key;
	}


	public function getType(): WiFiBand
	{
		return $this->type;
	}


	/**
	 * @return array{serial:string, oui:string|null, mac:string|null, key:string, type:string, typeId:int, serialPrefix:string}
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'serial' => $this->serial,
			'oui' => $this->oui,
			'mac' => $this->mac,
			'key' => $this->key,
			'type' => $this->type->getLabel(),
			'typeId' => $this->type->value,
			'serialPrefix' => $this->serialPrefix,
		];
	}

}
