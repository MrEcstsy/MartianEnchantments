<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\utils;

use pocketmine\player\Player;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\Server;

final class EffectTracker {

    /** @var array<string, array<string, array<string, Effect>>> */
    private static array $playerEffects = [];

    public static function addEffect(Player $player, EffectInstance $effect, string $enchantmentName): void {
        $playerName = $player->getName();
        $effectName = Server::getInstance()->getLanguage()->translate($effect->getType()->getName());
        self::$playerEffects[$playerName][$enchantmentName][$effectName] = $effect;
        $player->getEffects()->add($effect);
    }

    public static function removeEnchantmentEffects(Player $player, string $enchantmentName): void {
        $playerName = $player->getName();
        if (!isset(self::$playerEffects[$playerName][$enchantmentName])) {
            return;
        }

        foreach (self::$playerEffects[$playerName][$enchantmentName] as $effect) {

            if (!$effect instanceof EffectInstance) {
                return;
            }

            $player->getEffects()->remove($effect->getType());
        }

        unset(self::$playerEffects[$playerName][$enchantmentName]);
    }
    
    public static function hasEffect(Player $player, string $enchantmentName, string $effectName): bool {
        return isset(self::$playerEffects[$player->getName()][$enchantmentName][$effectName]);
    }
}
