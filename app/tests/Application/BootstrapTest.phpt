<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\DI\Container;
use Nette\Utils\FileSystem;
use Override;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;
use Tracy\Debugger;
use Tracy\Dumper;
use Tracy\ILogger;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class BootstrapTest extends TestCase
{

	private string $exceptionLog;
	private ?string $tempLog = null;

	/** @var list<string> */
	private array $tempDirs = [];


	public function __construct()
	{
		$logDirectory = Debugger::$logDirectory;
		assert(is_string($logDirectory), 'Call Nette\Bootstrap\Configurator::enableTracy() first, possibly in MichalSpacekCz\Application\Bootstrap::createConfigurator()');
		$this->exceptionLog = $logDirectory . '/' . ILogger::EXCEPTION . '.log';
		if (file_exists($this->exceptionLog)) {
			$this->tempLog = $this->exceptionLog . '.' . uniqid(more_entropy: true);
			rename($this->exceptionLog, $this->tempLog);
		}
		ServerEnv::setString('SERVER_NAME', 'michalspacek.cz');
	}


	#[Override]
	protected function tearDown(): void
	{
		foreach ($this->tempDirs as $tempDir) {
			FileSystem::delete($tempDir);
		}
		$this->tempDirs = [];
	}


	/**
	 * Each boot gets its own temp directory: the container cache key includes dynamic parameter names, never
	 * values, so a container compiled into the shared temp/ and checked only against the template's placeholders
	 * could be reused by a real boot with the same key
	 */
	private function newTempDir(): string
	{
		$tempDir = sys_get_temp_dir() . '/bootstrap-test-' . bin2hex(random_bytes(8));
		$this->tempDirs[] = $tempDir;
		return $tempDir;
	}


	public function __destruct()
	{
		if (file_exists($this->exceptionLog)) {
			echo file_get_contents($this->exceptionLog);
			unlink($this->exceptionLog);
		}
		if ($this->tempLog !== null && file_exists($this->tempLog)) {
			rename($this->tempLog, $this->exceptionLog);
		}
	}


	/**
	 * @return array<string, array{environment:string|null}>
	 */
	public function getBootEnvironments(): array
	{
		return [
			'production' => [
				'environment' => null,
			],
			'development' => [
				'environment' => 'development',
			],
		];
	}


	/** @dataProvider getBootEnvironments */
	public function testBoot(?string $environment): void
	{
		if ($environment === null) {
			ServerEnv::unset('ENVIRONMENT');
		} else {
			ServerEnv::setString('ENVIRONMENT', $environment);
		}
		$container = null;
		Assert::noError(function () use (&$container): void {
			// The committed template stands in for the gitignored secrets.neon, so this boots the same on every machine
			$container = Bootstrap::bootContainerTest(__DIR__ . '/../../config/secrets.template.neon', $this->newTempDir());
		});
		Assert::type(Container::class, $container);

		// Booting has to leave the scrubber on the blue screen, so no dump in the exception log shows a value
		// equal to a current secret; checked with a placeholder from the template that booted above
		$scrubber = Debugger::getBlueScreen()->scrubber;
		Assert::notNull($scrubber);
		assert($scrubber !== null); // Assert::notNull() doesn't narrow the type for phpstan and psalm
		Assert::true($scrubber('apiKey', 'britishteaatfiveoclockisnotacoffee', null), 'the blue screen scrubber does not hide a current secret');
		Assert::true($scrubber('users', ['britishteaatfiveoclockisnotacoffee' => 'web'], null), 'the blue screen scrubber does not hide a structure with a secret as a key, and Tracy always renders key names');
		Assert::false($scrubber('apiKey', 'no secret has this value', null), 'the blue screen scrubber hides more than the secrets, which would make every dump useless');
		// The closure holds the values it compares against, and Tracy dumps closure captures, so they're wrapped
		// in SensitiveParameterValue, which Tracy redacts: dumping the scrubber itself must show nothing
		Assert::notContains('britishteaatfiveoclockisnotacoffee', Dumper::toText($scrubber, [Dumper::DEPTH => 8]), 'dumping the scrubber closure shows the secrets it holds');
	}


	public function testEmptySecretsFileIsRefused(): void
	{
		// An empty mapping would replace the whole declared %secrets% tree with nothing, and the boot would
		// then die later, on the first service reading a secret, with an error that never says what's missing
		$file = sys_get_temp_dir() . '/bootstrap-test-secrets-' . bin2hex(random_bytes(8)) . '.neon';
		FileSystem::write($file, "[]\n");
		try {
			Assert::exception(function () use ($file): void {
				Bootstrap::bootContainerTest($file, $this->newTempDir());
			}, RuntimeException::class, "{$file} is empty%a%");
		} finally {
			FileSystem::delete($file);
		}
	}


	public function testListSecretsFileIsRefused(): void
	{
		// Decodes to a PHP array like a mapping does, but no named path can ever resolve in it, so the boot
		// would die later with an error that never says what's wrong
		$file = sys_get_temp_dir() . '/bootstrap-test-secrets-' . bin2hex(random_bytes(8)) . '.neon';
		FileSystem::write($file, "- abcdefgh\n");
		try {
			Assert::exception(function () use ($file): void {
				Bootstrap::bootContainerTest($file, $this->newTempDir());
			}, RuntimeException::class, '%a%neon mapping keyed by the secret names%a%list');
		} finally {
			FileSystem::delete($file);
		}
	}


	public function testBrokenSecretsFileIsReportedWithoutItsContents(): void
	{
		// Built at runtime so the value can't reach any log through the source excerpt of this frame
		$token = 'brokenfile-' . bin2hex(random_bytes(8));
		$file = sys_get_temp_dir() . '/bootstrap-test-secrets-' . bin2hex(random_bytes(8)) . '.neon';
		// The unclosed quote is what a hand-edit typo looks like; the parser's own message quotes the file
		FileSystem::write($file, "apiKey: '{$token}\n");
		try {
			$e = Assert::exception(function () use ($file): void {
				Bootstrap::bootContainerTest($file, $this->newTempDir());
			}, RuntimeException::class, "Loading {$file} failed with %a%");
			assert($e !== null); // Assert::exception() doesn't narrow the type for phpstan and psalm
			Assert::notContains($token, $e->getMessage(), 'the replacement message quotes the broken file');
			// The rendered log guards the not-chained part too: a chained original carries the whole file
			// as the decoder's argument, and Tracy dumps arguments
			$log = $file . '.bluescreen.html';
			Assert::true(Debugger::getBlueScreen()->renderToFile($e, $log), 'Tracy refused to write the blue screen');
			Assert::notContains($token, FileSystem::read($log), 'the exception log leaked the broken file');
			Assert::notContains($token, FileSystem::read($file . '.bluescreen.md'), 'the agent log leaked the broken file');
		} finally {
			FileSystem::delete($file);
			FileSystem::delete($file . '.bluescreen.html');
			FileSystem::delete($file . '.bluescreen.md');
		}
	}

}

TestCaseRunner::run(BootstrapTest::class);
