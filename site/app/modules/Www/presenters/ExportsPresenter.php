<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Exports;
use Spaze\Exports\Bridges\Nette\Atom\Response;

/**
 * Exports presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ExportsPresenter extends BasePresenter
{

	/** @var Exports */
	protected $exports;


	/**
	 * @param Exports $exports
	 */
	public function __construct(Exports $exports)
	{
		$this->exports = $exports;
		parent::__construct();
	}


	public function actionArticles(?string $param = null): void
	{
		$feed = $this->exports->getArticles($this->link('//this'), $param);
		$this->lastModified($feed->getUpdated(), sha1((string)$feed), '1 hour');
		$this->sendResponse(new Response($feed));
	}

}
