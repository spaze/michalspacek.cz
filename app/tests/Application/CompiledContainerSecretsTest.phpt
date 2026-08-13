<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Composer\Pcre\Preg;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Bootstrap\Configurator;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\Definitions\Statement;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Override;
use RuntimeException;
use SensitiveParameter;
use Tester\Assert;
use Tester\TestCase;
use Throwable;
use Tracy\BlueScreen;

require __DIR__ . '/../bootstrap.php';

/*
 * The secrets from config/secrets.neon must never be written into the container Nette compiles into temp/:
 * Tracy renders the source of every frame into the exception log, and `#[SensitiveParameter]` and `keysToHide`
 * both work on values, so neither reaches source code. Building an encryption service is where that bites,
 * its constructor rejects a bad key and the frame is the generated factory, with the key in the excerpt if it
 * was compiled in. Bootstrap passes the secrets as dynamic parameters, supplied at runtime and never written
 * to the container, and these tests hold that in place.
 */

/** @testCase */
final class CompiledContainerSecretsTest extends TestCase
{

	/** @var list<string> */
	private array $tempDirs = [];


	public function __construct(
		private readonly BlueScreen $blueScreen, // the container's, so this runs against the configured keysToHide
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		foreach ($this->tempDirs as $tempDir) {
			FileSystem::delete($tempDir);
		}
		$this->tempDirs = [];
		$this->blueScreen->scrubber = null; // the container's blue screen is shared, the scrubber isn't this test's to keep
	}


	public function testKeyIsNotInTheExceptionLogWhenTheEncryptionServiceFailsToBuild(): void
	{
		$key = 'msee_' . str_repeat('ab', 32);

		Assert::false($this->keyInRenderedBlueScreen($key, dynamic: true), 'the key reached the exception log even as a dynamic parameter');
		Assert::true($this->keyInRenderedBlueScreen($key, dynamic: false), 'a key written into the container was not found either, so this would pass whatever it rendered');
	}


	public function testStaleValueUnderParametersSecretsIsRefused(): void
	{
		$secret = "refused-o'brien-" . bin2hex(random_bytes(8));
		// A stale value under parameters.secrets, different from everything in secrets.neon: what a local.neon
		// not cleaned up after a rotation looks like; refused whatever the value is. Built at runtime, a literal
		// would leak into the log check below through the source excerpt Tracy renders for this very frame
		$staleValue = 'stale-rotated-' . bin2hex(random_bytes(8));
		ContainerSecretsChecker::hideSecrets($this->blueScreen, ['apiKey' => $secret]);

		$thrown = $this->compileRefused('a config with a static value under parameters.secrets was accepted', ['apiKey' => $secret], [
			'factory' => 'ArrayObject',
			'arguments' => [['key' => '%secrets.apiKey%']],
		], extraConfig: ['parameters' => ['secrets' => ['apiKey' => $staleValue]]]);
		Assert::match('%a%secrets.apiKey%a%statically%a%', $thrown->getMessage());
		$files = glob($this->lastTempDir() . '/cache/nette.configurator/Container_*.php');
		Assert::same([], $files === false ? [] : $files, 'the refused container was written anyway');
		// The stale value isn't a current secret, so the scrubber doesn't know it and nothing hides it by name
		// either: it stays out of the log only as long as the config arrays sit deeper than Tracy's maxDepth,
		// and this is here to notice when that stops being true
		$this->assertNoLeakInRenderedBlueScreen($thrown, 'refused-stale', substr($staleValue, -16));
	}


