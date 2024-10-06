<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Application;

use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use Override;

class LocaleLinkGeneratorMock extends LocaleLinkGenerator
{

	/** @var array<string, string> */
	private array $allLinks = [];


	/** @noinspection PhpMissingParentConstructorInspection Intentionally */
	public function __construct()
	{
	}


	#[Override]
	public function links(string $destination, array $params = []): array
	{
		return [];
	}


	#[Override]
	public function defaultParams(array $params): array
	{
		return ['*' => $params];
	}


	#[Override]
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


	#[Override]
	public function allLinks(string $destination, array $params = []): array
	{
		return $this->allLinks;
	}

}
