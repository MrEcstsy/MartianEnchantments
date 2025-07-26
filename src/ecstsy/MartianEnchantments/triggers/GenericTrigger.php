<?php

namespace ecstsy\MartianEnchantments\triggers;

use ecstsy\MartianEnchantments\utils\managers\CooldownManager;
use ecstsy\MartianEnchantments\utils\managers\EnchantmentDisableManager;
use ecstsy\MartianEnchantments\utils\TriggerHelper;
use ecstsy\MartianEnchantments\utils\TriggerInterface;
use pocketmine\entity\Entity;
use pocketmine\player\Player;

class GenericTrigger implements TriggerInterface {
    use TriggerHelper;
    
    public function execute(Entity $attacker, ?Entity $victim, array $enchantments, string $context, array $extraData = []): void {
        if ($victim === null) {
            $victim = $attacker;
        }

        foreach ($enchantments as $enchantmentData) {
            $types = array_map('strtoupper', $enchantmentData['config']['type'] ?? []);
            
            if (in_array("HELD", $types, true) || in_array("EFFECT_STATIC", $types, true)) {
                continue;
            }

            $level = $extraData['enchant-level'] ?? null;
            if ($level === null) {
                continue;
            }
        
            $config = $enchantmentData['config'];
            if (!isset($config['levels'][$level])) {
                continue;
            }
        
            $levelConfig = $config['levels'][$level];
            $baseChance = $levelConfig['chance'] ?? 100;
        
            $conditionsMet = true;
            $adjustedChance = $baseChance;
            $forceTriggered = false;
            $enchantmentName = $enchantmentData['name'] ?? 'unknown';
        
            if ($attacker instanceof Player && EnchantmentDisableManager::isEnchantmentDisabled($enchantmentName, $attacker->getName())) {
                $disabledUntil = EnchantmentDisableManager::getDisabledUntilTime($enchantmentName, $attacker->getName());
                if ($disabledUntil > time()) {
                    continue;  
                } else {
                    EnchantmentDisableManager::removeDisableState($enchantmentName, $attacker->getName());
                }
            }
        
            if ($victim instanceof Player && EnchantmentDisableManager::isEnchantmentDisabled($enchantmentName, $victim->getName())) {
                $disabledUntil = EnchantmentDisableManager::getDisabledUntilTime($enchantmentName, $victim->getName());
                if ($disabledUntil > time()) {
                    continue;  
                } else {
                    EnchantmentDisableManager::removeDisableState($enchantmentName, $victim->getName());
                }
            }
            
            if (!empty($levelConfig['conditions'])) {
                foreach ($levelConfig['conditions'] as $condition) {
                    if (!$this->handleConditions($condition, $attacker, $victim, $context, $extraData)) {
                        $conditionsMet = false;
                        break;
                    }
                }
            }
        
            if (!$conditionsMet && !$forceTriggered) {
                continue;
            }
        
            $effectCooldown = $levelConfig['cooldown'] ?? 0;
        
            if (!$forceTriggered && $effectCooldown > 0 && CooldownManager::isOnCooldown($attacker, $enchantmentName)) {
                continue;
            }
        
            if ($forceTriggered) {
                $this->applyEffects($levelConfig, $attacker, $victim, $context, $extraData, $adjustedChance, $enchantmentName, true);
            } else {
                $this->applyEffects($levelConfig, $attacker, $victim, $context, $extraData, $adjustedChance, $enchantmentName, false);
            }            
        }
    }    
}
