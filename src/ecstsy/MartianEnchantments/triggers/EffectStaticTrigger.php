<?php
declare(strict_types=1);

namespace ecstsy\MartianEnchantments\triggers;

use ecstsy\MartianEnchantments\utils\EffectTracker;
use ecstsy\MartianEnchantments\utils\managers\EnchantmentDisableManager;
use ecstsy\MartianEnchantments\utils\TriggerHelper;
use ecstsy\MartianEnchantments\utils\TriggerInterface;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\player\Player;

final class EffectStaticTrigger implements TriggerInterface {
    use TriggerHelper;

    public function execute(Entity $attacker, ?Entity $victim, array $enchantments, string $context, array $extraData = []): void {
        if (!$attacker instanceof Player) {
            return;
        }
        
        foreach ($enchantments as $enchantmentData) {
            $types = $enchantmentData['config']['type'] ?? [];
            if (!in_array("EFFECT_STATIC", array_map('strtoupper', $types), true)) {
                continue;
            }

            if (($enchantmentData['config']['applies-to'] ?? '') !== 'Armor') {
                continue;
            }
            
            $enchantmentName = $enchantmentData['name'] ?? 'unknown';
            $level = $enchantmentData['level'] ?? 1;
            
            if (EnchantmentDisableManager::isEnchantmentDisabled($enchantmentName, $attacker->getName())) {
                continue;
            }
            
            $this->applyEffects($enchantmentData['config']['levels'][$level], $attacker, null, "EFFECT_STATIC", $extraData);
        }
    }
    
}
