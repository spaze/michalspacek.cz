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
			$validatorUrl = new SecurityTxtValidatorUrl($this->securityTxtUrlParser->getUrl($url));
		} catch (SecurityTxtCannotParseHostnameException) {
			throw new SecurityTxtValidatorHostException(Html::fromText('Invalid URL or hostname'));
		}
		$host = $validatorUrl->getHost();
		if ($host === 'localhost' || str_starts_with($host, '127.') || $host === '[::1]') {
			throw new SecurityTxtValidatorHostException(Html::el()
				->setText("There's no ")
				->addHtml(Html::el('code')->setText('security.txt'))
				->addText(' on your machine ')
				->addHtml(Html::el('code')->addText('(◔_◔)')));
		}
		if (strlen($validatorUrl->getAsciiHost()) > 255) {
			throw new SecurityTxtValidatorHostException(Html::fromText('The hostname is too long, way too long ')
				->addHtml(Html::el('code')->addText('/┆\\')));
		}
		return $validatorUrl;
	}

}
