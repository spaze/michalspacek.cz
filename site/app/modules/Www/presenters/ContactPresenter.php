<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

class ContactPresenter extends BasePresenter
{

	/**
	 * Physical location root directory, no trailing slash.
	 *
	 * @var string
	 */
	private $locationRoot;


	public function renderDefault(): void
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.contact');
		$this->template->keyFile = $keyFile = 'key.asc';
		$this->template->key = file_get_contents("{$this->locationRoot}/{$keyFile}");
	}


	public function setLocationRoot(string $locationRoot): void
	{
		$this->locationRoot = $locationRoot;
	}

}
