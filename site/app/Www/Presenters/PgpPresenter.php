<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

class PgpPresenter extends BasePresenter
{

	public function __construct(
		private readonly string $locationRoot,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle  = $this->translator->translate('messages.title.encryptedmessages');
		$this->template->keyFile = $keyFile = 'key.asc';
		$this->template->key = file_get_contents("{$this->locationRoot}/{$keyFile}");
	}

}
