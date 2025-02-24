<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use MichalSpacekCz\Pulse\Passwords\Storage\StorageRegistry;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageSite;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageSpecificSite;
use Nette\Utils\Strings;

final readonly class SearchMatcher
{

	private ?string $search;


	public function __construct(
		?string $search,
		private StorageRegistry $storageRegistry,
	) {
		$this->search = $search !== null ? Strings::webalize($search) : null;
	}


	public function match(StorageSite $site): bool
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
		if ($tradeName !== null && str_contains(Strings::webalize($tradeName), $this->search)) {
			$match = true;
			$result->addTradeNameMatch($company);
		}
		if ($site instanceof StorageSpecificSite && str_contains($site->getUrl(), $this->search)) {
			$match = true;
			$result->addSiteUrlMatch($site);
		}
		if ($site instanceof StorageSpecificSite && str_contains($site->getAlias(), $this->search)) {
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
