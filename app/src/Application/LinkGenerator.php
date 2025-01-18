<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\LinkGenerator as NetteLinkGenerator;
use Nette\Application\UI\InvalidLinkException;

readonly class LinkGenerator
{

	public function __construct(
		private NetteLinkGenerator $linkGenerator,
	) {
	}


	/**
	 * Same as `Nette\Application\LinkGenerator::link()` but will always return just string, not string|null.
	 *
	 * @param array<array-key, mixed> $args
	 * @throws InvalidLinkException
	 */
	public function link(string $destination, array $args = [], ?NetteLinkGenerator $linkGenerator = null): string
	{
		$link = ($linkGenerator ?? $this->linkGenerator)->link($destination, $args);
		if ($link === null) {
			throw new ShouldNotHappenException('Link should be a string, null returned');
		}
		return $link;
	}

}
