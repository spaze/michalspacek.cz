<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Info;

use MichalSpacekCz\Application\SanitizedPhpInfo;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use Nette\Utils\Html;

final class InfoPresenter extends BasePresenter
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
