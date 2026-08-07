<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\User\UserAccounts;
use Nette\Database\DriverException;
use Override;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;
use Spaze\Encryption\SymmetricKeyEncryption;
use Tester\Assert;
use Tester\FileMock;
use Tester\TestCase;
use Throwable;
use Tracy\BlueScreen;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';

/**
 * Tracy writes every stack frame's arguments into the exception log, and the encryption library hands both the key
 * and the not-yet-encrypted value around as objects, so anything that throws mid-encryption puts them in the log.
 * The `tracy: keysToHide` setting in common.neon is what stops it.
 *
 * @testCase
 */
final class ExceptionLogSecretsTest extends TestCase
{

	private const string HTML_LOG = 'mock://bluescreen.html';
	private const string MD_LOG = 'mock://bluescreen.md'; // Tracy writes this one alongside, with the .html suffix replaced


	/**
	 * The service is the same object Debugger::getBlueScreen() returns, but asking for it is what makes
	 * TestCaseRunner build a container at all, and it's the container that applies the `tracy:` config; without
	 * one these tests would run against Tracy's defaults and pass without the setting they exist to check
	 */
	public function __construct(
		private readonly BlueScreen $blueScreen,
		private readonly Database $database,
		private readonly UserAccounts $userAccounts,
		private readonly TrainingApplicationStorage $trainingApplicationStorage,
	) {
		FileMock::register(); // can't use FileMock::create(), it creates the file and Tracy's fopen(..., 'x') would fail
	}


	#[Override]
	protected function tearDown(): void
	{
		FileMock::$files = [];
		$this->database->reset();
	}


	public function testKeyIsNotLoggedWhenDecryptionFails(): void
	{
		$keyMaterial = self::keyMaterial();
		$key = new EncryptionKey(new HiddenString($keyMaterial));
		$cipherText = Crypto::encrypt(new HiddenString('whatever'), $key);

		$thrown = null;
		try {
			Crypto::decrypt(substr($cipherText, 0, -4) . 'AAAA', $key); // a stored value that no longer authenticates
		} catch (Throwable $e) {
			$thrown = $e;
		}

		foreach ($this->logAndReadBack($thrown, 'Decrypting a corrupted value should have failed') as $extension => $contents) {
			Assert::notContains($keyMaterial, $contents, "the encryption key leaked into the {$extension} log");
		}
	}


	public function testKeyAndPlaintextAreNotLoggedWhenEncryptionFails(): void
	{
		$keyMaterial = self::keyMaterial();
		$key = new EncryptionKey(new HiddenString($keyMaterial));
		$email = bin2hex(random_bytes(8)) . '@example.com';

		$thrown = null;
		try {
			// Any failure inside encryption will do, this one is just easy to trigger; the plaintext is on the stack either way
			Crypto::encrypt(new HiddenString($email), $key, 'not-a-real-encoding');
		} catch (Throwable $e) {
			$thrown = $e;
		}

		foreach ($this->logAndReadBack($thrown, 'Encrypting with an unknown encoding should have failed') as $extension => $contents) {
			Assert::notContains($email, $contents, "the plaintext leaked into the {$extension} log");
			Assert::notContains($keyMaterial, $contents, "the encryption key leaked into the {$extension} log");
		}
	}


	/**
	 * The keys reach the encryption service as one `#[SensitiveParameter]` array, and it rejects a malformed one by
	 * throwing, so a single bad key in local.neon would otherwise log every good key next to it
	 */
	public function testKeysAreNotLoggedWhenTheEncryptionServiceRejectsOne(): void
	{
		$goodKey = bin2hex(random_bytes(32));

		$thrown = null;
		try {
			new SymmetricKeyEncryption(['live' => 'mstest_' . $goodKey, 'broken' => 'nope_' . bin2hex(random_bytes(32))], 'live', 'mstest');
		} catch (Throwable $e) {
			$thrown = $e;
		}

		foreach ($this->logAndReadBack($thrown, 'A key with the wrong prefix should have been rejected') as $extension => $contents) {
			Assert::notContains($goodKey, $contents, "a valid key leaked into the {$extension} log because another one was malformed");
		}
	}


