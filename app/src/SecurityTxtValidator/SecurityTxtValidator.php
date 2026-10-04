<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use DateInterval;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTimeFactoryUtc;
use MichalSpacekCz\DateTime\Exceptions\CannotCreateDateTimeObjectException;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorFetchFailedException;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorHostException;
use MichalSpacekCz\SecurityTxtValidator\Fetch\SecurityTxtValidatorFetch;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtIssueMessageFormatter;
use MichalSpacekCz\SecurityTxtValidator\Statistics\FileStatistics;
use MichalSpacekCz\SecurityTxtValidator\Statistics\Statistics;
use MichalSpacekCz\SecurityTxtValidator\Statistics\StatisticsVolume;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResultTemplateParameters;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResultTemplateParametersEnricher;
use Nette\Database\Row;
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
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtTooManyRedirectsException;
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
		private ResponseStorage $responseStorage,
		private DateTimeFactoryUtc $dateTimeFactory,
		private SecurityTxtJson $securityTxtJson,
		private SecurityTxtValidatorHost $validatorHost,
		private SecurityTxtParser $securityTxtParser,
		private SecurityTxtValidatorFetch $validatorFetch,
		private SecurityTxtCheckHostResultFactory $checkHostResultFactory,
		private SecurityTxtIssueMessageFormatter $issueMessageFormatter,
		private SecurityTxtValidatorLogger $logger,
		private ValidationResultTemplateParametersEnricher $templateParametersEnricher,
		private FileStatistics $fileStatistics,
		private Statistics $statistics,
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
			$this->templateParametersEnricher->addNotFoundProviderNames($host, $e, $errorMessage);
		} catch (SecurityTxtFetcherException $e) {
			if ($e instanceof SecurityTxtTooManyRedirectsException) {
				$this->logger->log($host, $e->getMessage());
			}
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
		$now = $this->dateTimeFactory->getNow();
		$freshSince = $now->modify("-{$this->responseTtl}");
		$result = $this->responseStorage->getNewestSince($url, $freshSince);
		$fetchAllowedIn = $this->fetchAllowedIn($url, $now);
		if ($result !== null && $this->addStoredResponse($host, $result, $template, $now, $fetchAllowedIn, false)) {
			return;
		}

		if ($fetchAllowedIn !== null) {
			$this->addStaleResponseOrWait($host, $url, $template, $now, $fetchAllowedIn);
			return;
		}

		$claimId = $this->responseStorage->claim($url, $now);
		if ($claimId === null) {
			// Another request took the origin between the reads above and this, so its fetch is what the visitor is
			// waiting for and the result will be there for them in a moment
			$this->templateParametersEnricher->addBeingChecked($template, $host);
			return;
		}
		// Read again now that the claim is held: nobody else can be writing this origin, so a response that landed
		// between the first read and the claim is found here and this one is not fetched on top of it
		$result = $this->responseStorage->getNewestSince($url, $freshSince);
		if ($result !== null && $this->addStoredResponse($host, $result, $template, $now, $this->fetchAllowedIn($url, $now, $claimId), false)) {
			$this->responseStorage->release($claimId);
			return;
		}
		// A fetch of the host under the other allowance may have finished in that gap too, and that one the claim could
		// not collide with, because it was over by the time the claim was taken; so the wait is checked once more, with
		// this request's own claim left out of it, and a fetch found there means waiting after all
		$fetchAllowedIn = $this->fetchAllowedIn($url, $now, $claimId);
		if ($fetchAllowedIn !== null) {
			$this->responseStorage->release($claimId);
			$this->addStaleResponseOrWait($host, $url, $template, $now, $fetchAllowedIn);
			return;
		}

		try {
			$response = $this->validatorFetch->fetch($url, false);
			// When the fetch finished, not when the request started: everything downstream measures the age of the
			// response from this, and a slow fetch would otherwise hand back a row that is already part way through its life
			$fetchedAt = $this->dateTimeFactory->getNow();
			$parseResult = $this->securityTxtParser->parseFetchResult($response->getFetchResult());
			$checkHostResult = $this->checkHostResultFactory->create($url->getSecurityTxtHost(), $parseResult);
			// Stored before the template is filled in, so a write that fails cannot leave the page showing a whole result
			// with an error banner over it
			$this->responseStorage->fill($claimId, $fetchedAt, Json::encode($checkHostResult), $response->getFetcherVersion());
		} catch (SecurityTxtValidatorFetchFailedException $e) {
			// A host that answered and has no usable file has been checked, and the response is worth the same as any
			// other: without a row, the only checks the cache would rate-limit are the ones that succeeded
			$fetcherException = $e->getFetcherException();
			try {
				if (in_array($fetcherException::class, self::NOT_THE_HOSTS_RESPONSE, true)) {
					$this->responseStorage->release($claimId);
				} else {
					$this->responseStorage->fill($claimId, $this->dateTimeFactory->getNow(), Json::encode(['error' => $fetcherException]), $e->getFetcherVersion());
					$this->statistics->increment(StatisticsVolume::Fetched, ...$this->fileStatistics->forFetchFailure($fetcherException));
				}
			} catch (Throwable $cacheFailure) {
				// Failing to write the response down is ours to deal with, and throwing from here would throw away the
				// response itself, leaving the visitor with a generic apology instead of what their host actually said
				$this->logger->logException($host, $cacheFailure);
				// The fetch is over, so the claim must not go on holding the origin until it counts as abandoned
				try {
					$this->responseStorage->release($claimId);
				} catch (Throwable $releaseFailure) {
					$this->logger->logException($host, $releaseFailure);
				}
			}
			throw $fetcherException;
		} catch (Throwable $e) {
			// Whatever broke was ours, and the claim must not go on holding the origin for it
			try {
				$this->responseStorage->release($claimId);
			} catch (Throwable $releaseFailure) {
				$this->logger->logException($host, $releaseFailure);
			}
			throw $e;
		}
		$this->statistics->increment(StatisticsVolume::Fetched, ...$this->fileStatistics->forCheckHostResult($checkHostResult));
		$this->templateParametersEnricher->addFromCheckHostResult($template, $checkHostResult, $fetchedAt, null, null);
	}


	/**
	 * Nothing fresh for this exact origin and no fetch allowed yet, so a response that has merely gone stale is worth
	 * more than an apology: it is what this origin last said, and saying so with its age beats saying nothing until
	 * whoever is holding the host's allowance lets go.
	 *
	 * @throws SecurityTxtFetcherException
	 * @throws CannotCreateDateTimeObjectException
	 */
	private function addStaleResponseOrWait(string $host, SecurityTxtValidatorUrl $url, ValidationResultTemplateParameters $template, DateTimeImmutable $now, DateInterval $fetchAllowedIn): void
	{
		$stale = $this->responseStorage->getNewest($url);
		if ($stale !== null && $this->addStoredResponse($host, $stale, $template, $now, $fetchAllowedIn, true)) {
			return;
		}
		// Nothing stored either, so this host was fetched a moment ago with a different port or scheme
		$this->templateParametersEnricher->addFetchedTooRecently($template, $host, $fetchAllowedIn);
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
		bool $isStale,
	): bool {
		assert(is_string($result->checkResult));
		assert($result->fetchTime instanceof DateTime);
		try {
			$decoded = Json::decode($result->checkResult, true);
			if (is_array($decoded)) {
				$fetchTime = $this->dateTimeFactory->createFrom($result->fetchTime);
				if (isset($decoded['error'])) {
					$storedFailure = $this->securityTxtJson->createFetcherExceptionFromJsonValues($decoded);
					$this->templateParametersEnricher->addCacheTiming($template, $fetchTime, $now->diff($fetchTime), $fetchAllowedIn);
					$template->isStale = $isStale;
					$this->statistics->increment($isStale ? StatisticsVolume::Stale : StatisticsVolume::Cached);
					throw $storedFailure;
				}
				$this->templateParametersEnricher->addFromCheckHostResult(
					$template,
					$this->securityTxtJson->createCheckHostResultFromJsonValues($decoded),
					$fetchTime,
					$now->diff($fetchTime),
					$fetchAllowedIn,
				);
				$template->isStale = $isStale;
				$this->statistics->increment($isStale ? StatisticsVolume::Stale : StatisticsVolume::Cached);
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
	 * The caller's own claim, named by `$exceptClaimId` once it holds one, is left out: it is a fetch about to start,
	 * not a recent one.
	 *
	 * @throws CannotCreateDateTimeObjectException
	 */
	private function fetchAllowedIn(SecurityTxtValidatorUrl $url, DateTimeImmutable $now, ?int $exceptClaimId = null): ?DateInterval
	{
		$recentFetch = $this->responseStorage->getRecentFetchTime($url, $now->modify("-{$this->timeBetweenFetches}"), $exceptClaimId);
		if ($recentFetch === null) {
			return null;
		}
		$allowedAt = $recentFetch->modify("+{$this->timeBetweenFetches}");
		$seconds = (int)ceil((float)$allowedAt->format('U.u') - (float)$now->format('U.u'));
		return $seconds > 0 ? $now->diff($now->modify("+{$seconds} seconds")) : null;
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
		$this->responseStorage->deleteFetchedUpTo($validatorUrl, $now->modify("-{$this->timeBetweenFetches}"));
	}


	public function validateDirectInput(string $input, ValidationResultTemplateParameters $templateParameters): void
	{
		$parseStringResult = $this->securityTxtParser->parseString($input);
		$this->templateParametersEnricher->addFromParseStringResult($templateParameters, $parseStringResult, $input);
		$this->statistics->increment(StatisticsVolume::Pasted, ...$this->fileStatistics->forParseStringResult($parseStringResult));
	}

}
