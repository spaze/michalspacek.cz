<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\Application\Theme;
use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Formatter\TexyPhraseHandler;
use MichalSpacekCz\Post\LocaleUrls;
use MichalSpacekCz\Test\Application\LocaleLinkGenerator;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\ServicesTrait;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Locales;
use MichalSpacekCz\Training\Prices;
use MichalSpacekCz\Training\Statuses;
use Nette\Application\Application;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\ApplicationLatte\TemplateFactory as NetteTemplateFactory;
use Nette\Caching\Storages\DevNullStorage;
use Spaze\NonceGenerator\Generator;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TemplateFactoryTest extends TestCase
{

	use ServicesTrait;


	private Theme $theme;
	private DevNullStorage $cacheStorage;
	private NoOpTranslator $translator;
	private Database $database;
	private Statuses $trainingStatuses;
	private Prices $prices;
	private DateTimeFormatter $dateTimeFormatter;
	private Dates $trainingDates;
	private Application $application;
	private Locales $trainingLocales;
	private LocaleLinkGenerator $localeLinkGenerator;
	private LocaleUrls $blogPostLocaleUrls;
	private TexyPhraseHandler $phraseHandler;
	private TexyFormatter $texyFormatter;
	private Filters $filters;
	private LatteFactory $latteFactory;
	private NetteTemplateFactory $netteTemplateFactory;
	private Generator $nonceGenerator;
	private TemplateFactory $templateFactory;



	protected function setUp()
	{
		$this->theme = $this->getTheme();
		$this->cacheStorage = $this->getCacheStorage();
		$this->translator = $this->getTranslator();
		$this->translator->setDefaultLocale('cs_CZ');
		$this->database = $this->getDatabase();
		$this->trainingStatuses = new Statuses($this->database);
		$this->prices = new Prices(0.21);
		$this->dateTimeFormatter = new DateTimeFormatter($this->translator->getDefaultLocale());
		$this->trainingDates = new Dates($this->database, $this->trainingStatuses, $this->prices, $this->dateTimeFormatter, $this->translator);
		$this->application = $this->getApplication();
		$this->trainingLocales = $this->getLocales();
		$this->localeLinkGenerator = $this->getLocaleLinkGenerator();
		$this->blogPostLocaleUrls = $this->getBlogPostLocaleUrls();
		$this->phraseHandler = new TexyPhraseHandler($this->application, $this->trainingLocales, $this->localeLinkGenerator, $this->blogPostLocaleUrls, $this->translator);
		$this->texyFormatter = new TexyFormatter($this->cacheStorage, $this->translator, $this->trainingDates, $this->prices, $this->dateTimeFormatter, $this->phraseHandler, '/', 'i', '/var/www');
		$this->filters = new Filters($this->texyFormatter, $this->dateTimeFormatter);
		$this->latteFactory = $this->getLatteFactory();
		$this->netteTemplateFactory = new NetteTemplateFactory($this->latteFactory);
		$this->nonceGenerator = $this->getNonceGenerator();
		$this->templateFactory = new TemplateFactory($this->theme, $this->filters, $this->translator, $this->netteTemplateFactory, $this->nonceGenerator);
	}


	public function testCreateTemplate(): void
	{
		$template = $this->templateFactory->createTemplate();
		$providers = $template->getLatte()->getProviders();
		Assert::hasKey('uiNonce', $providers);
		Assert::same($this->nonceGenerator->getNonce(), $providers['uiNonce']);
	}

}

(new TemplateFactoryTest())->run();
