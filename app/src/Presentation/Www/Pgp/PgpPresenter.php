<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Pgp;

use Contributte\Translation\Translator;
use MichalSpacekCz\Pgp\Keyserver;
use MichalSpacekCz\Presentation\Www\BasePresenter;

final class PgpPresenter extends BasePresenter
{

	public function __construct(
		private readonly Translator $translator,
		private readonly Keyserver $keyserver,
		private readonly string $locationRoot,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.encryptedmessages');
		$this->template->keyFile = $keyFile = 'key.asc';
		$this->template->key = file_get_contents("{$this->locationRoot}/{$keyFile}");
		$this->template->keyId = $keyId = 'B64BDD6E464AB529';
		$this->template->keyUrl = $this->keyserver->getLookupUrl($keyId);
	}

}
