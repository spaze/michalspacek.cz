<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\Placeholders;

use Contributte\Translation\Translator;
use MichalSpacekCz\Http\FetchMetadata\FetchMetadata;
use MichalSpacekCz\Http\FetchMetadata\FetchMetadataHeader;
use Nette\Utils\Html;
use Override;

/**
 * Inserts live `Sec-Fetch-*` (fetch metadata) headers into blog posts for example.
 *
 * All headers:
 * /---
 * **FETCH_METADATA:all**
 * \---
 * Single header:
 * /---
 * **FETCH_METADATA:Sec-Fetch-Dest**
 * \---
 * Inline values:
 * ''**FETCH_METADATA:Sec-Fetch-Dest**''
 */
readonly class FetchMetadataTexyFormatterPlaceholder implements TexyFormatterPlaceholder
{

	public function __construct(
		private FetchMetadata $fetchMetadata,
		private Translator $translator,
	) {
	}


	#[Override]
	public static function getId(): string
	{
		return 'FETCH_METADATA';
	}


	#[Override]
	public function replace(string $value): string
	{
		if ($value === 'all') {
			$headers = $this->fetchMetadata->getAllHeaders();
		} else {
			$header = FetchMetadataHeader::from($value);
			$headers = [$header->value => $this->fetchMetadata->getHeader($header)];
		}
		$result = [];
		foreach ($headers as $header => $value) {
			if ($value === null) {
				$value = Html::el('em')
					->addText('[')
					->addText($this->translator->translate('messages.httpHeaders.headerNotSent'))
					->addText(']');
			}
			$result[] = "{$header}: {$value}";
		}
		return implode("\n", $result);
	}

}