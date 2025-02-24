<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Locale;

use MichalSpacekCz\Database\TypedDatabase;

final class Locales
{

	/** @var array<int, string>|null */
	private ?array $locales = null;


	public function __construct(
		private readonly TypedDatabase $database,
	) {
	}


	/**
	 * @return array<int, string> of id => locale
	 */
	public function getAllLocales(): array
	{
		if ($this->locales === null) {
			$this->locales = $this->database->fetchPairsIntString('SELECT id_locale, locale FROM locales ORDER BY id_locale');
		}
		return $this->locales;
	}


	public function getLocaleById(int $id): ?string
	{
		return $this->getAllLocales()[$id] ?? null;
	}

}