	public function testScalarDirectlyAtParametersSecretsIsRefused(): void
	{
		$secret = "refused-o'brien-" . bin2hex(random_bytes(8));
		// The leaf path is then exactly `parameters.secrets`, which a prefix match with the trailing dot would miss
		$staleValue = 'stale-rotated-' . bin2hex(random_bytes(8));
		ContainerSecretsChecker::hideSecrets($this->blueScreen, ['apiKey' => $secret]);

		$thrown = $this->compileRefused('a scalar assigned directly to parameters.secrets was accepted', ['apiKey' => $secret], [
			'factory' => 'ArrayObject',
		], extraConfig: ['parameters' => ['secrets' => $staleValue]]);
		Assert::match('%a%secrets%a%statically%a%', $thrown->getMessage());
		// The direct scalar sits one level shallower in the dumped config arrays than the nested cases, so its
		// staying out of the log is pinned separately
		$this->assertNoLeakInRenderedBlueScreen($thrown, 'refused-direct', substr($staleValue, -16));
	}


	public function testStaleKeyUnderParametersSecretsIsRefused(): void
	{
		$secret = "refused-o'brien-" . bin2hex(random_bytes(8));
		$staleValue = 'stale-rotated-' . bin2hex(random_bytes(8));

		// A stale key with nothing under it, an empty array here: no leaf comes out of that, while Nette still
		// compiles the key text into the dynamic parameter's fallback structure, so the keys themselves have to
		// be ones the current secrets declare
		$thrown = $this->compileRefused('a stale key with an empty array under parameters.secrets was accepted', ['apiKey' => $secret], [
			'factory' => 'ArrayObject',
		], extraConfig: ['parameters' => ['secrets' => ['apiKey' => [$staleValue => []]]]]);
		Assert::match('%a%keys under secrets.apiKey%a%does not have%a%', $thrown->getMessage());
		Assert::notContains(substr($staleValue, -16), $thrown->getMessage(), 'the message leaked the stale key');
	}


	public function testStaleNumericKeyUnderParametersSecretsIsRefused(): void
	{
		$secret = "refused-o'brien-" . bin2hex(random_bytes(8));

		// A digit-only stale key, which PHP has turned into an integer by the time it's a key
		$thrown = $this->compileRefused('a stale numeric key with an empty array under parameters.secrets was accepted', ['apiKey' => $secret], [
			'factory' => 'ArrayObject',
		], extraConfig: ['parameters' => ['secrets' => ['apiKey' => [12345678 => []]]]]);
		Assert::match('%a%keys under secrets.apiKey%a%does not have%a%', $thrown->getMessage());
	}


	public function testValueUnderStaleKeyBelowParametersSecretsIsRefused(): void
	{
		$secret = "refused-o'brien-" . bin2hex(random_bytes(8));
		$staleValue = 'stale-rotated-' . bin2hex(random_bytes(8));
		ContainerSecretsChecker::hideSecrets($this->blueScreen, ['apiKey' => $secret]);

		// Nothing can recognize a stale secret, so the refusal names the path only down to what the current
		// secrets declare
		$thrown = $this->compileRefused('a config with a value under a stale key below parameters.secrets was accepted', ['apiKey' => $secret], [
			'factory' => 'ArrayObject',
		], extraConfig: ['parameters' => ['secrets' => ['apiKey' => [$staleValue => 'x']]]]);
		Assert::match('%a%secrets.apiKey.…%a%statically%a%', $thrown->getMessage());
		Assert::notContains(substr($staleValue, -16), $thrown->getMessage(), 'the message leaked the stale key');
		$this->assertNoLeakInRenderedBlueScreen($thrown, 'refused-stale-key', substr($staleValue, -16));
	}


	public function testConfigValueEqualToACurrentSecretIsRefused(): void
	{
		$secret = "refused-o'brien-" . bin2hex(random_bytes(8));
		ContainerSecretsChecker::hideSecrets($this->blueScreen, ['apiKey' => $secret]);

		// A current secret copied verbatim anywhere in the config, a pasted password say; a substring inside
		// a longer value is deliberately not a match, that's how an unrelated `localhost` stays a coincidence
		$thrown = $this->compileRefused('a config value equal to a current secret was accepted', ['apiKey' => $secret], [
			'factory' => 'ArrayObject',
			'arguments' => [$secret],
		]);
		Assert::match('%a%secrets.apiKey%a%statically%a%', $thrown->getMessage());
		Assert::notContains($secret, $thrown->getMessage(), 'the message leaked the value it exists to protect');
		$this->assertNoLeakInRenderedBlueScreen($thrown, 'refused-value', substr($secret, -16));
	}


