<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorHostException;
use Nette\Utils\Html;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotParseHostnameException;
use Spaze\SecurityTxt\Parser\SecurityTxtUrlParser;

final readonly class SecurityTxtValidatorHost
{

	public function __construct(
		private SecurityTxtUrlParser $securityTxtUrlParser,
	) {
	}


	/**
	 * @throws SecurityTxtValidatorHostException
	 */
	public function getHost(string $url): SecurityTxtValidatorUrl
	{
		try {
			$parsedUrl = $this->securityTxtUrlParser->getUrl($url);
			$validatorUrl = new SecurityTxtValidatorUrl($this->securityTxtUrlParser->getBaseUrl($parsedUrl));
		} catch (SecurityTxtCannotParseHostnameException) {
			throw new SecurityTxtValidatorHostException(Html::fromText('Invalid URL or hostname'));
		}
		// The fetcher takes these two and refuses everything else, but only once the request has been made, which for the
		// Lambda means an invocation spent to be told no. Refused here instead, so a scheme nothing can be fetched over
		// never reaches a fetch, a cache key or a column sized for the schemes that can.
		if (!in_array($validatorUrl->getScheme(), ['https', 'http'], true)) {
			throw new SecurityTxtValidatorHostException(Html::el()
				->setText('Only ')
				->addHtml(Html::el('code')->setText('https'))
				->addText(' and ')
				->addHtml(Html::el('code')->setText('http'))
				->addText(' can be checked'));
		}
		$host = $validatorUrl->getHost();
		if ($host === 'localhost' || str_starts_with($host, '127.') || $host === '[::1]') {
			throw new SecurityTxtValidatorHostException(Html::el()
				->setText("There's no ")
				->addHtml(Html::el('code')->setText('security.txt'))
				->addText(' on your machine ')
				->addHtml(Html::el('code')->addText('(◔_◔)')));
		}
		if (strlen($validatorUrl->getAsciiHost()) > 253) {
			throw new SecurityTxtValidatorHostException(Html::fromText('The hostname is too long, way too long ')
				->addHtml(Html::el('code')->addText('/┆\\')));
		}
		return $validatorUrl;
	}

}
