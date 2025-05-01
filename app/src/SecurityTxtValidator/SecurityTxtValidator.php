<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use DateMalformedStringException;
use DateTime;
use Exception;
use MichalSpacekCz\DateTime\DateTimeFactoryUtc;
use MichalSpacekCz\DateTime\Exceptions\CannotCreateDateTimeObjectException;
use MichalSpacekCz\Net\IpAddressType;
use MichalSpacekCz\Net\IpRanges;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorHostException;
use MichalSpacekCz\SecurityTxtValidator\Fetch\SecurityTxtValidatorFetch;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtIssueMessageFormatter;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResultTemplateParameters;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResultTemplateParametersEnricher;
use Nette\Database\Explorer;
use Nette\Http\IResponse;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Spaze\SecurityTxt\Check\Exceptions\SecurityTxtCannotParseJsonException;
use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResultFactory;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Spaze\SecurityTxt\Json\SecurityTxtJson;
use Spaze\SecurityTxt\Parser\SecurityTxtParser;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtCannotVerifySignatureException;
use Tracy\Debugger;

final readonly class SecurityTxtValidator
{

	public function __construct(
		private Explorer $database,
		private DateTimeFactoryUtc $dateTimeFactory,
		private SecurityTxtJson $securityTxtJson,
		private SecurityTxtValidatorHost $validatorHost,
		private SecurityTxtParser $securityTxtParser,
		private SecurityTxtValidatorFetch $validatorFetch,
		private SecurityTxtCheckHostResultFactory $checkHostResultFactory,
		private SecurityTxtIssueMessageFormatter $issueMessageFormatter,
		private SecurityTxtValidatorLogger $logger,
		private ValidationResultTemplateParametersEnricher $templateParametersEnricher,
		private IpRanges $ipRanges,
		private string $policyCacheTtl,
	) {
	}


	public function validate(?string $url): ValidationResultTemplateParameters
	{
		$template = new ValidationResultTemplateParameters();
		$template->cacheTtl = $this->policyCacheTtl;
		if ($url === null || trim($url) === '') {
			$template->showIntro = true;
			return $template;
		}
		try {
			$validatorUrl = $this->validatorHost->getHost($url);
		} catch (SecurityTxtValidatorHostException $e) {
			$template->errorMessage = $e->errorMessage;
			return $template;
		}
		$host = $validatorUrl->getHost();
		$template->host = $host;
		$template->displayUrl = Html::fromText($host); // Initial value, checkHost() will set the URL, if there's no error

		try {
			$this->checkHost($validatorUrl, $template);
		} catch (SecurityTxtValidatorException $e) {
			$this->logger->logException($host, $e);
			$template->errorMessage = Html::el()->setText("Can't fetch ")
				->addHtml(Html::el('code')->setText('security.txt'))
				->addText(' from ')
				->addHtml(Html::el('code')->setText($host))
				->addText(', please try again later');
		} catch (SecurityTxtNotFoundException $e) {
			$template->errorMessage = $this->issueMessageFormatter->format($e->getMessageFormat(), $e->getMessageValues());
			$template->allRedirects = $e->getAllRedirects();
			$this->addIpRangeNames($e, $template->errorMessage);
		} catch (SecurityTxtFetcherException $e) {
			$template->errorMessage = $this->issueMessageFormatter->format($e->getMessageFormat(), $e->getMessageValues());
		} catch (Exception $e) {
			Debugger::log($e, Debugger::EXCEPTION);
			$template->errorMessage = Html::el()->setText('Something went wrong while checking ')
				->addHtml(Html::el('code')->setText($host))
				->addText(', please try again later');
		}
		return $template;
	}


	/**
	 * @throws JsonException
	 * @throws SecurityTxtFetcherException
	 * @throws SecurityTxtCannotVerifySignatureException
	 * @throws SecurityTxtValidatorException
	 * @throws DateMalformedStringException
	 * @throws CannotCreateDateTimeObjectException
	 */
	private function checkHost(SecurityTxtValidatorUrl $url, ValidationResultTemplateParameters $template): void
	{
		$host = $url->getHost();
		$asciiHost = $url->getAsciiHost();
		$now = $this->dateTimeFactory->getNow();
		$result = $this->database->fetch(
			'SELECT
				last_check_time AS lastCheckTime,
				check_host_result AS checkHostResult
			FROM policy_cache
			WHERE ascii_host = ? AND last_check_time > ?',
			$asciiHost,
			$now->modify("-{$this->policyCacheTtl}"),
		);
		if ($result !== null) {
			assert(is_string($result->checkHostResult));
			assert($result->lastCheckTime instanceof DateTime);
			try {
				$decoded = Json::decode($result->checkHostResult, true);
				if (is_array($decoded)) {
					$checkHostResult = $this->securityTxtJson->createCheckHostResultFromJsonValues($decoded);
					$lastCheckTime = $this->dateTimeFactory->createFrom($result->lastCheckTime);
					$this->templateParametersEnricher->addFromCheckHostResult($template, $checkHostResult, $lastCheckTime, $now->diff($lastCheckTime));
					return;
				}
				$this->logger->log($host, "Ignoring cached policy, not an array: {$result->checkHostResult}");
			} catch (JsonException | SecurityTxtCannotParseJsonException $e) {
				$this->logger->logException($host, $e);
			}
		}

		$fetchResult = $this->validatorFetch->fetch($url, false);
		$parseResult = $this->securityTxtParser->parseFetchResult($fetchResult);
		$checkHostResult = $this->checkHostResultFactory->create($host, $parseResult);
		$this->templateParametersEnricher->addFromCheckHostResult($template, $checkHostResult, $now, null);
		$encodedResult = Json::encode($checkHostResult);
		$insertData = [
			'ascii_host' => $asciiHost,
			'last_check_time' => $now,
			'check_host_result' => $encodedResult,
		];
		$updateData = [
			'last_check_time' => $now,
			'check_host_result' => $encodedResult,
		];
		$this->database->query('INSERT INTO policy_cache', $insertData, 'ON DUPLICATE KEY UPDATE', $updateData);
	}


	/**
	 * Takes what the visitor typed, the same as validate() does, because the field accepts a URL as well as a
	 * hostname and the row has to be found by the key checkHost() writes it under.
	 */
	public function clearCache(string $url): void
	{
		try {
			$validatorUrl = $this->validatorHost->getHost($url);
		} catch (SecurityTxtValidatorHostException) {
			return;
		}
		$this->database->query('DELETE FROM policy_cache WHERE ascii_host = ?', $validatorUrl->getAsciiHost());
	}


	private function addIpRangeNames(SecurityTxtNotFoundException $e, Html $errorMessage): void
	{
		$rangeNames = [];
		foreach ($e->getIpAddresses() as $ipAddress => $typeAndCode) {
			if ($typeAndCode[1] === IResponse::S403_Forbidden) {
				$ipRange = $this->ipRanges->getRangeName($ipAddress, $typeAndCode[0] === SecurityTxtIpAddressType::V6 ? IpAddressType::V6 : IpAddressType::V4);
				if ($ipRange !== null) {
					$rangeNames[$ipAddress] = $ipRange;
				}
			}
		}
		if ($rangeNames === []) {
			return;
		}
		$errorMessage->addHtml(Html::el('br'))->addHtml(Html::el('br'));
		$ipRanges = implode(', ', array_map(fn(string $rangeName): string => "%s – {$rangeName}", $rangeNames));
		$ipRangesHtml = $this->issueMessageFormatter->format($ipRanges, array_keys($rangeNames));
		if (count($rangeNames) === 1) {
			$message = Html::el('em')
				->setText("The host's IP address is owned by a known provider (")
				->addHtml($ipRangesHtml)
				->addText(') and its firewall or configuration may block automated requests.');
		} else {
			$providerNames = array_flip($rangeNames);
			if (count($providerNames) === 1) {
				$message = Html::el('em')
					->setText("The host's IP addresses are owned by a known provider (")
					->addHtml($ipRangesHtml)
					->addText(') and its firewall or configuration may block automated requests.');
			} else {
				$message = Html::el('em')
					->setText("The host's IP addresses are owned by known providers (")
					->addHtml($ipRangesHtml)
					->addText(') and their firewall or configuration may block automated requests.');
			}
		}
		$message->addText(" If you're the host owner, consider adding an exception for both ")
			->addHtml(Html::el('code')->addText('/.well-known/security.txt'))
			->addText(' and ')
			->addHtml(Html::el('code')->addText('/security.txt'))
			->addText('.');
		$errorMessage->addHtml($message);
	}


	/**
	 * @throws SecurityTxtCannotVerifySignatureException
	 */
	public function validateDirectInput(string $input, ValidationResultTemplateParameters $templateParameters): void
	{
		$parseStringResult = $this->securityTxtParser->parseString($input);
		$this->templateParametersEnricher->addFromParseStringResult($templateParameters, $parseStringResult, $input);
	}

}
