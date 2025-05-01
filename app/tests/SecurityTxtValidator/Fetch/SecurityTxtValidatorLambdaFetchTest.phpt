<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Fetch;

use AsyncAws\Core\Test\Http\SimpleMockedResponse;
use AsyncAws\Lambda\LambdaClient;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
use MichalSpacekCz\SecurityTxtValidator\LambdaFunctions;
use MichalSpacekCz\SecurityTxtValidator\LambdaResponse;
use MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck\SecurityTxtValidatorLambdaVersionCheck;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorHost;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Json;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\Json\SecurityTxtJson;
use Spaze\SecurityTxt\Violations\SecurityTxtTopLevelDiffers;
use Symfony\Component\HttpClient\MockHttpClient;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorLambdaFetchTest extends TestCase
{

	private const string FILE_URL = 'https://xn--khby.example/.well-known/security.txt';

	private ?string $sentBody = null;


	public function __construct(
		private readonly LambdaResponse $lambdaResponse,
		private readonly LambdaFunctions $lambdaFunctions,
		private readonly SecurityTxtJson $securityTxtJson,
		private readonly SecurityTxtValidatorLambdaVersionCheck $lambdaVersionCheck,
		private readonly SecurityTxtValidatorLogger $logger,
		private readonly SecurityTxtValidatorHost $validatorHost,
	) {
	}


	private function createFetch(SimpleMockedResponse $response): SecurityTxtValidatorLambdaFetch
	{
		$httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($response): SimpleMockedResponse {
			$body = $options['body'] ?? null;
			if (is_string($body)) {
				$this->sentBody = $body;
			}
			return $response;
		});
		return new SecurityTxtValidatorLambdaFetch(
			new LambdaClient(['region' => 'eu-west-1', 'accessKeyId' => 'key', 'accessKeySecret' => 'secret'], null, $httpClient),
			$this->lambdaResponse,
			$this->lambdaFunctions,
			$this->securityTxtJson,
			$this->lambdaVersionCheck,
			$this->logger,
			true,
			'spaze/security-txt',
		);
	}


	/**
	 * The Lambda re-parses whatever string it is handed, and a host written in punycode does not always survive being
	 * decoded into the readable spelling: `xn--khby.example` reads as a name that re-encodes to `xn--jgb.example`,
	 * somewhere else entirely. Sending the ASCII form keeps the host the app keyed, displayed and asked about the same
	 * host the Lambda goes and fetches.
	 */
	public function testFetchSendsTheHostInAsciiSoItCannotBecomeAnotherHost(): void
	{
		$fetch = $this->createFetch(new SimpleMockedResponse('', ['x-amz-function-error' => ['Nope']], 202));
		$url = $this->validatorHost->getHost('https://xn--khby.example/');
		Assert::same('xn--khby.example', $url->getAsciiHost()); // what the app keys and displays
		Assert::exception(function () use ($fetch, $url): void {
			$fetch->fetch($url, false);
		}, SecurityTxtValidatorException::class);

		assert(is_string($this->sentBody));
		$payload = Json::decode($this->sentBody, true);
		assert(is_array($payload));
		Assert::same('https://xn--khby.example/', $payload['host']);
	}


	/**
	 * Every field at this boundary is read only once it has been checked for, so a reply claiming success without
	 * saying what it found is refused by name rather than by an array function tripping over nothing.
	 */
	public function testAReplyThatSaysNothingAboutWhatItFoundIsRefused(): void
	{
		$response = Json::encode([
			'status' => 'OK',
			'libVersion' => '3.0.0',
			'libReference' => 'v3.0.0',
		]);
		$fetch = $this->createFetch(new SimpleMockedResponse($response));
		Assert::exception(function () use ($fetch): void {
			$fetch->fetch($this->validatorHost->getHost('https://example.com/'), false);
		}, SecurityTxtValidatorException::class, 'fetchResult is missing or not an array: %A%');
	}


	/**
	 * A reply the Lambda considers a success is the only one the app has anything to show, and it arrives as JSON that
	 * has to survive being turned back into a result object.
	 */
	public function testFetchReturnsWhatTheLambdaFound(): void
	{
		$contents = "Contact: mailto:security@xn--khby.example\n";
		$response = Json::encode([
			'status' => 'OK',
			'libVersion' => '3.0.0',
			'libReference' => 'v3.0.0',
			'fetchResult' => [
				'class' => SecurityTxtFetchResult::class,
				'constructedUrl' => self::FILE_URL,
				'finalUrl' => self::FILE_URL,
				'redirects' => [],
				'contents' => $contents,
				'isTruncated' => false,
				'errors' => [],
				'warnings' => [],
			],
		]);
		$fetch = $this->createFetch(new SimpleMockedResponse($response));
		$result = $fetch->fetch($this->validatorHost->getHost('https://xn--khby.example/'), false);
		Assert::same($contents, $result->getContents());
		Assert::same(self::FILE_URL, $result->getFinalUrl()->toAsciiString());
		Assert::false($result->isTruncated());
	}


	/**
	 * A file the host did not write in UTF-8 cannot travel as a JSON string, so the Lambda sends it under
	 * `contentsBase64` with `contents` gone. The bytes the app gets have to be the bytes the host served, because the
	 * only thing to say about such a file is that its encoding is wrong, and that is decided by the bytes.
	 */
	public function testFetchReadsAFileThatIsNotUtf8(): void
	{
		$contents = "Contact: mailto:security@example.com\n# Kontakt: Michal \xA9pa\xE8ek\n";
		Assert::false(mb_check_encoding($contents, 'UTF-8')); // or this test proves nothing
		$response = Json::encode([
			'status' => 'OK',
			'libVersion' => '3.1.0',
			'libReference' => 'v3.1.0',
			'fetchResult' => [
				'class' => SecurityTxtFetchResult::class,
				'constructedUrl' => self::FILE_URL,
				'finalUrl' => self::FILE_URL,
				'redirects' => [],
				'contentsBase64' => base64_encode($contents),
				'isTruncated' => false,
				'errors' => [],
				'warnings' => [],
			],
		]);
		$fetch = $this->createFetch(new SimpleMockedResponse($response));
		$result = $fetch->fetch($this->validatorHost->getHost('https://xn--khby.example/'), false);
		Assert::same($contents, $result->getContents());
		Assert::same("# Kontakt: Michal \xA9pa\xE8ek\n", $result->getLine(2));
	}


	/**
	 * A warning names the two files it compared, so its arguments carry their contents and travel under `paramsBase64`
	 * when either file is not UTF-8. Every string in that value is Base64, the one that would have travelled as itself
	 * included, so a reader that decodes only what looks encoded gets the other one wrong.
	 */
	public function testFetchReadsAWarningNamingAFileThatIsNotUtf8(): void
	{
		$wellKnown = "Contact: mailto:a@example.com\n# \xA9pa\xE8ek\n";
		$topLevel = "Contact: mailto:b@example.com\n";
		Assert::false(mb_check_encoding($wellKnown, 'UTF-8'));
		Assert::true(mb_check_encoding($topLevel, 'UTF-8')); // and it is Base64 all the same
		$response = Json::encode([
			'status' => 'OK',
			'libVersion' => '3.1.0',
			'libReference' => 'v3.1.0',
			'fetchResult' => [
				'class' => SecurityTxtFetchResult::class,
				'constructedUrl' => self::FILE_URL,
				'finalUrl' => self::FILE_URL,
				'redirects' => [],
				'contents' => '',
				'isTruncated' => false,
				'errors' => [],
				'warnings' => [
					[
						'class' => SecurityTxtTopLevelDiffers::class,
						'paramsBase64' => [base64_encode($wellKnown), base64_encode($topLevel)],
					],
				],
			],
		]);
		$fetch = $this->createFetch(new SimpleMockedResponse($response));
		$result = $fetch->fetch($this->validatorHost->getHost('https://xn--khby.example/'), false);
		$warning = $result->getWarnings()[0];
		assert($warning instanceof SecurityTxtTopLevelDiffers);
		Assert::same($wellKnown, $warning->getWellKnownContents());
		Assert::same($topLevel, $warning->getTopLevelContents());
	}

}

TestCaseRunner::run(SecurityTxtValidatorLambdaFetchTest::class);
