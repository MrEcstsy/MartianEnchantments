<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\server\items;

use ecstsy\MartianEnchantments\enchantments\CustomEnchantment;
use ecstsy\MartianEnchantments\enchantments\CustomEnchantments;
use ecstsy\MartianEnchantments\enchantments\Groups;
use ecstsy\MartianEnchantments\libs\ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\utils\TextFormat as C;

final class MartianEnchantItems {

    public static function enchantmentBook(CustomEnchantment $enchantment, int $level = 1, ?int $forcedSuccessChance = null, ?int $forcedDestroyChance = null) {
        $appliesTo = array_map('ucfirst', $enchantment->getApplicableItems());
        $success = $forcedSuccessChance !== null ? $forcedSuccessChance : mt_rand(1, 100);
        $destroy = $forcedDestroyChance !== null ? $forcedDestroyChance : mt_rand(1, 100);

        return MartianEnchantItemFactory::create('enchantment-book', [
            'enchantment' => CustomEnchantments::getEnchantmentDisplayName($enchantment->getName(), C::colorize(Groups::translateGroupToColor($enchantment->getRarity()))),
            'enchant-no-color' => ucfirst($enchantment->getName()),
            'level' => $level,
            'roman-level' => GeneralUtils::getRomanNumeral($level),
            'success' => $success,
            'destroy' => $destroy,
            'description' => $enchantment->getDescription(),
            'group-color' => C::colorize(Groups::translateGroupToColor($enchantment->getRarity())),
            'applies-to' => implode(", ", $appliesTo),
        ]);
    }

    public static function whiteScroll() {
        return MartianEnchantItemFactory::create('white-scroll');
    }

    public static function blackScroll(int $success) {
        return MartianEnchantItemFactory::create('black-scroll', [
            'success' => $success
        ]);
    }

}
