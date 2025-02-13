<?php

namespace ecstsy\MartianEnchantments\utils\managers;

class EnchantmentDisableManager {
    private static array $disabledEnchantments = [];

    /**
     * Check if an enchantment is currently disabled for a player.
     */
    public static function isEnchantmentDisabled(string $enchantmentId, string $playerName): bool {
        return isset(self::$disabledEnchantments[$playerName][$enchantmentId]) &&
            self::$disabledEnchantments[$playerName][$enchantmentId] > time();
    }

    /**
     * Get the time until an enchantment is re-enabled for a player.
     */
    public static function getDisabledUntilTime(string $enchantmentId, string $playerName): int {
        return self::$disabledEnchantments[$playerName][$enchantmentId] ?? 0;
    }

    /**
     * Remove the disabled state of an enchantment for a player.
     */
    public static function removeDisableState(string $enchantmentId, string $playerName): void {
        unset(self::$disabledEnchantments[$playerName][$enchantmentId]);
    }

    /**
     * Disable an enchantment for a player for a specified duration.
     */
    public static function disableEnchantmentForPlayer(string $enchantmentId, string $playerName, int $duration): void {
        self::$disabledEnchantments[$playerName][$enchantmentId] = time() + $duration;
    }
}