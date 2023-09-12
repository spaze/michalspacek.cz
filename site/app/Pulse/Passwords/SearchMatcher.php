<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use MichalSpacekCz\Pulse\Site;
use MichalSpacekCz\Pulse\SpecificSite;
use Nette\Utils\Strings;

class SearchMatcher
{

	private ?string $search;


	public function __construct(
		?string $search,
		private readonly StorageRegistry $storageRegistry,
	) {
		$this->search = $search ? Strings::webalize($search) : null;
	}


	public function match(Site $site): bool
	{
		$result = $this->storageRegistry->getStorage($site->getStorageId())->getSearchResult();
		if ($this->search === null || $this->search === '') {
			return true;
		}

		$match = false;
		$company = $site->getCompany();
		if (str_contains(Strings::webalize($company->getCompanyName()), $this->search)) {
			$match = true;
			$result->addCompanyNameMatch($company);
		}
		$tradeName = $company->getTradeName();
		if ($tradeName && str_contains(Strings::webalize($tradeName), $this->search)) {
			$match = true;
			$result->addTradeNameMatch($company);
		}
		if ($site instanceof SpecificSite && str_contains($site->getUrl(), $this->search)) {
			$match = true;
			$result->addSiteUrlMatch($site);
		}
		if ($site instanceof SpecificSite && str_contains($site->getAlias(), $this->search)) {
			$match = true;
			$result->addSiteAliasMatch($site);
		}
		$i = 0;
		foreach ($site->getAlgorithms() as $algorithm) {
			if (str_contains(Strings::webalize($algorithm->getName()), $this->search)) {
				$match = true;
				$result->addAlgorithmNameMatch($algorithm);
				if ($i > 0) {
					$result->markDisclosureHistoryMatch();
				}
			}
			foreach ($algorithm->getDisclosures() as $disclosure) {
				if (str_contains(Strings::webalize($disclosure->getUrl()), $this->search)) {
					$match = true;
					$result->addDisclosureUrlMatch($disclosure);
					if ($i > 0) {
						$result->markDisclosureHistoryMatch();
					}
				}
			}
			$i++;
		}
		return $match;
	}

}
