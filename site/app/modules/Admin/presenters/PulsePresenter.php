<?php
namespace App\AdminModule\Presenters;

use \MichalSpacekCz\Pulse\Sites;

/**
 * Pulse presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class PulsePresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Pulse\Companies */
	protected $companies;

	/** @var \MichalSpacekCz\Pulse\Sites */
	protected $sites;

	/** @var \MichalSpacekCz\Pulse\Passwords */
	protected $passwords;


	/**
	 * @param \MichalSpacekCz\Pulse\Companies $companies
	 * @param \MichalSpacekCz\Pulse\Sites $sites
	 * @param \MichalSpacekCz\Pulse\Passwords $passwords
	 */
	public function __construct(
		\MichalSpacekCz\Pulse\Companies $companies,
		Sites $sites,
		\MichalSpacekCz\Pulse\Passwords $passwords
	)
	{
		$this->companies = $companies;
		$this->sites = $sites;
		$this->passwords = $passwords;
	}


	public function actionPasswordsStorages()
	{
		$this->template->pageTitle = 'Password storages';
		$this->template->newDisclosures = 3;
	}


	protected function createComponentPasswordsStorages($formName)
	{
		$form = new \MichalSpacekCz\Form\Pulse\PasswordsStorages(
			$this,
			$formName,
			$this->template->newDisclosures,
			$this->companies,
			$this->sites,
			$this->passwords
		);
		$form->onValidate[] = $this->validatePasswordsStorages;
		$form->onSuccess[] = $this->submittedPasswordsStorages;
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
	 * @param \MichalSpacekCz\Form\Pulse\PasswordsStorages $form
	 * @return null
	 */
	public function validatePasswordsStorages(\MichalSpacekCz\Form\Pulse\PasswordsStorages $form)
	{
		$values = $form->getValues();
		if (empty($values->company->new->name)) {
			$storages = $this->passwords->getStoragesByCompanyId($values->company->id);
			if ($values->site->id === Sites::ALL && !empty($storages->sites)) {
				$form->addError('Invalid combination, can\'t add dislosure for all sites when sites already exist');
			}
			if ($values->site->id !== Sites::ALL && !isset($storages->sites[$values->site->id])) {
				$form->addError('Invalid combination, the site is already assigned to different company');
			}
			if (empty($values->algo->from) && isset($storages->storages[$values->company->id][$values->site->id])) {
				foreach ($storages->storages[$values->company->id][$values->site->id] as $storage) {
					if (empty($storage->from)) {
						$form->addError('Can\'t add another algorithm without from');
						break;
					}
				}
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


	public function submittedPasswordsStorages(\MichalSpacekCz\Form\Pulse\PasswordsStorages $form)
	{
		$values = $form->getValues();
		if ($this->passwords->addStorage($values)) {
			$this->flashMessage('Password storage added successfully');
		}
		$this->redirect('this');
	}

}