	/**
	 * The applicant's name travels beside the email through the same writes, and identifies them more directly
	 * than the address does, so it is marked and checked the same way.
	 */
	public function testAttendeeNameIsNotLoggedWhenTheWriteFails(): void
	{
		$attendeeName = 'Probe ' . bin2hex(random_bytes(6));
		$email = bin2hex(random_bytes(8)) . '@example.com';
		// The status id is read before anything is written, so failing the read puts both parameters on the stack
		$this->database->willThrowOnRead(fn(): DriverException => new DriverException('The database fell over'));

		$thrown = null;
		try {
			$this->trainingApplicationStorage->addPreliminaryInvitation(1, $attendeeName, $email);
		} catch (Throwable $e) {
			$thrown = $e;
		}

		foreach ($this->logAndReadBack($thrown, 'Adding a preliminary invitation should have failed') as $extension => $contents) {
			Assert::notContains($attendeeName, $contents, "the attendee name leaked into the {$extension} log");
			Assert::notContains($email, $contents, "the email leaked into the {$extension} log");
		}
	}


	/**
	 * Unlike the key and the HiddenString above, an email arrives as a plain string parameter, which `keysToHide`
	 * can't help with: the name is `$email` and the value is just a string. `#[SensitiveParameter]` is what hides it.
	 */
	public function testEmailIsNotLoggedWhenTheWriteFails(): void
	{
		$email = bin2hex(random_bytes(8)) . '@example.com';
		// Built when thrown, not here: an exception created up front captures its trace here, without the frame being tested
		$this->database->willThrow(fn(): DriverException => new DriverException('The database fell over'));

		$thrown = null;
		try {
			$this->userAccounts->setNotificationEmail(42, $email);
		} catch (Throwable $e) {
			$thrown = $e;
		}

		foreach ($this->logAndReadBack($thrown, 'Writing the notification email should have failed') as $extension => $contents) {
			Assert::notContains($email, $contents, "the email leaked into the {$extension} log");
		}
	}


	/**
	 * The attribute only covers the value while it is a stack frame's argument. The blue screen also dumps `$_POST`
	 * on its own, so a form submission that errors would log what was typed into it regardless, which the `email`
	 * entry in `keysToHide` is what stops. That section renders only on the web (`section-http.phtml` returns early
	 * under CLI), so what is checked here is the dumper it hands each key and value to.
	 */
	public function testEmailIsHiddenWhereverItIsDumpedByName(): void
	{
		$email = bin2hex(random_bytes(8)) . '@example.com';

		$dumper = $this->blueScreen->getAgentDumper(); // the section dumps each entry as $dump($value, $key)

		Assert::notContains($email, $dumper($email, 'email'), 'an email dumped under an `email` key leaked');
		Assert::contains($email, $dumper($email, 'note'), 'the same value under another key vanished too, so this passes whatever the config says');
	}


	/**
	 * Printable key bytes, because Tracy renders binary as \xNN escapes and searching a log for the raw bytes of a
	 * random key would find nothing whether it leaked or not. Hex of half the bytes is the 32 characters Halite wants
	 */
	private static function keyMaterial(): string
	{
		return bin2hex(random_bytes(16));
	}


	/**
	 * @param Throwable|null $thrown Null when the call unexpectedly succeeded. Checked here because inside the
	 *     caller's try block Assert's own exception would be caught, and the test would pass on its own failure
	 * @return array<string, string> Log file contents, keyed by extension. Tracy writes an .html blue screen plus an
	 *     .md rendering of the same exception, and the two hide values differently, so both are checked
	 */
	private function logAndReadBack(?Throwable $thrown, string $shouldHaveFailed): array
	{
		Assert::notNull($thrown, $shouldHaveFailed);
		assert($thrown !== null); // Assert::notNull() doesn't narrow the type for phpstan and psalm
		unset(FileMock::$files[self::HTML_LOG], FileMock::$files[self::MD_LOG]); // Tracy won't write over a file that exists
		Assert::true($this->blueScreen->renderToFile($thrown, self::HTML_LOG));
		Assert::hasKey(self::MD_LOG, FileMock::$files, 'Tracy no longer writes the .md log, drop it here rather than silently checking less');
		$logged = [
			'html' => FileMock::$files[self::HTML_LOG],
			'md' => FileMock::$files[self::MD_LOG],
		];
		foreach ($logged as $extension => $contents) {
			// Absence of a secret proves nothing if the arguments weren't dumped at all, which is what stops these
			// tests from passing for the wrong reason if Tracy or the library ever stops putting them in the trace.
			// Backslashes go because the .md escapes the asterisks Tracy marks a hidden value with
			Assert::contains(Dumper::HIDDEN_VALUE, str_replace('\\', '', $contents), "nothing was hidden in the {$extension} log, so it can't show these secrets are being redacted");
		}
		return $logged;
	}

}

TestCaseRunner::run(ExceptionLogSecretsTest::class);
