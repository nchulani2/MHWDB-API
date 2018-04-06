<?php
	namespace App\Scraping\Scrapers\Helpers\KiranicoWeaponParser\WeaponDataInterpreters;

	use App\Game\Attribute;
	use App\Scraping\Scrapers\Helpers\KiranicoWeaponParser\WeaponData;
	use App\Scraping\Scrapers\Helpers\KiranicoWeaponParser\WeaponDataInterpreterInterface;
	use App\Utility\StringUtil;
	use Symfony\Component\DomCrawler\Crawler;

	class SpecialAmmoInterpreter implements WeaponDataInterpreterInterface {
		/**
		 * {@inheritdoc}
		 */
		public function supports(Crawler $node): bool {
			return $node->filter('small.text-muted')->text() === 'Special Ammo';
		}

		/**
		 * {@inheritdoc}
		 */
		public function parse(Crawler $node, WeaponData $target): void {
			$ammo = StringUtil::clean($node->filter('.lead')->text());

			$target->setAttribute(Attribute::SPECIAL_AMMO, $ammo);
		}
	}