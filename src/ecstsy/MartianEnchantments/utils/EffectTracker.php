<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\utils;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\player\Player;

final class EffectTracker {
    private static array $trackedEffects = [];

    public static function addEffect(Player $player, string $enchantName, int $slot, EffectInstance $effect): void {
        self::$trackedEffects[$player->getName()][$enchantName][$slot] = $effect;
    }

    public static function removeEffects(Player $player, string $enchantName, int $slot): void {
        $name = $player->getName();
        if (isset(self::$trackedEffects[$name][$enchantName][$slot])) {
            $effect = self::$trackedEffects[$name][$enchantName][$slot];
            $player->getEffects()->remove($effect->getType());
            unset(self::$trackedEffects[$name][$enchantName][$slot]);
        }
    }

    public static function clearSlotEffects(Player $player, int $slot): void {
        $name = $player->getName();
        foreach (self::$trackedEffects[$name] ?? [] as $enchantName => $slots) {
            if (isset($slots[$slot])) {
                $player->getEffects()->remove($slots[$slot]->getType());
                unset(self::$trackedEffects[$name][$enchantName][$slot]);
            }
        }
    }
}