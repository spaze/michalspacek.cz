<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use DateInterval;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\DateTime\DateTimeFactoryUtc;
use MichalSpacekCz\DateTime\Exceptions\CannotCreateDateTimeObjectException;
use MichalSpacekCz\Net\IpAddressType;
use MichalSpacekCz\Net\IpRanges;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorFetchFailedException;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorHostException;
use MichalSpacekCz\SecurityTxtValidator\Fetch\SecurityTxtValidatorFetch;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtIssueMessageFormatter;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResultTemplateParameters;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResultTemplateParametersEnricher;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Http\IResponse;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Spaze\SecurityTxt\Check\Exceptions\SecurityTxtCannotParseJsonException;
use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResultFactory;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlExtensionNotLoadedException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlUserAgentInvalidException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtOnlyIpv6HostButIpv6DisabledException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Spaze\SecurityTxt\Json\SecurityTxtJson;
use Spaze\SecurityTxt\Parser\SecurityTxtParser;
use Throwable;
use Tracy\Debugger;

final readonly class SecurityTxtValidator
{

	/**
	 * Failures that describe this application rather than the host it was asked about: our runtime with no curl
	 * extension, our own user agent being unusable, and a host reachable only over IPv6 while our settings have IPv6
	 * turned off, which would go on being the response after that setting changed.
	 *
	 * Stored, any of them becomes the host's response: everyone asking about that domain is told it is broken for as
	 * long as it is kept, nobody can clear it for the first 30 seconds, and its owner has no way to tell the fault is
	 * ours. A page that reports on other people's domains must not publish a claim about one that is really about us.
	 *
	 * Everything else the fetcher throws describes the host and is worth keeping. `SecurityTxtValidatorStoredFailureAllowListTest`
	 * fails when the library grows a failure neither it nor this list has heard of, so a new one is decided rather than
	 * inherited, and phpstan reports any of these three going away under a different name.
	 */
	private const array NOT_THE_HOSTS_RESPONSE = [
		SecurityTxtCannotOpenUrlExtensionNotLoadedException::class,
		SecurityTxtCannotOpenUrlUserAgentInvalidException::class,
		SecurityTxtOnlyIpv6HostButIpv6DisabledException::class,
	];


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
		private SecurityTxtLibraryVersion $libraryVersion,
		private LibraryVersions $libraryVersions,
		private string $responseTtl,
		private string $timeBetweenFetches,
	) {
	}


	public function validate(?string $url): ValidationResultTemplateParameters
	{
		$template = new ValidationResultTemplateParameters();
		$template->cacheTtl = $this->responseTtl;
		if ($url === null || trim($url) === '') {
			$template->showIntro = true;
			return $template;
		}
		try {
			$validatorUrl = $this->validatorHost->getHost($url);
		} catch (SecurityTxtValidatorHostException $e) {
			$this->templateParametersEnricher->addErrorMessageAndLogo($template, $e->errorMessage);
			return $template;
		}
		$host = $validatorUrl->getHost();
		$template->host = $host;
		$template->displayUrl = Html::fromText($host); // Initial value, checkHost() will set the URL, if there's no error

		try {
			$this->checkHost($validatorUrl, $template);
		} catch (SecurityTxtValidatorException $e) {
			$this->logger->logException($host, $e);
			$this->templateParametersEnricher->addErrorMessageAndLogo($template, Html::el()->setText("Can't fetch ")
				->addHtml(Html::el('code')->setText('security.txt'))
				->addText(' from ')
				->addHtml(Html::el('code')->setText($host))
				->addText(', please try again later'));
		} catch (SecurityTxtNotFoundException $e) {
			$errorMessage = $this->issueMessageFormatter->format($e->getMessageFormat(), $e->getMessageValues());
			$this->templateParametersEnricher->addErrorMessageAndLogo($template, $errorMessage);
			$template->allRedirects = $e->getAllRedirects();
			$this->addIpRangeNames($e, $errorMessage);
		} catch (SecurityTxtFetcherException $e) {
			$this->templateParametersEnricher->addErrorMessageAndLogo($template, $this->issueMessageFormatter->format($e->getMessageFormat(), $e->getMessageValues()));
		} catch (Throwable $e) {
			Debugger::log($e, Debugger::EXCEPTION);
			$this->templateParametersEnricher->addErrorMessageAndLogo($template, Html::el()->setText('Something went wrong while checking ')
				->addHtml(Html::el('code')->setText($host))
				->addText(', please try again later'));
		}
		return $template;
	}


	/**
	 * @throws JsonException
	 * @throws SecurityTxtFetcherException
	 * @throws SecurityTxtValidatorException
	 * @throws DateMalformedStringException
	 * @throws CannotCreateDateTimeObjectException
	 */
	private function checkHost(SecurityTxtValidatorUrl $url, ValidationResultTemplateParameters $template): void
	{
		$host = $url->getHost();
		$scheme = $url->getScheme();
		$asciiHost = $url->getAsciiHost();
		$port = $url->getPort();
		$now = $this->dateTimeFactory->getNow();
		$result = $this->database->fetch(
			'SELECT
				fetch_time AS fetchTime,
				check_result AS checkResult
			FROM responses
			WHERE scheme = ? AND ascii_host = ? AND port = ? AND fetch_time > ?
			ORDER BY fetch_time DESC, id DESC
			LIMIT 1',
			$scheme,
			$asciiHost,
			$port,
			$now->modify("-{$this->responseTtl}"),
		);
		$fetchAllowedIn = $this->fetchAllowedIn($url, $now);
		if ($result !== null && $this->addStoredResponse($host, $result, $template, $now, $fetchAllowedIn)) {
			return;
		}

		if ($fetchAllowedIn !== null) {
			// Nothing fresh for this exact origin and no fetch allowed yet, so a response that has merely gone stale is
			// worth more than an apology: it is what this origin last said, and saying so with its age beats saying
			// nothing until whoever is holding the host's allowance lets go
			$stale = $this->database->fetch(
				'SELECT
					fetch_time AS fetchTime,
					check_result AS checkResult
				FROM responses
				WHERE scheme = ? AND ascii_host = ? AND port = ?
				ORDER BY fetch_time DESC, id DESC
				LIMIT 1',
				$scheme,
				$asciiHost,
				$port,
			);
			$template->isStale = true; // set before, so a row that turns out to be unreadable does not leave it claiming otherwise
			if ($stale !== null && $this->addStoredResponse($host, $stale, $template, $now, $fetchAllowedIn)) {
				return;
			}
			$template->isStale = false;
			// Nothing stored either, so this host was fetched a moment ago with a different port or scheme
			$this->templateParametersEnricher->addFetchedTooRecently($template, $host, $fetchAllowedIn);
			return;
		}

		try {
			$response = $this->validatorFetch->fetch($url, false);
		} catch (SecurityTxtValidatorFetchFailedException $e) {
			// A host that answered and has no usable file has been checked, and the response is worth the same as any
			// other: without a row, the only checks the cache would rate-limit are the ones that succeeded
			$fetcherException = $e->getFetcherException();
			try {
				if (!in_array($fetcherException::class, self::NOT_THE_HOSTS_RESPONSE, true)) {
					$this->store($scheme, $asciiHost, $port, $this->dateTimeFactory->getNow(), Json::encode(['error' => $fetcherException]), $e->getFetcherVersion());
				}
			} catch (Throwable $cacheFailure) {
				// Failing to write the response down is ours to deal with, and throwing from here would throw away the
				// response itself, leaving the visitor with a generic apology instead of what their host actually said
				$this->logger->logException($host, $cacheFailure);
			}
			throw $fetcherException;
		}
		// When the fetch finished, not when the request started: everything downstream measures the age of the response
		// from this, and a slow fetch would otherwise hand back a row that is already part way through its life
		$fetchedAt = $this->dateTimeFactory->getNow();
		$parseResult = $this->securityTxtParser->parseFetchResult($response->getFetchResult());
		$checkHostResult = $this->checkHostResultFactory->create($url->getSecurityTxtHost(), $parseResult);
		// Stored before the template is filled in, so a write that fails cannot leave the page showing a whole result
		// with an error banner over it
		$this->store($scheme, $asciiHost, $port, $fetchedAt, Json::encode($checkHostResult), $response->getFetcherVersion());
		$this->templateParametersEnricher->addFromCheckHostResult($template, $checkHostResult, $fetchedAt, null, null);
	}


	private function store(string $scheme, string $asciiHost, int $port, DateTimeImmutable $fetchedAt, string $checkResult, DependencyVersion $fetcherVersion): void
	{
		$this->database->query('INSERT INTO responses', [
			'scheme' => $scheme,
			'ascii_host' => $asciiHost,
			'port' => $port,
			'fetch_time' => $fetchedAt,
			'check_result' => $checkResult,
			'key_parser_library_version' => $this->libraryVersions->getId($this->libraryVersion->getInstalled()),
			'key_fetcher_library_version' => $this->libraryVersions->getId($fetcherVersion),
		]);
	}


	/**
	 * Puts a stored response on the page, whether it is still fresh or only the last thing this origin said. False when
	 * the row could not be read back, which is a miss rather than an error: the caller goes and asks the host again.
	 *
	 * @throws SecurityTxtFetcherException The stored response, when what was stored was a failure. Thrown rather than
	 *     rendered here so it reaches the same arm in `validate()` that a live failure does, and one place decides
	 *     what a visitor is told. It passes the catch below untouched, which only reads a row that made no sense.
	 * @throws CannotCreateDateTimeObjectException
	 */
	private function addStoredResponse(
		string $host,
		Row $result,
		ValidationResultTemplateParameters $template,
		DateTimeImmutable $now,
		?DateInterval $fetchAllowedIn,
	): bool {
		assert(is_string($result->checkResult));
		assert($result->fetchTime instanceof DateTime);
		try {
			$decoded = Json::decode($result->checkResult, true);
			if (is_array($decoded)) {
				$fetchTime = $this->dateTimeFactory->createFrom($result->fetchTime);
				if (isset($decoded['error'])) {
					$this->templateParametersEnricher->addCacheTiming($template, $fetchTime, $now->diff($fetchTime), $fetchAllowedIn);
					throw $this->securityTxtJson->createFetcherExceptionFromJsonValues($decoded);
				}
				$this->templateParametersEnricher->addFromCheckHostResult(
					$template,
					$this->securityTxtJson->createCheckHostResultFromJsonValues($decoded),
					$fetchTime,
					$now->diff($fetchTime),
					$fetchAllowedIn,
				);
				return true;
			}
			$this->logger->log($host, "Ignoring cached policy, not an array: {$result->checkResult}");
		} catch (JsonException | SecurityTxtCannotParseJsonException $e) {
			$this->logger->logException($host, $e);
		}
		return false;
	}


	/**
	 * How long until this host can be fetched again, and null when it can be fetched now. Whole seconds, rounded up,
	 * because a visitor told to wait 19 and then refused at 19 has been told the wrong number: `DateInterval` counts
	 * whole seconds and would drop the fraction the row's second-precision timestamp leaves behind.
	 *
	 * One answer, used by everything that needs it: what a visitor is told to wait, what the page prints beside a
	 * cached result, and whether a fetch or a clear may go ahead. They cannot disagree if there is only one of them.
	 *
	 * @throws CannotCreateDateTimeObjectException
	 */
	private function fetchAllowedIn(SecurityTxtValidatorUrl $url, DateTimeImmutable $now): ?DateInterval
	{
		$recentFetch = $this->recentFetch($url, $now);
		if ($recentFetch === null) {
			return null;
		}
		$allowedAt = $recentFetch->modify("+{$this->timeBetweenFetches}");
		$seconds = (int)ceil((float)$allowedAt->format('U.u') - (float)$now->format('U.u'));
		return $seconds > 0 ? $now->diff($now->modify("+{$seconds} seconds")) : null;
	}


	/**
	 * The most recent fetch that makes this one wait, and null when it may happen now. Recent enough to count means
	 * within `timeBetweenFetches`.
	 *
	 * Two allowances per host, not one. The origin on the scheme's own port is the one nearly every visitor asks
	 * about, and it counts only its own fetches, so it cannot be taken away from them. Every other origin of that host
	 * shares the second allowance and counts any fetch of the host, so naming a port nobody has asked about buys no
	 * extra fetches: a host is one machine to be polite to however many names point at it.
	 *
	 * One allowance for all of them would let anyone hold a host's only slot open with two requests a minute, and
	 * nobody could check it again once the cached response aged out.
	 *
	 * @throws CannotCreateDateTimeObjectException
	 */
	private function recentFetch(SecurityTxtValidatorUrl $url, DateTimeImmutable $now): ?DateTimeImmutable
	{
		$since = $now->modify("-{$this->timeBetweenFetches}");
		$result = $url->isDefaultPort()
			? $this->database->fetch(
				'SELECT fetch_time AS fetchTime
				FROM responses
				WHERE ascii_host = ? AND scheme = ? AND port = ? AND fetch_time > ?
				ORDER BY fetch_time DESC, id DESC
				LIMIT 1',
				$url->getAsciiHost(),
				$url->getScheme(),
				$url->getPort(),
				$since,
			)
			: $this->database->fetch(
				'SELECT fetch_time AS fetchTime
				FROM responses
				WHERE ascii_host = ? AND fetch_time > ?
				ORDER BY fetch_time DESC, id DESC
				LIMIT 1',
				$url->getAsciiHost(),
				$since,
			);
		if ($result === null) {
			return null;
		}
		assert($result->fetchTime instanceof DateTime);
		return $this->dateTimeFactory->createFrom($result->fetchTime);
	}


	/**
	 * Takes what the visitor typed, the same as validate() does, because the field accepts a URL as well as a
	 * hostname and the rows have to be found by the host checkHost() writes them under.
	 */
	public function clearCache(string $url): void
	{
		try {
			$validatorUrl = $this->validatorHost->getHost($url);
		} catch (SecurityTxtValidatorHostException) {
			return;
		}
		$now = $this->dateTimeFactory->getNow();
		if ($this->fetchAllowedIn($validatorUrl, $now) !== null) {
			return;
		}
		$this->database->query(
			'DELETE FROM responses WHERE scheme = ? AND ascii_host = ? AND port = ? AND fetch_time <= ?',
			$validatorUrl->getScheme(),
			$validatorUrl->getAsciiHost(),
			$validatorUrl->getPort(),
			$now->modify("-{$this->timeBetweenFetches}"),
		);
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


	public function validateDirectInput(string $input, ValidationResultTemplateParameters $templateParameters): void
	{
		$parseStringResult = $this->securityTxtParser->parseString($input);
		$this->templateParametersEnricher->addFromParseStringResult($templateParameters, $parseStringResult, $input);
	}

}
