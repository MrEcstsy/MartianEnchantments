<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\listeners;

use ecstsy\MartianEnchantments\Loader;
use ecstsy\MartianEnchantments\triggers\GenericTrigger;
use ecstsy\MartianEnchantments\utils\TriggerHelper;
use ecstsy\MartianEnchantments\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

final class EnchantmentListener implements Listener {

    use TriggerHelper;

    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onPlayerAttack(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }

        $attacker = $event->getDamager();
        $victim = $event->getEntity();
    
        if (!$attacker instanceof Player || $attacker->getInventory()->getItemInHand()->isNull()) {
            return;
        }
    
        $item = $attacker->getInventory()->getItemInHand();
        $enchantments = Utils::extractEnchantmentsFromItems([$item]);
    
        if (empty($enchantments)) {
            return;
        }
    
        foreach ($enchantments as &$enchantmentConfig) {
            $level = $enchantmentConfig['level'] ?? 1;
            $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
            $enchantName = $enchantmentConfig['name'] ?? 'unknown';

            if ($level !== null) {
                $extraData = [
                    'enchant-level' => $level, 
                    "chance" => $chance,
                    'enchant-name' => $enchantName
                ];
            }
        }
        
        $trigger = new GenericTrigger();
        $trigger->execute($attacker, $victim, $enchantments, 'ATTACK', $extraData);
    }

    public function onAttackMob(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }
    
        $attacker = $event->getDamager();
        $victim = $event->getEntity();
    
        if (!($attacker instanceof Player) && !($attacker instanceof Living)) {
            return;
        }
    
        if (!($victim instanceof Living) || $victim instanceof Player) {
            return;
        }
    
        $item = ($attacker instanceof Player) ? $attacker->getInventory()->getItemInHand() : null;
        $enchantments = Utils::extractEnchantmentsFromItems($item !== null ? [$item] : []);
    
        if (empty($enchantments)) {
            return;
        }
    
        $extraData = [
            'enchant-level' => $enchantments['level'] ?? 1,
            'chance' => $enchantments['config']['levels'][$enchantments['level'] ?? 1]['chance'] ?? 100,
            'enchant-name' => $enchantments['name'] ?? 'unknown'
        ];
    
        $trigger = new GenericTrigger();
        $trigger->execute($attacker, $victim, $enchantments, 'ATTACK_MOB', $extraData);
    }
    

    public function onPlayerDefend(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }

        $victim = $event->getEntity();
        $attacker = $event->getDamager();

        if (!$victim instanceof Living) {
            return;
        }

        $armorItems = $victim->getArmorInventory()->getContents();
        $enchantments = Utils::extractEnchantmentsFromItems($armorItems);

        if (empty($enchantments)) {
            return;
        }

        foreach ($enchantments as &$enchantmentConfig) {
            $level = $enchantmentConfig['level'] ?? 1;
            $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
            $enchantName = $enchantmentConfig['name'] ?? 'unknown';

            if ($level !== null) {
                $extraData = [
                    'enchant-level' => $level, 
                    "chance" => $chance,
                    'enchant-name' => $enchantName
                ];
            }
        }

        $trigger = new GenericTrigger();
        $trigger->execute($attacker, $victim, $enchantments, 'DEFENSE', $extraData);
    }

    public function onEntityMiscDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        $config = GeneralUtils::getConfiguration($this->plugin, "enchantments.yml");

        if ($event->isCancelled()) {
            return;
        }

        if (!$entity instanceof Living) {
            return;
        }

        $miscTriggers = ["FALL_DAMAGE"];
        $armorItems = $entity->getArmorInventory()->getContents();
        
        foreach ($miscTriggers as $trigger) {
            $effects = Utils::getEffectsFromItems($armorItems, $trigger, $config);

            if ($trigger === "FALL_DAMAGE") {
                if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
                    foreach ($effects as $effect) {
                        if (isset($effect['type']) && $effect['type'] === "CANCEL_EVENT") {
                            $chance = isset($effect['chance']) ? $effect['chance'] : 100;

                            if (mt_rand(0, 100) <= $chance) {
                                $extraData = ['chance' => 0];
                                $conditionsMet = $this->handleConditions($effect['conditions'] ?? [], $entity, null, "FALL_DAMAGE", $extraData);

                                if ($conditionsMet) {
                                    $event->cancel();
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function onEntityDamageModification(EntityDamageByEntityEvent $event): void {
        $victim = $event->getEntity();
        $attacker = $event->getDamager();
    
        if ($event->isCancelled()) {
            return;
        }
    
        if (!$victim instanceof Living || !$attacker instanceof Player) {
            return;
        }
    
        $config = GeneralUtils::getConfiguration($this->plugin, "enchantments.yml");
        $armorItems = $victim->getArmorInventory()->getContents();
        $weapon = $attacker->getInventory()->getItemInHand();
    
        $defenseEffects = Utils::getEffectsFromItems($armorItems, "DEFENSE", $config);
        $defenseConditions = Utils::getConditionsFromItems($armorItems, "DEFENSE", $config);
    
        foreach ($defenseEffects as $effectGroup) {
            foreach ($effectGroup as $effect) {
                if ($effect['type'] === "DECREASE_DAMAGE") {
                    foreach ($defenseConditions as $conditionGroup) {
                        foreach ($conditionGroup as $condition) {
                            $chance = $effectGroup['chance'] ?? 100;
                            $extraData = ['chance' => $chance];
                            $conditionsMet = $this->handleConditions($condition, $attacker, $victim, "DEFENSE", $extraData);
    
                            if ($conditionsMet) {
                                $finalDamage = $event->getFinalDamage();
                                $percentageReduction = $effect['amount'] ?? 0;
                                $damageReduction = $finalDamage * ($percentageReduction / 100);
    
                                $event->setBaseDamage($event->getBaseDamage() - $damageReduction);
                            }
                        }
                    }
                }
            }
        }
    
        if (!$weapon->isNull()) {
            $attackEffects = Utils::getEffectsFromItems([$weapon], "ATTACK", $config);
            $attackConditions = Utils::getConditionsFromItems([$weapon], "ATTACK", $config);
    
            foreach ($attackEffects as $effectGroup) {
                foreach ($effectGroup as $effect) {
                    if ($effect['type'] === "INCREASE_DAMAGE") {
                        foreach ($attackConditions as $conditionGroup) {
                            foreach ($conditionGroup as $condition) {
                                $chance = $effectGroup['chance'] ?? 100;
                                $extraData = ['chance' => $chance];
                                $conditionsMet = $this->handleConditions($condition, $attacker, $victim, "ATTACK", $extraData);
    
                                if ($conditionsMet) {
                                    $finalDamage = $event->getFinalDamage();
                                    $percentageIncrease = $effect['amount'] ?? 0;
                                    $damageIncrease = $finalDamage * ($percentageIncrease / 100);
    
                                    $event->setBaseDamage($event->getBaseDamage() + $damageIncrease);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
}