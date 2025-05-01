<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Forms;

use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidator;
use Nette\Forms\Form;

final readonly class ClearCacheFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private SecurityTxtValidator $validator,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 */
	public function create(callable $onSuccess, ?string $host): Form
	{
		$form = $this->factory->create();
		$form->addSubmit('clear', 'clear cache');
		$form->onSuccess[] = function () use ($onSuccess, $host): void {
			if ($host !== null) {
				$this->validator->clearCache($host);
			}
			$onSuccess();
		};
		return $form;
	}

}
