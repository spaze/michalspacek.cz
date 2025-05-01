<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\SecurityTxtValidator\Homepage;

use MichalSpacekCz\Presentation\SecurityTxtValidator\BasePresenter;
use MichalSpacekCz\SecurityTxtValidator\Forms\ClearCacheFormFactory;
use MichalSpacekCz\SecurityTxtValidator\Forms\ValidateHostFormFactory;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidator;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResult;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResultFactory;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResultTemplateParameters;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Forms\Form;

final class HomepagePresenter extends BasePresenter
{

	private ?string $originalHost = null;

	private ?ValidationResultTemplateParameters $templateParameters = null;


	public function __construct(
		private readonly ValidateHostFormFactory $validateHostFormFactory,
		private readonly ClearCacheFormFactory $clearCacheFormFactory,
		private readonly SecurityTxtValidator $securityTxtValidator,
		private readonly ValidationResultFactory $validationResultFactory,
	) {
		parent::__construct();
	}


	public function actionDefault(?string $h = null): void
	{
//$url = 'https://loopsofzen.uk'; // IPv6-only
//$url = 'https://404media.co'; // redirects
//		$url = 'https://www.michalspacek.cz/';
//$url = 'https://www.kb.cz/'; // Errors and warnings
//$url = 'https://www.swisscom.ch/'; // Warnings
// $url = 'https://www.nic.cz/.well-known/security.txt'; // 404 in / but present in .well-known, but exception thrown
// $url = 'https://www.litacka.cz'; // HTML and redirects
		$this->originalHost = $h;
		$this->templateParameters = $this->securityTxtValidator->validate($this->originalHost);
		$this->template->setParameters($this->templateParameters);
	}


	/**
	 * @throws InvalidLinkException
	 */
	protected function createComponentValidateHost(): Form
	{
		return $this->validateHostFormFactory->create(
			function (string $host): never {
				$this->redirect('this', $host);
			},
			$this->originalHost,
			$this->link('default'), // Remove params from the form action to stop the host in the param being validated when the form is submitted
		);
	}


	protected function createComponentClearCache(): Form
	{
		return $this->clearCacheFormFactory->create(
			function (): void {
				$this->redirect('this');
			},
			$this->originalHost,
		);
	}


	protected function createComponentValidationResult(): ValidationResult
	{
		if ($this->templateParameters === null) {
			throw new ShouldNotHappenException("templateParameters should be set by the form's onSuccess handler");
		}
		return $this->validationResultFactory->create($this->templateParameters);
	}

}
