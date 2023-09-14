<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Application;

use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;

class LocaleLinkGeneratorMock extends LocaleLinkGenerator
{

	/** @var array<string, string> */
	private array $allLinks = [];


	/** @noinspection PhpMissingParentConstructorInspection Intentionally */
	public function __construct()
	{
	}


	public function links(string $destination, array $params = []): array
	{
		return [];
	}


	public function defaultParams(array $params): array
	{
		return ['*' => $params];
	}


	public function setDefaultParams(array &$params, array $defaultParams): void
	{
		$params['*'] = $defaultParams;
	}


	/**
	 * @param array<string, string> $allLinks
	 */
	public function setAllLinks(array $allLinks): void
	{
		$this->allLinks = $allLinks;
	}


	public function allLinks(string $destination, array $params = []): array
	{
		return $this->allLinks;
	}

}
