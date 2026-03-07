<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Talks;

use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Presentation\Www\Talks\Exceptions\DeprecatedEmbedSlideInUrlException;
use MichalSpacekCz\Presentation\Www\Talks\Exceptions\IncorrectSlideAliasInUrlException;
use MichalSpacekCz\Presentation\Www\Talks\Exceptions\TalkExistsInOtherLocaleException;
use MichalSpacekCz\Talks\Exceptions\TalkDoesNotExistException;
use MichalSpacekCz\Talks\Exceptions\TalkSlideAliasDoesNotExistException;
use MichalSpacekCz\Talks\Exceptions\TalkSlidesNotPublishedException;
use MichalSpacekCz\Talks\TalkLocaleUrls;
use MichalSpacekCz\Talks\TalksList;
use MichalSpacekCz\Talks\TalksListFactory;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\IResponse;
use Override;

final class TalksPresenter extends BasePresenter
{

	/** @var array<string, array{name: string}> */
	private array $localeLinkParams = [];


	public function __construct(
		private readonly TalkLocaleUrls $talkLocaleUrls,
		private readonly TalksListFactory $talksListFactory,
		private readonly LocaleLinkGenerator $localeLinkGenerator,
		private readonly TalksDefaultTemplateParametersFactory $defaultTemplateParametersFactory,
		private readonly TalksTalkTemplateParametersFactory $talkTemplateParametersFactory,
	) {
		parent::__construct();
	}


	/**
	 * @throws ContentTypeException
	 */
	public function renderDefault(): void
	{
		$this->template->setParameters($this->defaultTemplateParametersFactory->create());
	}


	/**
	 * @param string $name
	 * @param string|null $slide
	 * @throws InvalidLinkException
	 * @throws ContentTypeException
	 */
	public function actionTalk(string $name, ?string $slide = null): void
	{
		try {
			$templateParameters = $this->talkTemplateParametersFactory->create($name, $slide);
		} catch (TalkExistsInOtherLocaleException $e) {
			$links = $this->localeLinkGenerator->links(parent::getLocaleLinkAction(), parent::getLocaleLinkParams());
			$this->redirectUrl($links[$e->locale]->getUrl(), IResponse::S301_MovedPermanently);
		} catch (TalkDoesNotExistException | TalkSlideAliasDoesNotExistException | TalkSlidesNotPublishedException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		} catch (IncorrectSlideAliasInUrlException $e) {
			$this->redirectPermanent('this', ['slide' => $e->correctAlias]);
		} catch (DeprecatedEmbedSlideInUrlException) {
			$this->redirectPermanent('this', ['slide' => null]);
		}
		$this->localeLinkParams = $this->talkLocaleUrls->getLinkParams($templateParameters->talk);
		$this->template->setParameters($templateParameters);
	}


	#[Override]
	protected function getLocaleLinkAction(): string
	{
		return (count($this->localeLinkParams) > 1 ? parent::getLocaleLinkAction() : 'Www:Talks:');
	}


	/**
	 * @return array<string, array{name: string}>
	 */
	#[Override]
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkParams;
	}


	protected function createComponentTalksList(): TalksList
	{
		return $this->talksListFactory->create();
	}

}
