<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Photo;

use Contributte\Translation\Translator;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\Photo;
use MichalSpacekCz\Media\PhotoFile;
use MichalSpacekCz\Presentation\Www\BasePresenter;

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
				new PhotoFile('michalspacek-trademark-400x268.jpg', 400, 268),
				$this->texyFormatter->translatePossiblyUnsafeHtml('messages.photo.trademark.desc', ['https://twitter.com/spazef0rze', 'https://www.facebook.com/spaze', 'https://about.me/lukashudecek']),
				[
					new PhotoFile('michalspacek-trademark-400x268.jpg', 400, 268),
					new PhotoFile('michalspacek-trademark-800x536.jpg', 800, 536),
					new PhotoFile('michalspacek-trademark-1600x1071.jpg', 1600, 1071),
				],
			),
			new Photo(
				$this->translator->translate('messages.photo.linuxdays2024.header'),
				new PhotoFile('michalspacek-linuxdays2024-400x300.jpg', 400, 300),
				$this->texyFormatter->translatePossiblyUnsafeHtml('messages.photo.linuxdays2024.desc', ['talk:zadni-vratka-pro-zviratka-linuxdays', 'https://www.root.cz/clanky/postkvantova-kryptografie-linux-na-macbooku-sobota-na-linuxdays/']),
				[
					new PhotoFile('michalspacek-linuxdays2024-400x300.jpg', 400, 300),
					new PhotoFile('michalspacek-linuxdays2024-800x600.jpg', 800, 600),
					new PhotoFile('michalspacek-linuxdays2024-1600x1200.jpg', 1600, 1200),
				],
			),
			new Photo(
				$this->translator->translate('messages.photo.linuxdays2026.header'),
				new PhotoFile('michalspacek-linuxdays2026-400x300.jpg', 400, 300),
				$this->texyFormatter->translatePossiblyUnsafeHtml('messages.photo.linuxdays2026.desc', ['https://www.root.cz/clanky/ai-promenuje-software-bezpecnost-i-svet-kolem-nas-zapisky-z-linuxdays/']),
				[
					new PhotoFile('michalspacek-linuxdays2026-400x300.jpg', 400, 300),
					new PhotoFile('michalspacek-linuxdays2026-800x600.jpg', 800, 600),
					new PhotoFile('michalspacek-linuxdays2026-1600x1200.jpg', 1600, 1200),
				],
			),
			new Photo(
				$this->translator->translate('messages.photo.office.header'),
				new PhotoFile('michalspacek-serious-400x268.jpg', 400, 268),
				$this->translator->translate('messages.photo.office.desc'),
				[
					new PhotoFile('michalspacek-serious-400x268.jpg', 400, 268),
					new PhotoFile('michalspacek-serious-400x400.jpg', 400, 400),
				],
			),
			new Photo(
				$this->translator->translate('messages.photo.talk.header'),
				new PhotoFile('michalspacek-webtop100-400x268.jpg', 400, 268),
				$this->texyFormatter->translatePossiblyUnsafeHtml('messages.photo.talk.desc', ['link:Www:Talks:talk technicke-chyby-vas-pripravi-o-penize-webtop100']),
				[
					new PhotoFile('michalspacek-webtop100-400x268.jpg', 400, 268),
					new PhotoFile('michalspacek-webtop100-720x480.jpg', 720, 480),
				],
			),
		];
		$this->template->photos = $photos;
		$this->template->pageTitle = $this->translator->translate('messages.title.photo');
	}

}
