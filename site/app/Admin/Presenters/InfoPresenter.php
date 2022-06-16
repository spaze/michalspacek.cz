<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use Nette\Utils\Html;
use Spaze\PhpInfo\PhpInfo;

class InfoPresenter extends BasePresenter
{

	public function __construct(
		private readonly PhpInfo $phpInfo,
	) {
		parent::__construct();
	}


	public function renderPhp(): void
	{
		$this->template->pageTitle = 'phpinfo()';
		$this->template->phpinfo = Html::el()->setHtml($this->phpInfo->getHtml());
	}

}
