<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\Application\UI\InvalidLinkException;

interface LocaleLinkGeneratorInterface
{

	/**
	 * @param string $destination destination in format "[[[module:]presenter:]action] [#fragment]"
	 * @param array<string, array<string, string|null>> $params of locale => [name => value]
	 * @return array<string, LocaleLink> of locale => URL
	 * @throws InvalidLinkException
	 */
	public function links(string $destination, array $params = []): array;


	/**
	 * @param array<string, string|null> $params
	 * @return array<string, array<string, string|null>>
	 */
	public function defaultParams(array $params): array;


	/**
	 * @param array<string, array<string, string|null>> $params
	 * @param array<string, string|null> $defaultParams
	 */
	public function setDefaultParams(array &$params, array $defaultParams): void;


	/**
	 * @param string $destination
	 * @param array<string, array<string, string>> $params
	 * @return array<string, string>
	 */
	public function allLinks(string $destination, array $params = []): array;

}
