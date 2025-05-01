<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\SecurityTxtValidator;

use MichalSpacekCz\Presentation\Www\BasePresenter as WwwBasePresenter;
use MichalSpacekCz\SecurityTxtValidator\Account\SecurityTxtValidatorAccount;
use MichalSpacekCz\Templating\DefaultTemplate;
use Override;

/**
 * @property-read DefaultTemplate $template
 */
abstract class BasePresenter extends WwwBasePresenter
{

	private SecurityTxtValidatorAccount $securityTxtValidatorAccount;


	/**
	 * @internal
	 */
	public function injectSecurityTxtValidatorAccount(SecurityTxtValidatorAccount $securityTxtValidatorAccount): void
	{
		$this->securityTxtValidatorAccount = $securityTxtValidatorAccount;
	}


	#[Override]
	public function beforeRender(): void
	{
		$this->template->accountFeatureEnabled = $this->securityTxtValidatorAccount->accountFeatureEnabled;
	}

}
