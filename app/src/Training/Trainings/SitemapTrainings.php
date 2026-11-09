<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

/**
 * The trainings Trainings::getNames() lists, with the alternates the training page links to itself. A service, so the
 * training pages and their reviews pages in the sitemap share one list instead of each building its own; the container
 * keeps it for as long as it serves the request.
 */
final readonly class SitemapTrainings
{

	/**
	 * @param array<int, array<string, array<array-key, mixed>>> $localeParams training id => locale => the parameters of its page in that locale
	 */
	public function __construct(
		private array $localeParams,
	) {
	}


	/**
	 * @return array<int, array<string, array<array-key, mixed>>> training id => locale => the parameters of its page in that locale
	 */
	public function getLocaleParams(): array
	{
		return $this->localeParams;
	}

}
