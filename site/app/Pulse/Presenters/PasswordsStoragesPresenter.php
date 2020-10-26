<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Presenters;

use MichalSpacekCz\Www\Presenters\BasePresenter;
use MichalSpacekCz\Pulse\Passwords;
use MichalSpacekCz\Pulse\Passwords\Rating;
use Nette\Application\BadRequestException;

class PasswordsStoragesPresenter extends BasePresenter
{

	/** @var Passwords */
	protected $passwords;

	/** @var Rating */
	protected $passwordsRating;


	public function __construct(Passwords $passwords, Rating $passwordsRating)
	{
		$this->passwords = $passwords;
		$this->passwordsRating = $passwordsRating;
		parent::__construct();
	}


	/**
	 * Storages action handler.
	 *
	 * @param string|null $param
	 */
	public function actionDefault(?string $param): void
	{
		// Keep old, published URLs alive
		if ($param) {
			$this->redirectPermanent('site', $param);
		}
		$data = $this->passwords->getAllStorages();
		$this->template->isDetail = false;
		$this->template->pageTitle = 'Password storage disclosures';
		$this->template->data = $data;
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
	}


	/**
	 * Storages by site action handler.
	 *
	 * @param string $param
	 */
	public function actionSite(string $param): void
	{
		if (empty($param)) {
			$this->redirectPermanent('default');
		}

		$sites = explode(',', $param);
		$data = $this->passwords->getStoragesBySite($sites);
		if (empty($data->sites)) {
			throw new BadRequestException('Unknown site alias');
		}

		$this->template->isDetail = true;
		$this->template->pageTitle = implode(', ', $sites) . ' password storage disclosures';
		$this->template->data = $data;
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
		$this->setView('default');
	}


	/**
	 * Storages by company action handler.
	 *
	 * @param string $param
	 */
	public function actionCompany(string $param): void
	{
		if (empty($param)) {
			$this->redirectPermanent('default');
		}

		$companies = explode(',', $param);
		$data = $this->passwords->getStoragesByCompany($companies);
		if (empty($data->sites)) {
			throw new BadRequestException('Unknown company alias');
		}

		$names = [];
		foreach ($data->companies as $item) {
			$names[] = ($item->tradeName ?: $item->companyName);
		}

		$this->template->isDetail = true;
		$this->template->pageTitle = implode(', ', $names) . ' password storage disclosures';
		$this->template->data = $data;
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
		$this->setView('default');
	}


	/**
	 * Storages rating action handler.
	 */
	public function actionRating(): void
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
	public function actionQuestions(): void
	{
		$this->template->pageTitle = 'Password storage disclosures questions';
	}

}
