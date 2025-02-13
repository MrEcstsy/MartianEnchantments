<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\enchantments;

use ecstsy\MartianEnchantments\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\Bow;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shovel;
use pocketmine\item\Sword;

final class CustomEnchantment {
    private string $name;
    private int $rarity;
    private string $description;
    private int $maxLevel;
    private int $primaryFlag;
    private int $secondaryFlag;

    public function __construct(string $name, int $rarity, string $description, int $maxLevel, int $primaryFlag, int $secondaryFlag = ItemFlags::NONE) {
        $this->name = $name;
        $this->rarity = $rarity;
        $this->description = $description;
        $this->maxLevel = $maxLevel;
        $this->primaryFlag = $primaryFlag;
        $this->secondaryFlag = $secondaryFlag;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getRarity(): int {
        return $this->rarity;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getMaxLevel(): int {
        return $this->maxLevel;
    }

    public function getPrimaryFlag(): mixed {
        return $this->primaryFlag;
    }

    public function getSecondaryFlag(): mixed {
        return $this->secondaryFlag;
    }

    public function getLoreLine(int $level): string {
        $groupId = $this->rarity;
        $groupName = Groups::getGroupName($groupId);
        $color = Groups::translateGroupToColor($groupId);
    
        return $color . $groupName . " " . GeneralUtils::getRomanNumeral($level);
    }
    
    public static function getApplicable(Enchantment $enchantment): string {
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "enchantments.yml")->getAll();
        $enchantmentData = $config[$enchantment->getName()];
        if (is_array($enchantmentData['applies'])) {
            return implode(", ", $enchantmentData['applies']);
        }
        return $enchantmentData['applies'] ?? '';
    }
    
    public static function matchesApplicable(Item $item, string $applicable): bool {
        $applicableTypes = explode(", ", $applicable);
        foreach ($applicableTypes as $type) {
            switch (strtolower($type)) {;
                case 'all_sword':
                    if ($item instanceof Sword) return true;
                    break;
                case 'netherite_sword':
                    if ($item->getTypeId() === ItemTypeIds::NETHERITE_SWORD) return true;
                    break;    
                case 'diamond_sword':
                    if ($item->getTypeId() === ItemTypeIds::DIAMOND_SWORD) return true;
                    break;
                case 'golden_sword':    
                case 'gold_sword':
                    if ($item->getTypeId() === ItemTypeIds::GOLDEN_SWORD) return true;
                    break;    
                case 'iron_sword':
                    if ($item->getTypeId() === ItemTypeIds::IRON_SWORD) return true;
                    break;
                case 'stone_sword':
                    if ($item->getTypeId() === ItemTypeIds::STONE_SWORD) return true;
                    break;
                case 'wood_sword':
                case 'wooden_sword':
                    if ($item->getTypeId() === ItemTypeIds::WOODEN_SWORD) return true;
                    break;      
                case 'all_pickaxe':
                    if ($item instanceof Pickaxe) return true;
                    break;
                case 'netherite_pickaxe':
                    if ($item->getTypeId() === ItemTypeIds::NETHERITE_PICKAXE) return true;
                    break;
                case 'diamond_pickaxe':
                    if ($item->getTypeId() === ItemTypeIds::DIAMOND_PICKAXE) return true;
                    break;
                case 'golden_pickaxe':
                case 'gold_pickaxe':
                    if ($item->getTypeId() === ItemTypeIds::GOLDEN_PICKAXE) return true;
                    break;
                case 'iron_pickaxe':
                    if ($item->getTypeId() === ItemTypeIds::IRON_PICKAXE) return true;
                    break;
                case 'stone_pickaxe':
                    if ($item->getTypeId() === ItemTypeIds::STONE_PICKAXE) return true;
                    break;
                case 'wood_pickaxe':
                case 'wooden_pickaxe':
                    if ($item->getTypeId() === ItemTypeIds::WOODEN_PICKAXE) return true;
                    break;    
                case 'all_axe':
                    if ($item instanceof Axe) return true;
                    break;
                case 'netherite_axe':
                    if ($item->getTypeId() === ItemTypeIds::NETHERITE_AXE) return true;
                    break;
                case 'diamond_axe':
                    if ($item->getTypeId() === ItemTypeIds::DIAMOND_AXE) return true;
                    break;
                case 'golden_axe':
                case 'gold_axe':
                    if ($item->getTypeId() === ItemTypeIds::GOLDEN_AXE) return true;
                    break;
                case 'iron_axe':
                    if ($item->getTypeId() === ItemTypeIds::IRON_AXE) return true;
                    break;
                case 'stone_axe':
                    if ($item->getTypeId() === ItemTypeIds::STONE_AXE) return true;
                    break;
                case 'wood_axe':
                case 'wooden_axe':
                    if ($item->getTypeId() === ItemTypeIds::WOODEN_AXE) return true;
                    break;
                case 'all_spade':    
                case 'all_shovel':
                    if ($item instanceof Shovel) return true;
                    break;
                case 'netherite_shovel':
                    if ($item->getTypeId() === ItemTypeIds::NETHERITE_SHOVEL) return true;
                    break;
                case 'diamond_shovel':
                    if ($item->getTypeId() === ItemTypeIds::DIAMOND_SHOVEL) return true;
                    break;
                case 'golden_shovel':
                case 'gold_shovel':
                    if ($item->getTypeId() === ItemTypeIds::GOLDEN_SHOVEL) return true;
                    break;
                case 'iron_shovel':
                    if ($item->getTypeId() === ItemTypeIds::IRON_SHOVEL) return true;
                    break;
                case 'stone_shovel':
                    if ($item->getTypeId() === ItemTypeIds::STONE_SHOVEL) return true;
                    break;
                case 'wood_shovel':
                case 'wooden_shovel':
                    if ($item->getTypeId() === ItemTypeIds::WOODEN_SHOVEL) return true;
                    break;
                case 'all_armor':
                    if ($item instanceof Armor) return true;
                    break;
                case 'all_helmet':
                    if ($item->getTypeId() === ItemTypeIds::NETHERITE_HELMET || $item->getTypeId() === ItemTypeIds::DIAMOND_HELMET || $item->getTypeId() === ItemTypeIds::GOLDEN_HELMET || $item->getTypeId() === ItemTypeIds::IRON_HELMET || $item->getTypeId() === ItemTypeIds::CHAINMAIL_HELMET || $item->getTypeId() === ItemTypeIds::LEATHER_CAP) return true;
                    break;     
                case 'all_chestplate':
                    if ($item->getTypeId() === ItemTypeIds::NETHERITE_CHESTPLATE || $item->getTypeId() === ItemTypeIds::DIAMOND_CHESTPLATE || $item->getTypeId() === ItemTypeIds::GOLDEN_CHESTPLATE || $item->getTypeId() === ItemTypeIds::IRON_CHESTPLATE || $item->getTypeId() === ItemTypeIds::CHAINMAIL_CHESTPLATE || $item->getTypeId() === ItemTypeIds::LEATHER_TUNIC) return true;     
                    break;  
                case 'all_leggings':
                    if ($item->getTypeId() === ItemTypeIds::NETHERITE_LEGGINGS || $item->getTypeId() === ItemTypeIds::DIAMOND_LEGGINGS || $item->getTypeId() === ItemTypeIds::GOLDEN_LEGGINGS || $item->getTypeId() === ItemTypeIds::IRON_LEGGINGS || $item->getTypeId() === ItemTypeIds::CHAINMAIL_LEGGINGS || $item->getTypeId() === ItemTypeIds::LEATHER_PANTS) return true;
                    break;
                case 'all_boots':
                    if ($item->getTypeId() === ItemTypeIds::NETHERITE_BOOTS || $item->getTypeId() === ItemTypeIds::DIAMOND_BOOTS || $item->getTypeId() === ItemTypeIds::GOLDEN_BOOTS || $item->getTypeId() === ItemTypeIds::IRON_BOOTS || $item->getTypeId() === ItemTypeIds::CHAINMAIL_BOOTS || $item->getTypeId() === ItemTypeIds::LEATHER_BOOTS) return true;
                    break;    
                case 'trident':
                    if ($item->getTypeId() === 20458) return true;   // Is this safe??
                    break;
                case "bow":
                    if ($item instanceof Bow) return true;
                    break;    
            }
        }
        return false;
    }

}