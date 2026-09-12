<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\TestCaseRunner;
use ReflectionClass;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotParseHostnameException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtConnectedToWrongIpAddressException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressInvalidException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressNotPublicException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoHttpCodeException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoLocationHeaderException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtTooManyRedirectsException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlUnsupportedSchemeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorStoredFailureAllowListTest extends TestCase
{

	/**
	 * Failures the validator keeps under the host's name, because each describes that host: it did not resolve, it
	 * answered with nothing usable, it sent us somewhere we will not follow.
	 */
	private const array STORED_AS_THE_HOSTS_ANSWER = [
		SecurityTxtCannotOpenUrlException::class,
		SecurityTxtCannotParseHostnameException::class,
		SecurityTxtConnectedToWrongIpAddressException::class,
		SecurityTxtHostIpAddressInvalidException::class,
		SecurityTxtHostIpAddressNotFoundException::class,
		SecurityTxtHostIpAddressNotPublicException::class,
		SecurityTxtHostNotFoundException::class,
		SecurityTxtNoHttpCodeException::class,
		SecurityTxtNoLocationHeaderException::class,
		SecurityTxtNotFoundException::class,
		SecurityTxtTooManyRedirectsException::class,
		SecurityTxtUrlNotFoundException::class,
		SecurityTxtUrlUnsupportedSchemeException::class,
	];


	/**
	 * Whether a failure is the host's to own is a judgement, and a library upgrade bringing a new one would otherwise
	 * make that judgement by default, in whichever direction the code happened to be written. This fails instead, so
	 * the new one gets decided rather than inherited.
	 */
	public function testEveryFetcherFailureIsEitherStoredOrDeliberatelyNot(): void
	{
		$ours = new ReflectionClass(SecurityTxtValidator::class)->getConstant('NOT_THE_HOSTS_ANSWER');
		assert(is_array($ours)); // false when the constant has been renamed, which this test would then be silent about
		$decided = array_merge(self::STORED_AS_THE_HOSTS_ANSWER, $ours);
		sort($decided);
		$found = $this->getFetcherExceptions();
		Assert::same($found, $decided, 'A fetcher exception is missing from one of the two lists, or names a class that no longer exists');
	}


	/**
	 * @return list<string>
	 */
	private function getFetcherExceptions(): array
	{
		$found = [];
		$files = glob(__DIR__ . '/../../vendor/spaze/security-txt/src/Fetcher/Exceptions/*.php');
		if ($files === false) {
			throw new ShouldNotHappenException('Cannot read the fetcher exceptions directory');
		}
		Assert::notSame([], $files); // a path that matches nothing would make this test pass by finding nothing
		foreach ($files as $file) {
			$class = 'Spaze\SecurityTxt\Fetcher\Exceptions\\' . basename($file, '.php');
			if (!class_exists($class)) {
				continue;
			}
			$reflection = new ReflectionClass($class);
			if (!$reflection->isAbstract() && $reflection->isSubclassOf(SecurityTxtFetcherException::class)) {
				$found[] = $class;
			}
		}
		sort($found);
		return $found;
	}

}

TestCaseRunner::run(SecurityTxtValidatorStoredFailureAllowListTest::class);
