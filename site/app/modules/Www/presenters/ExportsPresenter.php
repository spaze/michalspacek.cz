<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

/**
 * Exports presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ExportsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Exports */
	protected $exports;


	/**
	 * @param \MichalSpacekCz\Exports $exports
	 */
	public function __construct(\MichalSpacekCz\Exports $exports)
	{
		$this->exports = $exports;
		parent::__construct();
	}


	public function actionArticles(?string $param = null): void
	{
		$feed = $this->exports->getArticles($this->link('//this'), $param);
		$this->lastModified($feed->getUpdated(), sha1((string)$feed), '1 hour');
		$this->sendResponse(new \Spaze\Exports\Bridges\Nette\Atom\Response($feed));
	}

}
