<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Composer\Pcre\Preg;
use Nette\DI\CompilerExtension;
use Nette\Neon\Exception as NeonException;
use Nette\Neon\Neon;
use Override;
use ReflectionObject;
use RuntimeException;
use SensitiveParameter;
use SensitiveParameterValue;
use Tracy\BlueScreen;

/**
 * Refuses to compile a container whose configuration carries a secret. Everything a secret could ride into the
 * generated code on is some configuration value, so the merged config is checked instead of the generated text,
 * and it happens before a single line is generated: a refused config means no container file was written, and
 * every following boot recompiles and refuses again until the config is fixed.
 *
 * Bootstrap registers this from its onCompile hook, with the values currently loaded from config/secrets.neon.
 */
final class ContainerSecretsChecker extends CompilerExtension
{

	/**
	 * A shorter value can equal an ordinary configuration value by accident, and the check would then refuse
	 * a perfectly clean config, so short values are refused up front instead.
	 */
	private const int MIN_CHECKABLE_LENGTH = 8;


	/**
	 * @param array<array-key, mixed> $secrets
	 */
	public function __construct(
		#[SensitiveParameter] private readonly array $secrets,
	) {
	}


	/**
	 * Runs after every extension got its configuration and before any code is generated.
	 *
	 * Two rules: `parameters.secrets` must hold no value at all, whatever it is, because the config is compiled
	 * into the container as the dynamic parameter's fallback, and a stale value left there after a rotation
	 * equals nothing in secrets.neon while still being a secret. Everywhere else, no configuration value or key
	 * may equal a current secret, keys are emitted into the generated code as literals just like values: a whole
	 * value equal to one isn't a coincidence, it's a copy, while a mere substring somewhere would be, so only
	 * exact matches count.
	 *
	 * The refusals name paths, and a path can have a pasted secret as a segment, so every current secret is
	 * replaced in them before they reach the exception message; under `parameters.secrets` only the declared
	 * part of the path is named, the keys there can be stale secrets nothing can recognize.
	 */
	#[Override]
	public function beforeCompile(): void
	{
		$secretValues = [];
		foreach (self::scalarLeaves($this->secrets, 'secrets') as $path => $value) {
			if (is_string($value) && $value !== '') {
				$secretValues[$value] = $path;
			}
		}
		$hidden = array_fill_keys(array_keys($secretValues), '…');

		$filled = [];
		$unknownKeys = [];
		$copied = [];
		foreach ($this->compiler->getExtensions() as $name => $extension) {
			if ($extension === $this) {
				continue;
			}
			foreach (self::configLeaves($extension->getConfig(), $name) as $path => $value) {
				if ($path === 'parameters.secrets' || str_starts_with($path, 'parameters.secrets.')) {
					if ($value !== null && $value !== '') {
						$filled[] = $this->declaredSecretsPath($path);
					}
				} elseif (is_string($value) && isset($secretValues[$value])) {
					// Strings only: checkValues() refuses secrets that would decode as numbers or dates when
					// unquoted, so no value of another type can equal one
					$copied[] = "{$secretValues[$value]} (" . strtr($path, $hidden) . ')';
				}
			}
			foreach (self::configKeys($extension->getConfig(), $name) as [$holder, $key]) {
				if ($holder === 'parameters.secrets' || str_starts_with($holder, 'parameters.secrets.')) {
					// Keys under parameters.secrets are compiled into the container as the fallback structure
					// even with nothing under them, an empty array say, so every key has to be one the current
					// secrets declare: what isn't is either a secret nobody has put in secrets.neon yet or one
					// left there after a rotation. With no secrets loaded there's no shape to compare against,
					// and nothing current to protect either
					$holderPath = $this->declaredSecretsPath($holder);
					if (
						$this->secrets !== []
						&& !str_ends_with($holderPath, '…')
						&& str_ends_with($this->declaredSecretsPath("{$holder}.{$key}"), '…')
					) {
						$unknownKeys[] = $holderPath; // the topmost unknown key only, everything below it is unknown by definition
					}
				} elseif (isset($secretValues[$key])) {
					$copied[] = "{$secretValues[$key]} (a key under " . strtr($holder, $hidden) . ')';
				}
			}
		}
		if ($filled !== []) {
			throw new RuntimeException('Values of ' . implode(', ', array_unique($filled)) . ' are defined statically in the config files and would be compiled into the container as the fallback for the dynamic parameter; they belong only in config/secrets.neon');
		}
		if ($unknownKeys !== []) {
			throw new RuntimeException('The config files declare keys under ' . implode(', ', array_unique($unknownKeys)) . " that config/secrets.neon does not have, and that file replaces the whole tree: add them there, or delete them from the config files if they are left over after a rotation; the names aren't printed because a key can be a secret itself, compare the two files to find them");
		}
		if ($copied !== []) {
			throw new RuntimeException('Values of ' . implode(', ', $copied) . ' are written into the configuration statically and would be compiled into the container; they belong only in config/secrets.neon');
		}
	}


