<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

class RouterFactoryRoute
{

	/**
	 * @param array<string, array<string, string>>|null $initialMetadata
	 */
	public function __construct(
		public readonly string $mask,
		public readonly string $defaultPresenter,
		public readonly string $defaultAction,
		public readonly ?array $initialMetadata = null,
		public readonly RouterRoutes $class = RouterRoutes::Route,
	) {
	}

}
