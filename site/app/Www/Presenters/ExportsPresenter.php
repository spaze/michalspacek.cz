<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Feed\Exports;
use Spaze\Exports\Atom\Feed;
use Spaze\Exports\Bridges\Nette\Atom\Response;

class ExportsPresenter extends BasePresenter
{

	public function __construct(
		private readonly Exports $exports,
	) {
		parent::__construct();
	}


	public function actionArticles(?string $param = null): never
	{
		$feed = $this->exports->getArticles($this->link('//this'), $param);
		$updated = $feed->getUpdated();
		if ($updated) {
			$this->lastModified($updated, $this->getEtag($feed), '1 hour');
		}
		$this->sendResponse(new Response($feed));
	}


	private function getEtag(Feed $feed): string
	{
		return sha1((string)$feed);
	}

}
