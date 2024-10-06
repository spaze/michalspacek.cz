<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Application\SanitizedPhpInfo;
use Nette\Utils\Html;

class InfoPresenter extends BasePresenter
{

	public function __construct(
		private readonly SanitizedPhpInfo $phpInfo,
	) {
		parent::__construct();
	}


	public function renderPhp(): void
	{
		$this->template->pageTitle = 'phpinfo()';
		$this->template->phpinfo = Html::el()->setHtml($this->phpInfo->getHtml());
	}

}
