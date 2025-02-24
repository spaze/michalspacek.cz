<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Routing;

final readonly class RouterFactoryRoute
{

	/**
	 * @param array<string, array<string, string>>|null $initialMetadata
	 */
	public function __construct(
		public string $mask,
		public string $defaultPresenter,
		public string $defaultAction,
		public ?array $initialMetadata = null,
		public RouterRoutes $class = RouterRoutes::Route,
	) {
	}

}