	public function testConfigKeyEqualToACurrentSecretIsRefused(): void
	{
		$secret = "refused-o'brien-" . bin2hex(random_bytes(8));
		ContainerSecretsChecker::hideSecrets($this->blueScreen, ['apiKey' => $secret]);

		// A current secret pasted as a KEY: Nette emits config keys into the generated code as literals just
		// like values. The value under it is the secret too, so this also checks that the refusal names only
		// what holds the key, the paths of value matches included, a path can have the key as a segment.
		// The scrubber can't hide a key by hiding its value, Tracy always renders key names, so the structure
		// holding the key has to disappear from the rendered log whole
		$thrown = $this->compileRefused('a config key equal to a current secret was accepted', ['apiKey' => $secret], [
			'factory' => 'ArrayObject',
			'arguments' => [[$secret => $secret]],
		]);
		Assert::match('%a%secrets.apiKey (a key under%a%statically%a%', $thrown->getMessage());
		Assert::notContains($secret, $thrown->getMessage(), 'the message leaked the key it exists to protect');
		$this->assertNoLeakInRenderedBlueScreen($thrown, 'refused-key', substr($secret, -16));
	}


	public function testStatementEntityEqualToACurrentSecretIsRefused(): void
	{
		$secret = "refused-o'brien-" . bin2hex(random_bytes(8));

		// The entity, the class name a `new` is compiled from, sits in a private property that get_object_vars()
		// would miss. Nested in arguments, because a secret in the factory position dies earlier, on Nette
		// resolving the class, before any check here can run
		$thrown = $this->compileRefused('a Statement entity equal to a current secret was accepted', ['apiKey' => $secret], [
			'factory' => 'ArrayObject',
			'arguments' => [new Statement($secret)],
		]);
		Assert::match('%a%secrets.apiKey%a%statically%a%', $thrown->getMessage());
		Assert::notContains($secret, $thrown->getMessage(), 'the message leaked the value it exists to protect');
	}


	public function testConfigWithoutCopiesCompilesWithTheGuardInPlace(): void
	{
		$secret = "refused-o'brien-" . bin2hex(random_bytes(8));

		// Including a config value that is a substring of a secret: that's the `localhost` coincidence, not a copy
		Assert::noError(function () use ($secret): void {
			$this->compile(['apiKey' => $secret], dynamic: true, guarded: true, service: [
				'factory' => 'ArrayObject',
				'arguments' => [['key' => '%secrets.apiKey%', 'coincidence' => substr($secret, 0, 12)]],
			]);
		});
	}


	/**
	 * Real configs arrive as files and go through the NeonAdapter before any extension sees them: it rewrites
	 * values and strips key suffixes, and the array-based compiles above never exercise it, so this does.
	 * Parameter expansion runs only after the check, but it can only copy what some parameter already holds,
	 * and that parameter is itself a config leaf the checker sees raw.
	 */
	public function testFileBackedConfigIsCheckedAfterTheAdapter(): void
	{
		$secret = 'filebacked-' . bin2hex(random_bytes(8));

		// A secret pasted as an ordinary parameter, referenced by a service: the refusal names the parameter,
		// the source every later expansion would copy from
		$thrown = $this->compileRefused('a file-backed config with a secret pasted as a parameter was accepted', ['apiKey' => $secret], [
			'factory' => 'ArrayObject',
		], extraConfigNeon: "parameters:\n\tprobe: '{$secret}'\n\nservices:\n\tprobe2: ArrayObject(%probe%)\n");
		Assert::match('%a%secrets.apiKey (parameters.probe)%a%', $thrown->getMessage());
		Assert::notContains($secret, $thrown->getMessage(), 'the message leaked the value it exists to protect');

		// A stale key under parameters.secrets with the adapter's ! suffix: the adapter strips the suffix and
		// puts its own merge marker under the key, so what the rules see is neither the key nor the value the
		// file has, and this is here to notice if that ever stops being caught
		$staleValue = 'stalebang-' . bin2hex(random_bytes(8));
		$thrown = $this->compileRefused('a file-backed config with a stale key under parameters.secrets was accepted', ['apiKey' => $secret], [
			'factory' => 'ArrayObject',
		], extraConfigNeon: "parameters:\n\tsecrets:\n\t\t{$staleValue}!: []\n");
		Assert::match('%a%secrets.…%a%statically%a%', $thrown->getMessage());
		Assert::notContains(substr($staleValue, -16), $thrown->getMessage(), 'the message leaked the stale key');
	}


