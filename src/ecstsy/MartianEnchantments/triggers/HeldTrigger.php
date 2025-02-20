<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\triggers;

use ecstsy\MartianEnchantments\utils\managers\EnchantmentDisableManager;
use ecstsy\MartianEnchantments\utils\TriggerHelper;
use ecstsy\MartianEnchantments\utils\TriggerInterface;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\player\Player;

final class HeldTrigger implements TriggerInterface {
    use TriggerHelper;

    public function execute(Entity $attacker, ?Entity $victim, array $enchantments, string $context, array $exteraData = []): void
    {

        if (!$attacker instanceof Player) {
            return;
        }
        
        foreach ($enchantments as $enchantmentData) {
            if (($enchantmentData['config']['applies-to'] ?? '') === 'Armor') {
                continue;
            }
            
            $enchantmentName = $enchantmentData['name'] ?? 'unknown';
            $level = $enchantmentData['level'] ?? 1;

            if (EnchantmentDisableManager::isEnchantmentDisabled($enchantmentName, $attacker->getName())) {
                continue;
            }

            $this->applyEffects($enchantmentData['config']['levels'][$level], $attacker, null, "HELD", []);
        }
    }
}