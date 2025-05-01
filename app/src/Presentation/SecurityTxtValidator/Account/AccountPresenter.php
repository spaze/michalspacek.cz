<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\SecurityTxtValidator\Account;

use MichalSpacekCz\Presentation\SecurityTxtValidator\BasePresenter;
use MichalSpacekCz\SecurityTxtValidator\Account\SecurityTxtValidatorAccount;
use Nette\Application\BadRequestException;
use Override;

final class AccountPresenter extends BasePresenter
{

	public function __construct(
		private readonly SecurityTxtValidatorAccount $securityTxtValidatorAccount,
	) {
		parent::__construct();
	}


	#[Override]
	protected function startup(): void
	{
		parent::startup();
		if (!$this->securityTxtValidatorAccount->accountFeatureEnabled) {
			throw new BadRequestException('Account feature is not enabled');
		}
	}


	public function actionLogin(): void
	{
		$this->template->setParameters(new AccountTemplateParameters());
	}

}
