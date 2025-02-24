<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Feed\Exports;
use MichalSpacekCz\Http\FetchMetadata\ResourceIsolationPolicyCrossSite;
use MichalSpacekCz\Utils\Hash;
use Spaze\Exports\Bridges\Nette\Atom\Response;

final class ExportsPresenter extends BasePresenter
{

	public function __construct(
		private readonly Exports $exports,
	) {
		parent::__construct();
	}


	#[ResourceIsolationPolicyCrossSite]
	public function actionArticles(?string $param = null): never
	{
		$feed = $this->exports->getArticles($this->link('//this'), $param);
		$updated = $feed->getUpdated();
		if ($updated) {
			$this->lastModified($updated, Hash::nonCryptographic((string)$feed), '1 hour');
		}
		$this->sendResponse(new Response($feed));
	}

}
