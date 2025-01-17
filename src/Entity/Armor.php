<?php
	namespace App\Entity;

	use DaybreakStudios\Utility\DoctrineEntities\EntityInterface;
	use Doctrine\Common\Collections\ArrayCollection;
	use Doctrine\Common\Collections\Collection;
	use Doctrine\Common\Collections\Selectable;
	use Doctrine\ORM\Mapping as ORM;
	use Symfony\Component\Validator\Constraints as Assert;

	/**
	 * @ORM\Entity(repositoryClass="App\Repository\ArmorRepository")
	 * @ORM\Table(
	 *     name="armor",
	 *     indexes={
	 *         @ORM\Index(columns={"type"})
	 *     }
	 * )
	 *
	 * Class Armor
	 *
	 * @package App\Entity
	 */
	class Armor implements EntityInterface, LengthCachingEntityInterface {
		use EntityTrait;
		use AttributableTrait;

		/**
		 * @Assert\NotBlank()
		 * @Assert\Length(max="64")
		 *
		 * @ORM\Column(type="string", length=64, unique=true)
		 *
		 * @var string
		 */
		private $name;

		/**
		 * @Assert\NotBlank()
		 * @Assert\Choice(callback={"App\Game\ArmorType", "all"})
		 *
		 * @ORM\Column(type="string", length=32)
		 *
		 * @var string
		 */
		private $type;

		/**
		 * @Assert\NotBlank()
		 * @Assert\Choice(callback={"App\Game\Rank", "all"})
		 *
		 * @ORM\Column(type="string", length=16)
		 *
		 * @var string
		 */
		private $rank;

		/**
		 * @Assert\NotBlank()
		 * @Assert\Range(min=1)
		 *
		 * @ORM\Column(type="smallint", options={"unsigned": true})
		 *
		 * @var int
		 */
		private $rarity;

		/**
		 * @ORM\Embedded(class="App\Entity\Resistances", columnPrefix="resist_")
		 *
		 * @var Resistances
		 */
		private $resistances;

		/**
		 * @Assert\Valid()
		 *
		 * @ORM\Embedded(class="App\Entity\ArmorDefenseValues", columnPrefix="defense_")
		 *
		 * @var ArmorDefenseValues
		 */
		private $defense;

		/**
		 * @ORM\ManyToMany(targetEntity="App\Entity\SkillRank")
		 * @ORM\JoinTable(name="armor_skill_ranks")
		 *
		 * @var Collection|Selectable|SkillRank[]
		 */
		private $skills;

		/**
		 * @Assert\Valid()
		 *
		 * @ORM\OneToMany(targetEntity="App\Entity\ArmorSlot", mappedBy="armor", orphanRemoval=true, cascade={"all"})
		 *
		 * @var Collection|Selectable|ArmorSlot[]
		 */
		private $slots;

		/**
		 * @ORM\ManyToOne(targetEntity="App\Entity\ArmorSet", inversedBy="pieces")
		 *
		 * @var ArmorSet|null
		 */
		private $armorSet = null;

		/**
		 * @Assert\Valid()
		 *
		 * @ORM\OneToOne(targetEntity="App\Entity\ArmorAssets", orphanRemoval=true, cascade={"all"})
		 *
		 * @var ArmorAssets|null
		 */
		private $assets = null;

		/**
		 * @Assert\Valid()
		 *
		 * @ORM\OneToOne(targetEntity="App\Entity\ArmorCraftingInfo", orphanRemoval=true, cascade={"all"})
		 *
		 * @var ArmorCraftingInfo|null
		 */
		private $crafting = null;

		/**
		 * @ORM\Column(type="integer", options={"unsigned": true, "default": 0})
		 *
		 * @var int
		 * @internal Used to allow API queries against "skills.length"
		 */
		private $skillsLength = 0;

		/**
		 * @ORM\Column(type="integer", options={"unsigned": true, "default": 0})
		 *
		 * @var int
		 * @internal Used to allow API queries against "slots.length"
		 */
		private $slotsLength = 0;

		/**
		 * Armor constructor.
		 *
		 * @param string $name
		 * @param string $type
		 * @param string $rank
		 * @param int    $rarity
		 */
		public function __construct(string $name, string $type, string $rank, int $rarity) {
			$this->name = $name;
			$this->type = $type;
			$this->rank = $rank;
			$this->rarity = $rarity;

			$this->resistances = new Resistances();
			$this->defense = new ArmorDefenseValues();

			$this->skills = new ArrayCollection();
			$this->slots = new ArrayCollection();
		}

		/**
		 * @return string
		 */
		public function getName(): string {
			return $this->name;
		}

		/**
		 * @param string $name
		 *
		 * @return $this
		 */
		public function setName(string $name) {
			$this->name = $name;

			return $this;
		}

		/**
		 * @return string
		 */
		public function getType(): string {
			return $this->type;
		}

		/**
		 * @param string $type
		 *
		 * @return $this
		 */
		public function setType(string $type) {
			$this->type = $type;

			return $this;
		}

		/**
		 * @return SkillRank[]|Collection|Selectable
		 */
		public function getSkills() {
			return $this->skills;
		}

		/**
		 * @return ArmorSlot[]|Collection|Selectable
		 */
		public function getSlots() {
			return $this->slots;
		}

		/**
		 * @return string
		 */
		public function getRank(): string {
			return $this->rank;
		}

		/**
		 * @param string $rank
		 *
		 * @return $this
		 */
		public function setRank(string $rank) {
			$this->rank = $rank;

			return $this;
		}

		/**
		 * @return ArmorSet|null
		 */
		public function getArmorSet(): ?ArmorSet {
			return $this->armorSet;
		}

		/**
		 * @param ArmorSet|null $armorSet
		 *
		 * @return $this
		 */
		public function setArmorSet(?ArmorSet $armorSet) {
			if ($armorSet === null && $this->armorSet)
				$this->armorSet->getPieces()->removeElement($this);

			$this->armorSet = $armorSet;

			if ($armorSet && !$armorSet->getPieces()->contains($this))
				$armorSet->getPieces()->add($this);

			return $this;
		}

		/**
		 * @return int
		 */
		public function getRarity(): int {
			return $this->rarity;
		}

		/**
		 * @param int $rarity
		 *
		 * @return $this
		 */
		public function setRarity(int $rarity) {
			$this->rarity = $rarity;
			return $this;
		}

		/**
		 * @return Resistances
		 */
		public function getResistances(): Resistances {
			return $this->resistances;
		}

		/**
		 * @return ArmorDefenseValues
		 */
		public function getDefense(): ArmorDefenseValues {
			return $this->defense;
		}

		/**
		 * @return ArmorAssets|null
		 */
		public function getAssets(): ?ArmorAssets {
			return $this->assets;
		}

		/**
		 * @param ArmorAssets|null $assets
		 *
		 * @return $this
		 */
		public function setAssets(?ArmorAssets $assets) {
			$this->assets = $assets;

			return $this;
		}

		/**
		 * @return ArmorCraftingInfo|null
		 */
		public function getCrafting(): ?ArmorCraftingInfo {
			return $this->crafting;
		}

		/**
		 * @param ArmorCraftingInfo $crafting
		 *
		 * @return $this
		 */
		public function setCrafting(ArmorCraftingInfo $crafting) {
			$this->crafting = $crafting;

			return $this;
		}

		/**
		 * @return void
		 */
		public function syncLengthFields(): void {
			$this->skillsLength = $this->skills->count();
			$this->slotsLength = $this->slots->count();
		}
	}