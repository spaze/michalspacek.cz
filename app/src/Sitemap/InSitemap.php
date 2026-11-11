<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

use Attribute;

/**
 * Lists the action's page in the sitemap. An action with parameters names the class listing their values,
 * one entry per page, an action without parameters is a single page and needs no class.
 *
 * Every action of a presenter in a module with a sitemap has this or NotInSitemap, so a new page is a decision.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class InSitemap
{

	/**
	 * @param class-string<SitemapEntries>|null $entries
	 */
	public function __construct(
		public ?string $entries = null,
	) {
	}

}
