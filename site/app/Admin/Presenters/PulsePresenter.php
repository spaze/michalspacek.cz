<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\Pulse\PasswordsStorages;
use MichalSpacekCz\Pulse\Companies;
use MichalSpacekCz\Pulse\Passwords;
use MichalSpacekCz\Pulse\Sites;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;

class PulsePresenter extends BasePresenter
{

	public function __construct(
		private readonly Companies $companies,
		private readonly Sites $sites,
		private readonly Passwords $passwords,
	) {
		parent::__construct();
	}


	public function actionPasswordsStorages(): void
	{
		$this->template->pageTitle = 'Password storages';
		$this->template->newDisclosures = 3;
	}


	protected function createComponentPasswordsStorages(string $formName): PasswordsStorages
	{
		$form = new PasswordsStorages(
			$this,
			$formName,
			$this->template->newDisclosures,
			$this->companies,
			$this->sites,
			$this->passwords,
		);
		$form->onValidate[] = [$this, 'validatePasswordsStorages'];
		$form->onSuccess[] = [$this, 'submittedPasswordsStorages'];
		return $form;
	}


	/**
	 * Validate submitted data.
	 *
	 * The rules for validation are:
	 * - new company, new site => ok
	 * - new company, all sites => ok
	 * - new company, existing sites => ok
	 * - existing company, new site => ok
	 * - existing company, all sites when sites exist => nope
	 * - existing company, another algo without "from" when there's one already
	 * - existing company, existing site => check if the combination already exists
	 *
	 * @param PasswordsStorages $form
	 * @param ArrayHash<int|string> $values
	 */
	public function validatePasswordsStorages(PasswordsStorages $form, ArrayHash $values): void
	{
		if (empty($values->company->new->name)) {
			$storages = $this->passwords->getStoragesByCompanyId($values->company->id);
			$specificSites = array_filter($storages->getSites(), function ($site) {
				return !$site->isTypeAll();
			});
			if ($values->site->id === Sites::ALL && !empty($specificSites)) {
				$form->addError('Invalid combination, can\'t add disclosure for all sites when sites already exist');
			}
			if ($values->site->id !== null && $values->site->id !== Sites::ALL && !$storages->hasSite((string)$values->site->id)) {
				$form->addError('Invalid combination, the site is already assigned to different company');
			}
		} elseif ($this->companies->getByName($values->company->new->name)) {
			$form->addError('Can\'t add new company, duplicated name');
		}
		if (!empty($values->site->new->url) && $this->sites->getByUrl($values->site->new->url)) {
			$form->addError('Can\'t add new site, duplicated URL');
		}
		if (!empty($values->algo->new->algo) && $this->passwords->getAlgorithmByName($values->algo->new->algo)) {
			$form->addError('Can\'t add new algorithm, duplicated name');
		}
	}


	/**
	 * @param Form $form
	 * @param ArrayHash<int|string> $values
	 */
	public function submittedPasswordsStorages(Form $form, ArrayHash $values): void
	{
		if ($this->passwords->addStorage($values)) {
			$this->flashMessage('Password storage added successfully');
		}
		$this->redirect('this');
	}

}
