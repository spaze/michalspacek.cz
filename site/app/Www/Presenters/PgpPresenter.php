<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

class PgpPresenter extends BasePresenter
{

	/**
	 * Physical location root directory, no trailing slash.
	 */
	private string $locationRoot;


	public function renderDefault(): void
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.encryptedmessages');
		$this->template->keyFile = $keyFile = 'key.asc';
		$this->template->key = file_get_contents("{$this->locationRoot}/{$keyFile}");
	}


	public function setLocationRoot(string $locationRoot): void
	{
		$this->locationRoot = $locationRoot;
	}

}