	/**
	 * A boot with no secrets file, a CLI one on CI say, still compiles, and the compile check can still refuse
	 * a stale value under parameters.secrets, dumping the config arrays into the exception log: the rule hiding
	 * anything keyed `secrets` must hold with nothing loaded at all
	 */
	public function testScrubberHidesTheSecretsKeyWithNoSecretsLoaded(): void
	{
		ContainerSecretsChecker::hideSecrets($this->blueScreen, []);
		$scrubber = $this->blueScreen->scrubber;
		Assert::notNull($scrubber);
		assert($scrubber !== null); // Assert::notNull() doesn't narrow the type for phpstan and psalm
		Assert::true($scrubber('secrets', 'stale-rotated-away-value', null), 'a structure keyed `secrets` is not hidden when no secrets are loaded');
		Assert::false($scrubber('anything', 'ordinary-value', null), 'an empty secrets list hides unrelated values, which would make every dump useless');
	}


	public function testSecretsTooShortToVerifyAreRefusedNotSkipped(): void
	{
		$e = Assert::exception(function (): void {
			// a value this short can equal an ordinary config value by accident, `www` would match a subdomain
			ContainerSecretsChecker::checkValues(['database' => ['default' => ['password' => 'www']]]);
		}, RuntimeException::class, '%a%secrets.database.default.password%a%');
		assert($e !== null);
		Assert::notContains("'www'", $e->getMessage(), 'the message leaked the value it exists to protect');

		Assert::exception(function (): void {
			// a number would lose leading zeros, and the compiled-container check only searches for strings
			ContainerSecretsChecker::checkValues(['database' => ['default' => ['password' => 12345678901]]]);
		}, RuntimeException::class, '%a%secrets.database.default.password%a%not strings%a%');

		Assert::noError(function (): void {
			// an empty string means not set, that's the declared shape's own convention, and long values are fine
			ContainerSecretsChecker::checkValues(['database' => ['default' => ['password' => 'long-enough-password'], 'admin' => ['password' => '']]]);
		});
	}


