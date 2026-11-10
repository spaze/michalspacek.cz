<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\PasswordsStorages;

use MichalSpacekCz\Form\Pulse\PasswordsStoragesSearchSortFormFactory;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Pulse\Passwords\Algorithms\PasswordHashingAlgorithms;
use MichalSpacekCz\Pulse\Passwords\Disclosures\PasswordHashingDisclosures;
use MichalSpacekCz\Pulse\Passwords\Passwords;
use MichalSpacekCz\Pulse\Passwords\PasswordsSorting;
use MichalSpacekCz\Pulse\Passwords\Rating;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageCompanySitemapEntries;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageRegistry;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageSiteSitemapEntries;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageSpecificSite;
use MichalSpacekCz\Sitemap\InSitemap;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Forms\Form;

final class PasswordsStoragesPresenter extends BasePresenter
{

	private ?string $rating = null;
	private ?string $sort = null;
	private ?string $search = null;


	public function __construct(
		private readonly Passwords $passwords,
		private readonly Rating $passwordsRating,
		private readonly PasswordsStoragesSearchSortFormFactory $searchSortFactory,
		private readonly PasswordsSorting $passwordsSorting,
		private readonly PasswordHashingAlgorithms $passwordHashingAlgorithms,
		private readonly PasswordHashingDisclosures $passwordHashingDisclosures,
	) {
		parent::__construct();
	}


	/**
	 * @throws InvalidLinkException
	 */
	#[InSitemap]
	public function actionDefault(?string $param, ?string $rating, ?string $sort, ?string $search): void
	{
		// Keep old, published URLs alive
		if ($param !== null) {
			$this->redirectPermanent('site', $param);
		}

		$this->sort = $sort;
		$this->search = $search;
		$this->rating = $rating === null || $rating === 'all' || !array_key_exists($rating, $this->passwordsRating->getRatings()) ? null : strtoupper($rating);

		$this->setDefaultViewAndVars(
			'Password storage disclosures',
			false,
			$this->rating !== null || $this->sort !== null || $this->search !== null,
			$this->link('//' . $this->getAction()), // Not using 'this' as the destination to omit params
			$this->passwords->getAllStorages($this->rating, $this->sort === null ? $this->passwordsSorting->getDefaultSort() : $this->sort, $this->search),
		);
	}


	/**
	 * Storages by site action handler.
	 *
	 * @param string $param
	 */
	#[InSitemap(StorageSiteSitemapEntries::class)]
	public function actionSite(string $param): void
	{
		if ($param === '') {
			$this->redirectPermanent('default');
		}

		$sites = explode(',', $param);
		$data = $this->passwords->getStoragesBySite($sites);
		$found = [];
		foreach ($data->getSites() as $site) {
			if ($site instanceof StorageSpecificSite) {
				$found[] = $site->getAlias();
			}
		}
		if (!$this->isEveryAliasFound($sites, $found)) {
			throw new BadRequestException('Unknown site alias');
		}

		$this->setDefaultViewAndVars(implode(', ', $sites) . ' password storage disclosures', true, false, null, $data);
	}


	/**
	 * Storages by company action handler.
	 *
	 * @param string $param
	 */
	#[InSitemap(StorageCompanySitemapEntries::class)]
	public function actionCompany(string $param): void
	{
		if ($param === '') {
			$this->redirectPermanent('default');
		}

		$companies = explode(',', $param);
		$data = $this->passwords->getStoragesByCompany($companies);
		$found = [];
		foreach ($data->getCompanies() as $company) {
			$found[] = $company->getCompanyAlias();
		}
		if (!$this->isEveryAliasFound($companies, $found)) {
			throw new BadRequestException('Unknown company alias');
		}

		$names = [];
		foreach ($data->getCompanies() as $item) {
			$names[] = $item->getDisplayName();
		}

		$this->setDefaultViewAndVars(implode(', ', $names) . ' password storage disclosures', true, false, null, $data);
	}


	/**
	 * Each alias in the URL has to name a different site or company, as stored, so a list with an unknown alias, one
	 * in another case, or one given twice is a 404, not the page of the aliases found.
	 *
	 * @param list<string> $aliases
	 * @param list<string> $found
	 */
	private function isEveryAliasFound(array $aliases, array $found): bool
	{
		sort($aliases, SORT_STRING);
		sort($found, SORT_STRING);
		return $aliases === $found;
	}


	private function setDefaultViewAndVars(string $pageTitle, bool $isDetail, bool $openSearchSort, ?string $canonicalLink, StorageRegistry $data): void
	{
		$templateParameters = new PasswordsStoragesDefaultTemplateParameters(
			$isDetail,
			$pageTitle,
			$data,
			$this->passwordsRating->getRatingGuide(),
			$openSearchSort,
			$canonicalLink,
		);
		$this->setView('default');
		$this->template->setParameters($templateParameters);
	}


	/**
	 * Storages rating action handler.
	 */
	#[InSitemap]
	public function actionRating(): void
	{
		$this->template->pageTitle = 'Password storage disclosures rating guide';
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
		$this->template->slowHashes = $this->passwordHashingAlgorithms->getSlowHashes();
		$this->template->visibleDisclosures = $this->passwordHashingDisclosures->getVisibleDisclosures();
		$this->template->invisibleDisclosures = $this->passwordHashingDisclosures->getInvisibleDisclosures();
	}


	/**
	 * Storages questions action handler.
	 */
	#[InSitemap]
	public function actionQuestions(): void
	{
		$this->template->pageTitle = 'Password storage disclosures questions';
	}


	protected function createComponentSearchSort(): Form
	{
		return $this->searchSortFactory->create($this->rating, $this->sort, $this->search);
	}

}
