<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Application;

use MichalSpacekCz\Application\LocaleLinkGeneratorInterface;

class LocaleLinkGenerator implements LocaleLinkGeneratorInterface
{

	/** @var array<string, string> */
	private array $allLinks;


	/**
	 * @inheritDoc
	 */
	public function links(string $destination, array $params = []): array
	{
		return [];
	}


	/**
	 * @inheritDoc
	 */
	public function defaultParams(array $params): array
	{
		return [];
	}


	/**
	 * @inheritDoc
	 */
	public function setDefaultParams(array &$params, array $defaultParams): void
	{
	}


	/**
	 * @param array<string, string> $allLinks
	 */
	public function setAllLinks(array $allLinks): void
	{
		$this->allLinks = $allLinks;
	}


	/**
	 * @inheritDoc
	 */
	public function allLinks(string $destination, array $params = []): array
	{
		return $this->allLinks;
	}

}
