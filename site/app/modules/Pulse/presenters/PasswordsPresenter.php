<?php
namespace App\PulseModule\Presenters;

/**
 * Pulse presenter.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class PasswordsPresenter extends \App\Presenters\BasePresenter
{

	/** @var \MichalSpacekCz\Pulse\Passwords */
	protected $passwords;

	/** @var \MichalSpacekCz\Pulse\Passwords\Rating */
	protected $passwordsRating;


	public function __construct(\MichalSpacekCz\Pulse\Passwords $passwords, \MichalSpacekCz\Pulse\Passwords\Rating $passwordsRating)
	{
		$this->passwords = $passwords;
		$this->passwordsRating = $passwordsRating;
	}


	/**
	 * Storages action handler.
	 */
	public function actionStorages()
	{
		$data = $this->passwords->getAllStorages();
		$this->template->isDetail = false;
		$this->template->pageTitle = 'Password storage disclosures';
		$this->template->data = $data;
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
	}


	/**
	 * Storages by site action handler.
	 *
	 * @param string $site
	 */
	public function actionStoragesBySite($site)
	{
		$sites = explode(',', $site);
		$data = $this->passwords->getStoragesBySite($sites);
		if (empty($data->sites)) {
			throw new \Nette\Application\BadRequestException('Unknown site alias', \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->isDetail = true;
		$this->template->pageTitle = implode(', ', $sites) . ' password storage disclosures';
		$this->template->data = $data;
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
		$this->setView('storages');
	}


	/**
	 * Storages rating action handler.
	 */
	public function actionStoragesRating()
	{
		$this->template->pageTitle = 'Password storage disclosures rating guide';
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
		$this->template->slowHashes = $this->passwords->getSlowHashes();
		$this->template->visibleDisclosures = $this->passwords->getVisibleDisclosures();
		$this->template->invisibleDisclosures = $this->passwords->getInvisibleDisclosures();
	}


	/**
	 * Storages questions action handler.
	 */
	public function actionStoragesQuestions()
	{
		$this->template->pageTitle = 'Password storage disclosures questions';
	}


	/**
	 * Default action handler.
	 */
	public function actionDefault()
	{
		$this->template->pageTitle = 'Passwords';
	}

}
