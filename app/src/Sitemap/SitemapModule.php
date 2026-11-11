<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

/**
 * The modules with a sitemap, each on its own host with its own Sitemap presenter.
 */
enum SitemapModule: string
{

	case Www = 'Www';
	case Pulse = 'Pulse';

}
