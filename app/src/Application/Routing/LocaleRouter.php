<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Routing;

use Nette\Application\Routers\RouteList;

final readonly class LocaleRouter
{

	/**
	 * @param array<string, RouteList> $localeRouters
	 */
	public function __construct(
		private array $localeRouters,
		private RouteList $routeList,
	) {
	}


	/**
	 * @return array<string, RouteList>
	 */
	public function getLocaleRouters(): array
	{
		return $this->localeRouters;
	}


	public function getRouteList(): RouteList
	{
		return $this->routeList;
	}

}
