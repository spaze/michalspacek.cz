<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\Application\Routers\Route as ApplicationRoute;

class RouterFactoryRoute
{

	/**
	 * @param array<string, array<string, string>>|null $initialMetadata
	 * @param class-string<ApplicationRoute> $class
	 */
	public function __construct(
		public readonly string $mask,
		public readonly string $defaultPresenter,
		public readonly string $defaultAction,
		public readonly ?array $initialMetadata = null,
		public readonly string $class = ApplicationRoute::class,
	) {
	}

}