	/**
	 * Makes the blue screen hide every value equal to a current secret, wherever it appears. A failed compile
	 * puts the whole configuration in the trace's frame arguments: the `Configurator` with its dynamic parameters,
	 * closure captures, the raw config arrays, under keys no `keysToHide` list can all name, so hiding goes by
	 * value. An array holding a secret as a KEY is hidden whole: Tracy always renders key names, so hiding
	 * the key's value wouldn't hide the key. Anything keyed `secrets` is hidden whatever it holds, because
	 * a STALE value under `parameters.secrets` equals no current secret, and its carrier in the dumped config
	 * arrays is always keyed that. Strings are all that's compared, checkValues() refuses secrets that would
	 * decode as something else when unquoted. The closure keeps the values wrapped in `SensitiveParameterValue`,
	 * which Tracy redacts natively, so dumping the closure itself shows no values, and there is no derived
	 * list, hashes say, to attack offline if a dump ever gets out.
	 *
	 * @param array<array-key, mixed> $secrets
	 */
	public static function hideSecrets(BlueScreen $blueScreen, #[SensitiveParameter] array $secrets): void
	{
		$secretValues = [];
		foreach (self::scalarLeaves($secrets, 'secrets') as $value) {
			if (is_string($value) && $value !== '') {
				$secretValues[] = new SensitiveParameterValue($value);
			}
		}
		$blueScreen->scrubber = static function (string $key, mixed $value = null) use ($secretValues): bool {
			if ($key === 'secrets') {
				return true;
			}
			if (is_string($value)) {
				$candidates = [$value];
			} elseif (is_array($value)) {
				$candidates = array_filter(array_keys($value), is_string(...));
			} else {
				return false;
			}
			foreach ($candidates as $candidate) {
				foreach ($secretValues as $secretValue) {
					if ($candidate === $secretValue->getValue()) {
						return true;
					}
				}
			}
			return false;
		};
	}


	/**
	 * @param array<array-key, mixed> $secrets
	 * @throws RuntimeException
	 */
	public static function checkValues(#[SensitiveParameter] array $secrets): void
	{
		$notStrings = [];
		$tooShort = [];
		$controlCharacters = [];
		$activeConfigSyntax = [];
		$wrongWhenUnquoted = [];
		foreach (self::scalarLeaves($secrets, 'secrets') as $path => $value) {
			if ($value === null || $value === '') {
				continue; // not set, same meaning the declared shape gives an empty value
			} elseif (!is_string($value)) {
				$notStrings[] = $path;
			} elseif (strlen($value) < self::MIN_CHECKABLE_LENGTH) {
				$tooShort[] = $path;
			} elseif (Preg::isMatch('/[\x00-\x1F\x7F]/', $value)) {
				// A value with a newline in it decodes on its own terms only: standalone it's a parse error,
				// which decodesDifferently() treats as safe, while pasted after a config key it splits into
				// several entries carrying the pieces
				$controlCharacters[] = $path;
			} elseif (str_starts_with($value, '@') || substr_count($value, '%') >= 2) {
				// Nette config syntax, not NEON: a leading @ is a service reference, which the neon adapter
				// escapes to @@ so an exact comparison never sees the secret's bytes, and the resolver unescapes
				// it only into the generated code; a % pair expands as a parameter, throwing Nette's own error
				// with the secret's insides quoted before any check here runs. A single % stays literal
				$activeConfigSyntax[] = $path;
			} elseif (self::decodesDifferently($value)) {
				$wrongWhenUnquoted[] = $path;
			}
		}
		if ($notStrings !== []) {
			throw new RuntimeException('Values of ' . implode(', ', $notStrings) . ' are not strings; secrets are strings, a number would lose leading zeros and the compiled-container check only compares strings');
		}
		if ($tooShort !== []) {
			throw new RuntimeException('Values of ' . implode(', ', $tooShort) . ' are shorter than ' . self::MIN_CHECKABLE_LENGTH . ' characters, and a value that short can equal an ordinary configuration value by accident, which the compiled-container check would then refuse; use longer values');
		}
		if ($controlCharacters !== []) {
			throw new RuntimeException('Values of ' . implode(', ', $controlCharacters) . ' contain control characters, a newline say, so a copy pasted into a config file splits into several entries carrying the pieces, which the whole-string comparisons can\'t see; use single-line values');
		}
		if ($activeConfigSyntax !== []) {
			throw new RuntimeException('Values of ' . implode(', ', $activeConfigSyntax) . ' read as Nette config syntax: a leading @ is a service reference and a % pair expands as a parameter, so a copy pasted into a config file changes shape before the compiled-container check can see it, or makes Nette\'s own error message quote it; use values that don\'t start with @ and have at most one %');
		}
		if ($wrongWhenUnquoted !== []) {
			throw new RuntimeException('Values of ' . implode(', ', $wrongWhenUnquoted) . ' would decode as something else (a number, a date, an array, a piece of themselves) if copied into a config file without quotes, NEON is quote-optional, and the compiled-container check and the blue screen scrubber compare whole strings only, so such a copy would slip through both; use values that decode back to exactly themselves, hex and base64 without the trailing padding do');
		}
	}


	/**
	 * NEON is quote-optional, so a copy of a secret pasted into a config file without quotes decodes as whatever
	 * the parser sees in it: `12345678` as an integer, `2026-08-11` as a date object, `[whatever]` as an array
	 * holding the bracketless inside, `whatever # tail` as the part before the comment. Each of those is compiled
	 * into the container carrying the secret's bytes, or a large piece of them, in a shape the string comparisons
	 * in beforeCompile() and the scrubber can't see, so the only safe values are the ones that decode back to
	 * exactly themselves, and everything else is refused up front. A value that fails to decode at all is fine
	 * too: pasting it unquoted breaks the config loudly instead of compiling quietly.
	 */
	private static function decodesDifferently(#[SensitiveParameter] string $value): bool
	{
		try {
			$decoded = Neon::decode($value);
		} catch (NeonException) {
			return false;
		}
		return $decoded !== $value;
	}


	/**
	 * The path under `parameters.secrets` cut down to what the current secrets declare: everything under there
	 * is refused whatever it is, so its keys can be anything, a stale secret from before a rotation too, and
	 * a refusal can only safely name the declared part.
	 */
	private function declaredSecretsPath(string $path): string
	{
		$declared = 'secrets';
		if ($path === 'parameters.secrets') {
			return $declared;
		}
		$known = $this->secrets;
		foreach (explode('.', substr($path, strlen('parameters.secrets.'))) as $segment) {
			if (!is_array($known) || !array_key_exists($segment, $known)) {
				return "{$declared}.…";
			}
			$declared .= ".{$segment}";
			$known = $known[$segment];
		}
		return $declared;
	}


	/**
	 * @param array<array-key, mixed> $values
	 * @return array<string, mixed> Path => leaf, everything that isn't an array, so checkValues() can refuse
	 *     what doesn't belong rather than this quietly leaving it out
	 */
	private static function scalarLeaves(#[SensitiveParameter] array $values, string $path): array
	{
		$leaves = [];
		foreach ($values as $key => $value) {
			$keyPath = "{$path}.{$key}";
			if (is_array($value)) {
				$leaves = array_merge($leaves, self::scalarLeaves($value, $keyPath));
			} else {
				$leaves[$keyPath] = $value;
			}
		}
		return $leaves;
	}


	/**
	 * Like scalarLeaves(), but for the shapes extension configs come in: a schema-processed config is an object,
	 * a service definition holds its arguments in Statement objects, so objects are walked by their properties
	 * too, the private ones included: a Statement keeps its entity, the class name it compiles into a `new`,
	 * in one.
	 *
	 * @return array<string, mixed>
	 */
	private static function configLeaves(mixed $config, string $path): array
	{
		$leaves = [];
		if (is_array($config)) {
			foreach ($config as $key => $value) {
				$leaves = array_merge($leaves, self::configLeaves($value, "{$path}.{$key}"));
			}
		} elseif (is_object($config)) {
			foreach (self::configItems($config) as $key => $value) {
				$leaves = array_merge($leaves, self::configLeaves($value, "{$path}.{$key}"));
			}
		} else {
			$leaves[$path] = $config;
		}
		return $leaves;
	}


	/**
	 * @return array<string, mixed>
	 */
	private static function configItems(object $config): array
	{
		$items = [];
		foreach ((new ReflectionObject($config))->getProperties() as $property) {
			if (!$property->isStatic() && $property->isInitialized($config)) {
				$items[$property->getName()] = $property->getValue($config);
			}
		}
		return $items;
	}


	/**
	 * Every key from every level of a config, each with the path of the structure holding it, which is what
	 * a refusal can safely name: the key itself may be the secret. Keys come out as strings, because PHP turns
	 * a digit-only key into an integer on its own, and a stale numeric key under `parameters.secrets` still
	 * compiles into the fallback structure.
	 *
	 * @return list<array{string, string}>
	 */
	private static function configKeys(mixed $config, string $path): array
	{
		$keys = [];
		$items = is_array($config) ? $config : (is_object($config) ? self::configItems($config) : []);
		foreach ($items as $key => $value) {
			$keys[] = [$path, (string)$key];
			$keys = array_merge($keys, self::configKeys($value, "{$path}.{$key}"));
		}
		return $keys;
	}

}
