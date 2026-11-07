<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Sitemap;

use MichalSpacekCz\Http\FetchMetadata\ResourceIsolationPolicyCrossSite;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Sitemap\NotInSitemap;
use MichalSpacekCz\Sitemap\Sitemap;
use MichalSpacekCz\Utils\Hash;
use Nette\Application\Responses\TextResponse;

final class SitemapPresenter extends BasePresenter
{

	public function __construct(
		private readonly Sitemap $sitemap,
	) {
		parent::__construct();
	}


	/**
	 * Only an ETag, no Last-Modified: most pages have no date to go by, so the newest date there is would say
	 * nothing has changed after a talk was added.
	 */
	#[ResourceIsolationPolicyCrossSite]
	#[NotInSitemap('the sitemap itself')]
	public function actionDefault(): never
	{
		$xml = $this->sitemap->getXml();
		$this->lastModified(null, Hash::nonCryptographic($xml), '1 hour');
		$this->getHttpResponse()->setContentType('application/xml', 'utf-8');
		$this->sendResponse(new TextResponse($xml));
	}

}
