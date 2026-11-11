<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\Sitemap;

use MichalSpacekCz\Http\FetchMetadata\ResourceIsolationPolicyCrossSite;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Sitemap\NotInSitemap;
use MichalSpacekCz\Sitemap\Sitemap;
use MichalSpacekCz\Sitemap\SitemapModule;
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
	 * Only an ETag, no Last-Modified, for the reason the Www sitemap gives.
	 */
	#[ResourceIsolationPolicyCrossSite]
	#[NotInSitemap('the sitemap itself')]
	public function actionDefault(): never
	{
		$xml = $this->sitemap->getXml(SitemapModule::Pulse);
		$this->lastModified(null, Hash::nonCryptographic($xml), '1 hour');
		$this->getHttpResponse()->setContentType('application/xml', 'utf-8');
		$this->sendResponse(new TextResponse($xml));
	}

}
