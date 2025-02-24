<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use Contributte\Translation\Translator;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\Photo;

final class PhotoPresenter extends BasePresenter
{

	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$photos = [
			new Photo(
				$this->translator->translate('messages.photo.trademark.header'),
				'michalspacek-trademark-400x268.jpg',
				$this->texyFormatter->translate('messages.photo.trademark.desc', ['https://twitter.com/spazef0rze', 'https://www.facebook.com/spaze', 'https://about.me/lukashudecek']),
				[
					'400×268' => 'michalspacek-trademark-400x268.jpg',
					'800×536' => 'michalspacek-trademark-800x536.jpg',
					'1600×1071' => 'michalspacek-trademark-1600x1071.jpg',
				],
			),
			new Photo(
				$this->translator->translate('messages.photo.lecturer.header'),
				'michalspacek-codecamp2015-400x268.jpg',
				$this->texyFormatter->translate('messages.photo.lecturer.desc', ['https://galerie.fotohavlin.cz/']),
				[
					'400×268' => 'michalspacek-codecamp2015-400x268.jpg',
					'800×536' => 'michalspacek-codecamp2015-800x536.jpg',
					'1600×1071' => 'michalspacek-codecamp2015-1600x1071.jpg',
				],
			),
			new Photo(
				$this->translator->translate('messages.photo.office.header'),
				'michalspacek-serious-400x268.jpg',
				$this->translator->translate('messages.photo.office.desc'),
				[
					'400×268' => 'michalspacek-serious-400x268.jpg',
					'400×400' => 'michalspacek-serious-400x400.jpg',
				],
			),
			new Photo(
				$this->translator->translate('messages.photo.talk.header'),
				'michalspacek-webtop100-400x268.jpg',
				$this->texyFormatter->translate('messages.photo.talk.desc', ['link:Www:Talks:talk technicke-chyby-vas-pripravi-o-penize-webtop100']),
				[
					'400×268' => 'michalspacek-webtop100-400x268.jpg',
					'720×480' => 'michalspacek-webtop100-720x480.jpg',
				],
			),
		];
		$this->template->photos = $photos;
		$this->template->pageTitle = $this->translator->translate('messages.title.photo');
	}

}
