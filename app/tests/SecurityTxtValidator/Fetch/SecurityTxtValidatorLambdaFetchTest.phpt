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

}

TestCaseRunner::run(SecurityTxtValidatorLambdaFetchTest::class);
