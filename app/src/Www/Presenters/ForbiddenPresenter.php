<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use Contributte\Translation\Translator;
use MichalSpacekCz\Templating\DefaultTemplate;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Http\IResponse;
use Override;

/**
 * @property-read DefaultTemplate $template
 */
final class ForbiddenPresenter extends BasePresenter
{

	public function __construct(
		private readonly Translator $translator,
		private readonly IResponse $httpResponse,
	) {
		parent::__construct();
	}


	#[Override]
	protected function startup(): void
	{
		parent::startup();
		if ($this->getRequest()?->getMethod() !== Request::FORWARD) {
			throw new BadRequestException("Direct access to '{$this->getName()}' is forbidden");
		}
	}


	public function actionDefault(string $message): void
	{
		$this->httpResponse->setCode(IResponse::S403_Forbidden);
		$this->template->pageTitle = $this->translator->translate('messages.title.forbidden');
		$this->template->message = $this->translator->translate($message);
	}

}
