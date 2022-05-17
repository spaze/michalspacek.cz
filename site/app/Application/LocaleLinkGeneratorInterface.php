<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

interface LocaleLinkGeneratorInterface
{

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
