<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Forms;

use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidator;
use Nette\Application\UI\Form;

final readonly class ClearCacheFormFactory
{

	public function __construct(
		private SecurityTxtValidator $validator,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 */
	public function create(callable $onSuccess, ?string $host): Form
	{
		$form = new Form();
		$form->addSubmit('clear', 'check again');
		$form->onSuccess[] = function () use ($onSuccess, $host): void {
			if ($host !== null) {
				$this->validator->clearCache($host);
			}
			$onSuccess();
		};
		return $form;
	}

}
