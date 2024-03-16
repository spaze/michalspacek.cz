<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\EasterEgg\PhpInfoCookieSanitization;
use MichalSpacekCz\Http\Cookies\CookieName;
use MichalSpacekCz\Test\Http\NullSession;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class SanitizedPhpInfoTest extends TestCase
{

	public function __construct(
		private readonly SanitizedPhpInfo $sanitizedPhpInfo,
		private readonly NullSession $session,
		private readonly Request $httpRequest,
	) {
	}


	public function testGetHtml(): void
	{
		$sessionId = 'foo31337';
		$sessionName = 'PHPSESSID';
		$returningUserValue = 'yolo';
		$permanentLoginValue = 'zomg';

		$this->session->setId($sessionId);
		$cookie = [
			$sessionName => $sessionId,
			CookieName::ReturningUser->value => $returningUserValue,
			CookieName::PermanentLogin->value => $permanentLoginValue,
		];

		$httpCookie = '';
		foreach ($cookie as $name => $value) {
			$httpCookie .= sprintf('%s=%s;', $name, $value);
		}
		ServerEnv::setString('HTTP_COOKIE', $httpCookie);
		$_COOKIE = $cookie;

		$this->httpRequest->setCookie(CookieName::ReturningUser->value, $returningUserValue);
		$this->httpRequest->setCookie(CookieName::PermanentLogin->value, $permanentLoginValue);

		$html = $this->sanitizedPhpInfo->getHtml();
		Assert::contains('phpinfo', $html);
		Assert::notContains($sessionId, $html);
		Assert::contains(PhpInfoCookieSanitization::SESSION_ID, $html);
		Assert::notContains($returningUserValue, $html);
		Assert::notContains($permanentLoginValue, $html);
		Assert::contains(PhpInfoCookieSanitization::COOKIE_VALUE, $html);
	}

}

TestCaseRunner::run(SanitizedPhpInfoTest::class);
