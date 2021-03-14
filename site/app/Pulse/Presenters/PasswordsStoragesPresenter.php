<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Presenters;

use MichalSpacekCz\Form\Pulse\PasswordsStoragesSearchSortFactory;
use MichalSpacekCz\Pulse\Passwords;
use MichalSpacekCz\Pulse\Passwords\PasswordsSorting;
use MichalSpacekCz\Pulse\Passwords\Rating;
use MichalSpacekCz\Www\Presenters\BasePresenter;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;

class PasswordsStoragesPresenter extends BasePresenter
{

	private Passwords $passwords;

	private Rating $passwordsRating;

	private PasswordsStoragesSearchSortFactory $searchSortFactory;

	private PasswordsSorting $passwordsSorting;

	private Form $searchSortForm;


	public function __construct(
		Passwords $passwords,
		Rating $passwordsRating,
		PasswordsStoragesSearchSortFactory $searchSortFactory,
		PasswordsSorting $passwordsSorting
	) {
		$this->passwords = $passwords;
		$this->passwordsRating = $passwordsRating;
		$this->searchSortFactory = $searchSortFactory;
		$this->passwordsSorting = $passwordsSorting;
		parent::__construct();
	}


	public function actionDefault(?string $param, ?string $rating, ?string $sort, ?string $search): void
	{
		// Keep old, published URLs alive
		if ($param) {
			$this->redirectPermanent('site', $param);
		}

		$this->searchSortForm = $this->searchSortFactory->create($rating, $sort, $search);
		$rating = $rating === null || $rating === 'all' || !array_key_exists($rating, $this->passwordsRating->getRatings()) ? null : strtoupper($rating);
		$data = $this->passwords->getAllStorages($rating, $sort === null ? $this->passwordsSorting->getDefaultSort() : $sort, $search);
		$this->template->isDetail = false;
		$this->template->pageTitle = 'Password storage disclosures';
		$this->template->data = $data;
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
		$this->template->openSearchSort = $rating !== null || $sort !== null || $search !== null;
		$this->template->canonicalLink = $this->link("//{$this->action}");  // Not using 'this' as the destination to omit params
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
		if (count($data->getSites()) === 0) {
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
		if (count($data->getSites()) === 0) {
			throw new BadRequestException('Unknown company alias');
		}

		$names = [];
		foreach ($data->getCompanies() as $item) {
			$names[] = $item->getDisplayName();
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


	protected function createComponentSearchSort(): Form
	{
		return $this->searchSortForm;
	}

}