	/**
	 * NEON is quote-optional, so a copy of a secret pasted into a config file without quotes decodes as whatever
	 * the parser sees in it, and a purely numeric or date-shaped value becomes another type carrying the same
	 * bytes, invisible to the string comparisons in the compile-time check and the scrubber. Refused up front,
	 * so every secret that exists is one the checks cover.
	 */
	public function testSecretsThatWouldNotStayStringsUnquotedAreRefused(): void
	{
		Assert::exception(function (): void {
			ContainerSecretsChecker::checkValues(['apiKey' => '1629074133397642']);
		}, RuntimeException::class, '%a%secrets.apiKey%a%without quotes%a%');

		Assert::exception(function (): void {
			ContainerSecretsChecker::checkValues(['apiKey' => '12345678.5']);
		}, RuntimeException::class, '%a%secrets.apiKey%a%without quotes%a%');

		Assert::exception(function (): void {
			ContainerSecretsChecker::checkValues(['apiKey' => '2026-08-11']);
		}, RuntimeException::class, '%a%secrets.apiKey%a%without quotes%a%');

		Assert::exception(function (): void {
			// decodes as an array holding the bracketless inside, which is the secret minus two characters
			ContainerSecretsChecker::checkValues(['apiKey' => '[abcdefgh]']);
		}, RuntimeException::class, '%a%secrets.apiKey%a%without quotes%a%');

		Assert::exception(function (): void {
			// decodes as the part before the comment, a piece of the secret big enough to matter
			ContainerSecretsChecker::checkValues(['apiKey' => 'abcdefgh # ijklmnop']);
		}, RuntimeException::class, '%a%secrets.apiKey%a%without quotes%a%');

		Assert::exception(function (): void {
			// base64 WITH the trailing padding: NEON reads `foo=` as a mapping with `foo` as its key, so a paste
			// puts the secret minus the padding into the container as a key, off by one `=` from what the exact
			// comparisons look for; the padding carries nothing, strip it or use hex
			ContainerSecretsChecker::checkValues(['apiKey' => 'c2VjcmV0dmFsdWU=']);
		}, RuntimeException::class, '%a%secrets.apiKey%a%without quotes%a%');

		Assert::exception(function (): void {
			// standalone this fails to decode, which would count as safe, but pasted after a config key it
			// splits into two entries carrying the pieces
			ContainerSecretsChecker::checkValues(['apiKey' => "abcdefgh\nother: ijklmnop"]);
		}, RuntimeException::class, '%a%secrets.apiKey%a%control characters%a%');

		Assert::exception(function (): void {
			// a leading @ is a service reference: the neon adapter escapes a quoted copy to @@, so the exact
			// comparison never sees the secret's bytes, and the resolver unescapes it only into the generated code
			ContainerSecretsChecker::checkValues(['apiKey' => '@abcdefgh']);
		}, RuntimeException::class, '%a%secrets.apiKey%a%config syntax%a%');

		Assert::exception(function (): void {
			// a % pair expands as a parameter, and the pasted copy throws Nette's own "Missing parameter"
			// with the secret's insides quoted, before any check here runs
			ContainerSecretsChecker::checkValues(['apiKey' => 'pre%mid%post']);
		}, RuntimeException::class, '%a%secrets.apiKey%a%config syntax%a%');

		Assert::noError(function (): void {
			// hex and unpadded base64 decode back to themselves, a mid-string @ isn't a reference, a single %
			// stays literal, and a value NEON can't parse at all is fine too: pasting that unquoted breaks
			// the config loudly instead of compiling quietly
			ContainerSecretsChecker::checkValues(['apiKey' => 'msee_cafecafe1629074133397642', 'unpadded' => 'c2VjcmV0dmFsdWU', 'atInside' => 'ab@cdefgh', 'onePercent' => '50%offabc', 'other' => "o'brien: {weird"]);
		});
	}


	public function testDynamicParametersAreNotCompiledIntoTheContainer(): void
	{
		$secret = 'probe-' . bin2hex(random_bytes(8)); // plain on purpose, this checks the raw bytes; escaped values are the refused test's subject

		Assert::false($this->secretInCompiledContainer($secret, dynamic: true), 'a secret passed as a dynamic parameter was written into the compiled container');
		Assert::true($this->secretInCompiledContainer($secret, dynamic: false), 'a static parameter was not found either, so this would pass however the container was built');
	}


	/**
	 * Every value under `parameters.secrets` in a committed config file has to stay empty, and it is every file
	 * because the compiled fallback is built from the merged config, not from parameters.neon alone: a value
	 * hot-fixed into common.neon or an extra-*.neon is compiled into the container with the two checks above
	 * still green. tests.neon is the exception, its fakes are the point, and the gitignored files are skipped
	 * because they aren't committed and a dev machine may legitimately have them.
	 */
	public function testCommittedConfigsDeclareTheSecretsWithoutValues(): void
	{
		$files = glob(__DIR__ . '/../../config/*.neon');
		assert(is_array($files));
		$declarationSeen = false;
		$filled = [];
		foreach ($files as $file) {
			$basename = basename($file);
			if ($basename === 'tests.neon' || $basename === 'local.neon' || str_starts_with($basename, 'remote')) {
				continue;
			}
			$config = Neon::decodeFile($file);
			if (!is_array($config) || !is_array($config['parameters'] ?? null) || !is_array($config['parameters']['secrets'] ?? null)) {
				continue;
			}
			$declarationSeen = $declarationSeen || $basename === 'parameters.neon';
			$filled = array_merge($filled, $this->filledLeaves($config['parameters']['secrets'], "{$basename}: secrets"));
		}
		Assert::true($declarationSeen, 'parameters.neon no longer declares the secrets shape, so nothing does');
		Assert::same([], $filled, 'these belong in config/secrets.neon, a value in a committed file is compiled into the container');
	}


