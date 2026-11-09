<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

final readonly class SitemapAction
{

	/**
	 * @param list<string> $methods the action and render methods the attributes were looked for on, none for an action with a template only
	 * @param list<InSitemap> $inSitemap
	 * @param list<NotInSitemap> $notInSitemap
	 */
	public function __construct(
		public string $destination,
		public array $methods,
		public array $inSitemap,
		public array $notInSitemap,
	) {
	}

}
