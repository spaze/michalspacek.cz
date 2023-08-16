<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Presenters;

use MichalSpacekCz\Form\Pulse\PasswordsStoragesSearchSortFormFactory;
use MichalSpacekCz\Pulse\Passwords;
use MichalSpacekCz\Pulse\Passwords\PasswordsSorting;
use MichalSpacekCz\Pulse\Passwords\Rating;
use MichalSpacekCz\Pulse\Passwords\StorageRegistry;
use MichalSpacekCz\Www\Presenters\BasePresenter;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Application\UI\InvalidLinkException;

class PasswordsStoragesPresenter extends BasePresenter
{

	private ?string $rating;
	private ?string $sort;
	private ?string $search;


	public function __construct(
		private readonly Passwords $passwords,
		private readonly Rating $passwordsRating,
		private readonly PasswordsStoragesSearchSortFormFactory $searchSortFactory,
		private readonly PasswordsSorting $passwordsSorting,
	) {
		parent::__construct();
	}


	/**
	 * @throws InvalidLinkException
	 */
	public function actionDefault(?string $param, ?string $rating, ?string $sort, ?string $search): void
	{
		$this->template->grade = Passwords\RatingGrade::B;
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

		$this->setDefaultViewAndVars(implode(', ', $sites) . ' password storage disclosures', true, false, null, $data);
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

		$this->setDefaultViewAndVars(implode(', ', $names) . ' password storage disclosures', true, false, null, $data);
	}


	private function setDefaultViewAndVars(string $pageTitle, bool $isDetail, bool $openSearchSort, ?string $canonicalLink, StorageRegistry $data): void
	{
		$this->template->pageTitle = $pageTitle;
		$this->template->isDetail = $isDetail;
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
		$this->template->openSearchSort = $openSearchSort;
		$this->template->canonicalLink = $canonicalLink;
		$this->template->data = $data;
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
		return $this->searchSortFactory->create($this->rating, $this->sort, $this->search);
	}

}