	/**
	 * A `%secrets.…%` reference compiles to a runtime lookup, so referencing a path the shape doesn't declare
	 * compiles fine everywhere and fails only when the service is first built on the machine with the real
	 * secrets: renaming a secret and missing one reference is exactly that. tests.neon and the gitignored
	 * files are skipped like everywhere else here, they're not the committed shape.
	 */
	public function testCommittedConfigsReferenceOnlyDeclaredSecrets(): void
	{
		$config = Neon::decodeFile(__DIR__ . '/../../config/parameters.neon');
		assert(is_array($config) && is_array($config['parameters']) && is_array($config['parameters']['secrets']));
		$declared = $config['parameters']['secrets'];

		$files = glob(__DIR__ . '/../../config/*.neon');
		assert(is_array($files));
		$references = 0;
		$undeclared = [];
		foreach ($files as $file) {
			$basename = basename($file);
			if ($basename === 'tests.neon' || $basename === 'local.neon' || $basename === 'secrets.neon' || str_starts_with($basename, 'remote')) {
				continue;
			}
			Preg::matchAllStrictGroups('/%secrets\.([\w.]+)%/', FileSystem::read($file), $matches);
			foreach ($matches[1] as $referencePath) {
				$references++;
				$known = $declared;
				foreach (explode('.', $referencePath) as $segment) {
					if (!is_array($known) || !array_key_exists($segment, $known)) {
						$undeclared[] = "{$basename}: %secrets.{$referencePath}%";
						continue 2;
					}
					$known = $known[$segment];
				}
			}
		}
		Assert::true($references > 0, 'no %secrets.…% references found at all, so this scanned nothing');
		Assert::same([], $undeclared, 'these reference paths parameters.neon does not declare, so they would fail only at runtime, on the machine with the real secrets');
	}


	/**
	 * secrets.neon replaces the whole declared tree at runtime with no per-key fallback, so the shape in
	 * parameters.neon and the template people copy have to agree, or a machine ends up with a container that
	 * compiles everywhere except where it runs.
	 */
	public function testTemplateAndDeclaredShapeAgree(): void
	{
		$template = $this->asArray(Neon::decodeFile(__DIR__ . '/../../config/secrets.template.neon'));
		$config = Neon::decodeFile(__DIR__ . '/../../config/parameters.neon');
		assert(is_array($config) && is_array($config['parameters']) && is_array($config['parameters']['secrets']));

		Assert::same([], $this->shapeDifferences($config['parameters']['secrets'], $template, 'secrets'));
	}


	/**
	 * @return array<array-key, mixed>
	 */
	private function asArray(mixed $values): array
	{
		if (!is_array($values)) {
			throw new RuntimeException('An array expected, got ' . get_debug_type($values));
		}
		return $values;
	}


	/**
	 * The directory of the latest compile() call, the way to reach its files when the compile threw
	 */
	private function lastTempDir(): string
	{
		$tempDir = end($this->tempDirs);
		if ($tempDir === false) {
			throw new RuntimeException('Nothing compiled anything yet, call compile() first');
		}
		return $tempDir;
	}


	/**
	 * @param array<array-key, mixed> $values
	 * @return list<string> The paths of every non-empty leaf, so a failure names the secret, not just its key
	 */
	private function filledLeaves(array $values, string $path): array
	{
		$filled = [];
		foreach ($values as $key => $value) {
			$keyPath = "{$path}.{$key}";
			if (is_array($value)) {
				$filled = array_merge($filled, $this->filledLeaves($value, $keyPath));
			} elseif ($value !== null && $value !== '') {
				$filled[] = $keyPath;
			}
		}
		return $filled;
	}


