<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

use Attribute;

/**
 * Keeps the action's page out of the sitemap, for the reason given.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class NotInSitemap
{

	public function __construct(
		public string $reason,
	) {
	}

}
