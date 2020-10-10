<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Http\IResponse;
use Nette\Localization\ITranslator;

/**
 * A forbidden presenter.
 *
 * Does not extend BasePresenter to avoid loop in startup().
 *
 * @property-read Template $template
 */
class ForbiddenPresenter extends Presenter
{

	/** @var ITranslator */
	protected $translator;

	/** @var IResponse */
	protected $httpResponse;


	public function __construct(ITranslator $translator, IResponse $httpResponse)
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