	/**
	 * Compares key sets level by level, descending only where the declared side has keys of its own: below an
	 * empty declared leaf are the key ids, which legitimately differ between machines.
	 *
	 * @param array<array-key, mixed> $declared
	 * @param array<array-key, mixed> $template
	 * @return list<string>
	 */
	private function shapeDifferences(array $declared, array $template, string $path): array
	{
		$differences = [];
		foreach (array_keys(array_diff_key($declared, $template)) as $key) {
			$differences[] = "{$path}.{$key} is declared in parameters.neon but missing from secrets.template.neon";
		}
		foreach (array_keys(array_diff_key($template, $declared)) as $key) {
			$differences[] = "{$path}.{$key} is in secrets.template.neon but not declared in parameters.neon";
		}
		foreach ($declared as $key => $value) {
			if (!array_key_exists($key, $template)) {
				continue; // already reported above
			}
			$templateValue = $template[$key];
			if (is_array($value)) {
				if (!is_array($templateValue)) {
					$differences[] = "{$path}.{$key} is an array in parameters.neon but not in secrets.template.neon";
				} elseif ($value !== []) {
					$differences = array_merge($differences, $this->shapeDifferences($value, $templateValue, "{$path}.{$key}"));
				}
			} elseif (is_array($templateValue)) {
				$differences[] = "{$path}.{$key} is a single value in parameters.neon but an array in secrets.template.neon";
			}
		}
		return $differences;
	}


