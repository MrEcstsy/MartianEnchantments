<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\enchantments;

use CustomEnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;

final class CustomEnchantmentManager {

    public static function applyEnchantment(Item $item, CustomEnchantment $enchantment, int $level): void {
        $maxLevel = $enchantment->getMaxLevel();
        if ($level > $maxLevel) {
            $level = $maxLevel;
        }
        
        $root = $item->getNamedTag();

        $enchTag = $root->getCompoundTag("MartianCES");
        if ($enchTag === null) {
            $enchTag = new CompoundTag();
        }

        $enchantmentName = $enchantment->getName();

        $enchTag->setInt($enchantmentName, $level);

        $root->setTag("MartianCES", $enchTag);

    }

    public static function removeEnchantment(Item $item, CustomEnchantment $enchantment): void {
        $root = $item->getNamedTag();
        $enchTag = $root->getCompoundTag("MartianCES");

        if ($enchTag === null) {
            return;
        }

        $enchantmentName = $enchantment->getName();
        $enchTag->removeTag($enchantmentName);

        if(empty($enchTag->getValue())) {
            $enchTag->removeTag("MartianCES");
        } else {
            $root->setTag("MartianCES", $enchTag);
        }
    }

    public function sortEnchantmentsByRarity(array $enchantments): array {
        usort($enchantments, function (CustomEnchantmentInstance $enchantmentA, CustomEnchantmentInstance $enchantmentB) {
            return $enchantmentB->getEnchantment()->getRarity() - $enchantmentA->getEnchantment()->getRarity();
        });
        return $enchantments;
    }
}