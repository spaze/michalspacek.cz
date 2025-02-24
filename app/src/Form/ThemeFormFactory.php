<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Application\Theme\Theme;
use MichalSpacekCz\Application\Theme\ThemeMode;

final readonly class ThemeFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private Theme $theme,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 */
	public function create(callable $onSuccess): UiForm
	{
		$form = $this->factory->create();
		$form->addSubmit(ThemeMode::Light->value)->onClick[] = function () use ($onSuccess): void {
			$this->theme->setLightMode();
			$onSuccess();
		};
		$form->addSubmit(ThemeMode::Dark->value)->onClick[] = function () use ($onSuccess): void {
			$this->theme->setDarkMode();
			$onSuccess();
		};
		return $form;
	}

}