	/**
	 * @param string $key Marked, or it is dumped as an argument of this very method and the test measures its own harness
	 */
	private function keyInRenderedBlueScreen(#[SensitiveParameter] string $key, bool $dynamic): bool
	{
		// An active key id that doesn't exist, so the constructor throws the way a misconfigured key would
		[$container, $tempDir] = $this->compile(['encryptionKeys' => ['email' => ['dev1' => $key]]], $dynamic, [
			'factory' => 'Spaze\Encryption\SymmetricKeyEncryption',
			'arguments' => ['%secrets.encryptionKeys.email%', 'nope', 'msee'],
		]);

		$thrown = null;
		try {
			$container->getService('probe');
		} catch (Throwable $e) {
			$thrown = $e;
		}
		Assert::notNull($thrown, 'building the service should have failed on the unknown active key id');
		assert($thrown !== null); // Assert::notNull() doesn't narrow the type for phpstan and psalm

		$log = $tempDir . '/bluescreen.html';
		Assert::true($this->blueScreen->renderToFile($thrown, $log), 'Tracy refused to write the blue screen, it will not overwrite an existing file');
		$agentLog = $tempDir . '/bluescreen.md';
		Assert::true(is_file($agentLog), 'Tracy no longer writes the .md log, drop it here rather than silently checking less');
		// Both, because the two hide values differently: the .md redaction leans on the SensitiveParameterValue
		// entry in keysToHide, which is marked as removable once the fix lands in Tracy itself
		return str_contains(FileSystem::read($log), $key) || str_contains(FileSystem::read($agentLog), $key);
	}


	private function secretInCompiledContainer(#[SensitiveParameter] string $secret, bool $dynamic): bool
	{
		[, $tempDir] = $this->compile(['apiKey' => $secret], $dynamic, [
			'factory' => 'ArrayObject',
			'arguments' => [['key' => '%secrets.apiKey%']],
		]);

		$files = glob($tempDir . '/cache/nette.configurator/Container_*.php');
		Assert::type('array', $files, 'no compiled container to look at, so this proves nothing');
		assert(is_array($files));
		$found = false;
		foreach ($files as $file) {
			$found = $found || str_contains(FileSystem::read($file), $secret);
		}
		return $found;
	}


	/**
	 * A guarded compile that must refuse the config; returns what it threw. No Assert::exception() here:
	 * its closure would capture the secrets, and Tracy dumps captured variables, so the log checks in the
	 * tests would fail on the harness rather than on what they test.
	 *
	 * @param array<string, mixed> $secrets
	 * @param array<string, mixed> $service
	 * @param array<string, mixed> $extraConfig
	 */
	private function compileRefused(string $acceptedDescription, #[SensitiveParameter] array $secrets, array $service, #[SensitiveParameter] array $extraConfig = [], #[SensitiveParameter] ?string $extraConfigNeon = null): RuntimeException
	{
		$thrown = null;
		try {
			$this->compile($secrets, dynamic: true, guarded: true, service: $service, extraConfig: $extraConfig, extraConfigNeon: $extraConfigNeon);
		} catch (RuntimeException $e) {
			$thrown = $e;
		}
		Assert::notNull($thrown, $acceptedDescription);
		assert($thrown !== null); // Assert::notNull() doesn't narrow the type for phpstan and psalm
		return $thrown;
	}


	/**
	 * Renders the refusal's blue screen, the .md twin included, and checks the value stayed out of both.
	 * Callers pass only a tail of the value: the blue screen escapes characters differently in every context
	 * (&#039; in HTML, \u0027 in the dump JSON), so looking for a whole value with an apostrophe in it would
	 * pass whatever leaked.
	 */
	private function assertNoLeakInRenderedBlueScreen(RuntimeException $thrown, string $name, #[SensitiveParameter] string $valueTail): void
	{
		$log = $this->lastTempDir() . "/{$name}-bluescreen.html";
		Assert::true($this->blueScreen->renderToFile($thrown, $log), 'Tracy refused to write the blue screen');
		Assert::notContains($valueTail, FileSystem::read($log), 'the exception log leaked the value');
		Assert::notContains($valueTail, FileSystem::read($this->lastTempDir() . "/{$name}-bluescreen.md"), 'the agent log leaked the value');
	}


	/**
	 * @param array<string, mixed> $secrets
	 * @param array<string, mixed> $service Registered as `probe`, the one thing that reads the secret
	 * @param array<string, mixed> $extraConfig Merged in on top, the way another config file would be
	 * @param bool $guarded Registers ContainerSecretsChecker the way Bootstrap does; off for the tests that
	 *     deliberately compile a secret in to look at the result
	 * @return array{Container, string} The container and the temp directory it was compiled into
	 */
	private function compile(#[SensitiveParameter] array $secrets, bool $dynamic, array $service, #[SensitiveParameter] array $extraConfig = [], bool $guarded = false, #[SensitiveParameter] ?string $extraConfigNeon = null): array
	{
		$tempDir = sys_get_temp_dir() . '/compiled-container-secrets-test-' . bin2hex(random_bytes(8));
		$this->tempDirs[] = $tempDir;

		$configurator = new Configurator();
		$configurator->setDebugMode(false);
		$configurator->setTempDirectory($tempDir);
		if ($dynamic) {
			$configurator->addDynamicParameters(['secrets' => $secrets]);
		} else {
			$configurator->addStaticParameters(['secrets' => $secrets]);
		}
		$configurator->addConfig([
			'application' => ['scanDirs' => false], // or it finds this app's presenters and fails on their dependencies
			'services' => ['probe' => $service],
		]);
		if ($extraConfig !== []) {
			$configurator->addConfig($extraConfig);
		}
		if ($extraConfigNeon !== null) {
			// added as a FILE: a real config goes through the NeonAdapter, which rewrites values and keys
			// in ways an addConfig(array) never sees
			FileSystem::createDir($tempDir);
			FileSystem::write($tempDir . '/extra.neon', $extraConfigNeon);
			$configurator->addConfig($tempDir . '/extra.neon');
		}
		if ($guarded) {
			$configurator->onCompile[] = function (Configurator $configurator, Compiler $compiler) use ($secrets): void {
				$compiler->addExtension('containerSecretsChecker', new ContainerSecretsChecker($secrets));
			};
		}
		return [$configurator->createContainer(initialize: false), $tempDir];
	}

}

TestCaseRunner::run(CompiledContainerSecretsTest::class);
