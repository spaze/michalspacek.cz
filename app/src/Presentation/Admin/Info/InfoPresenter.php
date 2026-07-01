<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Info;

use MichalSpacekCz\Application\SanitizedPhpInfo;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use MichalSpacekCz\User\SecurityActivity\SecurityEventLogger;
use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use Nette\Utils\Html;

final class InfoPresenter extends BasePresenter
{

	public function __construct(
		private readonly SanitizedPhpInfo $phpInfo,
		private readonly SecurityEventLogger $securityEventLogger,
	) {
		parent::__construct();
	}


	public function actionPhp(): void
	{
		$this->requireReauthentication();
		$this->securityEventLogger->record((int)$this->getUser()->getId(), SecurityEventType::PageViewed, ['page' => $this->link('this')]);
		$this->template->pageTitle = 'phpinfo()';
		$this->template->phpinfo = Html::el()->setHtml($this->phpInfo->getHtml());
	}

}
