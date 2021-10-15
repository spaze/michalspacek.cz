<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use Contributte\Translation\Translator;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Http\IResponse;

/**
 * A forbidden presenter.
 *
 * Does not extend BasePresenter to avoid loop in startup().
 *
 * @property-read Template $template
 */
class ForbiddenPresenter extends Presenter
{

	private Translator $translator;

	private IResponse $httpResponse;


	public function __construct(Translator $translator, IResponse $httpResponse)
	{
		$this->translator = $translator;
		$this->httpResponse = $httpResponse;
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->httpResponse->setCode(IResponse::S403_FORBIDDEN);
		$this->template->pageTitle = $this->translator->translate("messages.title.forbidden");
	}

}
