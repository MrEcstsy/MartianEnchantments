<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\triggers;

use ecstsy\MartianEnchantments\utils\managers\EnchantmentDisableManager;
use ecstsy\MartianEnchantments\utils\TriggerHelper;
use ecstsy\MartianEnchantments\utils\TriggerInterface;
use pocketmine\entity\Entity;
use pocketmine\player\Player;

final class HeldTrigger implements TriggerInterface {
    use TriggerHelper;

    public function execute(Entity $attacker, ?Entity $victim, array $enchantments, string $context, array $exteraData = []): void {
        if (!$attacker instanceof Player) {
            return;
        }
        
        foreach ($enchantments as $enchantmentData) {
            $types = $enchantmentData['config']['type'] ?? [];
            if (!in_array("HELD", array_map('strtoupper', $types), true)) {
                continue;
            }
            
            $enchantmentName = $enchantmentData['name'] ?? 'unknown';
            $level = $enchantmentData['level'] ?? 1;
            $levelConfig = $enchantmentData['config']['levels'][$level] ?? [];
            $chance = $levelConfig['chance'] ?? 100;

            $extraData = [
                'enchant-name' => $enchantmentName,
                'enchant-level' => $level,
                'chance' => $chance,
            ];

            if (EnchantmentDisableManager::isEnchantmentDisabled($enchantmentName, $attacker->getName())) {
                continue;
            }

            $conditionsMet = true;
            if (!empty($levelConfig['conditions'])) {
                foreach ($levelConfig['conditions'] as $condition) {
                    if (!$this->handleConditions($condition, $attacker, null, $context, $extraData)) {
                        $conditionsMet = false;
                        break;
                    }
                }
            }

            if ($conditionsMet) {
                $this->applyEffects($levelConfig, $attacker, null, "HELD", $extraData);
            }
        }
    }
}
