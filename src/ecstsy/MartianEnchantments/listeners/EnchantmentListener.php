<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\listeners;

use ecstsy\MartianEnchantments\Loader;
use ecstsy\MartianEnchantments\triggers\EffectStaticTrigger;
use ecstsy\MartianEnchantments\triggers\GenericTrigger;
use ecstsy\MartianEnchantments\triggers\HeldTrigger;
use ecstsy\MartianEnchantments\utils\TriggerHelper;
use ecstsy\MartianEnchantments\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use muqsit\arithmexp\Util;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

final class EnchantmentListener implements Listener {

    use TriggerHelper;

    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        $player->getArmorInventory()->getListeners()->add(new CallbackInventoryListener(
            function (Inventory $inventory, int $slot, Item $oldItem): void {
                Utils::onArmorSlotChange($inventory, $slot, $oldItem);
            },
            function (Inventory $inventory, array $oldContents): void {
            }
        ));

        $player->getInventory()->getListeners()->add(new CallbackInventoryListener(
            function (Inventory $inventory, int $slot, Item $oldItem): void {
                Utils::onInventorySlotChange($inventory, $slot, $oldItem);
            },
            function (Inventory $inventory, array $oldContents): void { }
        ));
    }

    /**
     * @priority HIGHEST
     */
    public function onPlayerAttack(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }

        $attacker = $event->getDamager();
        $victim = $event->getEntity();
    
        if (!$attacker instanceof Player || !$victim instanceof Player || $attacker->getInventory()->getItemInHand()->isNull()) {
            return;
        }
    
        $item = $attacker->getInventory()->getItemInHand();
        $enchantments = Utils::extractEnchantmentsFromItems([$item]);
    
        if (empty($enchantments)) {
            return;
        }
    
        $filteredEnchantments = []; 
        foreach ($enchantments as $enchantmentConfig) {
            $contextType = $enchantmentConfig['config']['type'] ?? [];
    
            if (!in_array("ATTACK", $contextType, true)) {
                continue; 
            }
    
            $level = $enchantmentConfig['level'] ?? 1;
            $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
            $enchantName = $enchantmentConfig['name'] ?? 'unknown';
    
            $extraData = [
                'enchant-level' => $level, 
                'chance' => $chance,
                'enchant-name' => $enchantName
            ];
    
            $filteredEnchantments[] = $enchantmentConfig; 
        }
    
        if (!empty($filteredEnchantments)) {
            $trigger = new GenericTrigger();
            $trigger->execute($attacker, $victim, $filteredEnchantments, 'ATTACK', $extraData);
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onAttackMob(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }
        
        $attacker = $event->getDamager();
        $victim = $event->getEntity();
        
        if (!$attacker instanceof Player || $victim instanceof Player || $attacker->getInventory()->getItemInHand()->isNull()) {
            return;
        }    
        
        $item = $attacker->getInventory()->getItemInHand();
        $enchantments = Utils::extractEnchantmentsFromItems([$item]);

        if (empty($enchantments)) {
            return;
        }
        
        $filteredEnchantments = []; 
        foreach ($enchantments as $enchantmentConfig) {
            $contextType = $enchantmentConfig['config']['type'] ?? [];
    
            if (!in_array("ATTACK_MOB", $contextType, true)) {
                continue; 
            }
    
            $level = $enchantmentConfig['level'] ?? 1;
            $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
            $enchantName = $enchantmentConfig['name'] ?? 'unknown';
    
            $extraData = [
                'enchant-level' => $level,
                'chance'        => $chance,
                'enchant-name'  => $enchantName,
            ];
    
            $filteredEnchantments[] = $enchantmentConfig; 
        }
    
        if (!empty($filteredEnchantments)) {
            $trigger = new GenericTrigger();
            $trigger->execute($attacker, $victim, $filteredEnchantments, 'ATTACK_MOB', $extraData);
        }
    }
    
    /**
     * @priority HIGHEST
     */
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

        $filteredEnchantments = [];
        foreach ($enchantments as $enchantmentConfig) {
            $contextType = $enchantmentConfig['config']['type'] ?? [];
    
            if (!in_array("DEFENSE", $contextType, true)) {
                continue; 
            }
    
            $level = $enchantmentConfig['level'] ?? 1;
            $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
            $enchantName = $enchantmentConfig['name'] ?? 'unknown';
    
            $extraData = [
                'enchant-level' => $level,
                'chance'        => $chance,
                'enchant-name'  => $enchantName
            ];
    
            $filteredEnchantments[] = $enchantmentConfig;
        }
    
        if (!empty($filteredEnchantments)) {
            $trigger = new GenericTrigger();
            $trigger->execute($attacker, $victim, $filteredEnchantments, 'DEFENSE', $extraData);
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onMobDefend(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }
    
        $victim = $event->getEntity();
        $attacker = $event->getDamager();
    
        if (!$victim instanceof Living) {
            return;
        }
    
        if ($attacker instanceof Living) {
            $armorItems = $victim->getArmorInventory()->getContents();
            $enchantments = Utils::extractEnchantmentsFromItems($armorItems);
    
            if (empty($enchantments)) {
                return;
            }
    
            $filteredEnchantments = [];
            foreach ($enchantments as $enchantmentConfig) {
                $contextType = $enchantmentConfig['config']['type'] ?? [];
    
                if (!in_array("DEFENSE_MOB", $contextType, true)) {
                    continue; 
                }
    
                $level = $enchantmentConfig['level'] ?? 1;
                $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
                $enchantName = $enchantmentConfig['name'] ?? 'unknown';
    
                $extraData = [
                    'enchant-level' => $level,
                    'chance'        => $chance,
                    'enchant-name'  => $enchantName
                ];
    
                $filteredEnchantments[] = $enchantmentConfig;
            }
    
            if (!empty($filteredEnchantments)) {
                $trigger = new GenericTrigger();
                $trigger->execute($attacker, $victim, $filteredEnchantments, 'DEFENSE_MOB', $extraData);
            }
        } 
    }

    /**
     * @priority HIGHEST
     */
    public function onDefenseMobProjectile(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }
    
        $victim = $event->getEntity();
        $attacker = $event->getDamager();
    
        if (!$victim instanceof Living) {
            return;
        }
    
        if ($attacker instanceof Projectile && $attacker->getOwningEntity() instanceof Living) {
            $armorItems = $victim->getArmorInventory()->getContents();
            $enchantments = Utils::extractEnchantmentsFromItems($armorItems);
    
            if (empty($enchantments)) {
                return;
            }
    
            $filteredEnchantments = [];
            foreach ($enchantments as $enchantmentConfig) {
                $contextType = $enchantmentConfig['config']['type'] ?? [];
    
                if (!in_array("DEFENSE_MOB_PROJECTILE", $contextType, true)) {
                    continue;
                }
    
                $level = $enchantmentConfig['level'] ?? 1;
                $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
                $enchantName = $enchantmentConfig['name'] ?? 'unknown';
    
                $extraData = [
                    'enchant-level' => $level,
                    'chance'        => $chance,
                    'enchant-name'  => $enchantName
                ];
    
                $filteredEnchantments[] = $enchantmentConfig;
            }
    
            if (!empty($filteredEnchantments)) {
                $trigger = new GenericTrigger();
                $trigger->execute($attacker, $victim, $filteredEnchantments, 'DEFENSE_MOB_PROJECTILE', $extraData);
            }
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onPlayerProjectileDefend(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }
    
        $victim = $event->getEntity();
        $attacker = $event->getDamager();
    
        if (!$victim instanceof Living) {
            return;
        }
    
        if ($attacker instanceof Projectile && $attacker->getOwningEntity() instanceof Player) {
            $armorItems = $victim->getArmorInventory()->getContents();
            $enchantments = Utils::extractEnchantmentsFromItems($armorItems);
    
            if (empty($enchantments)) {
                return;
            }
    
            $filteredEnchantments = [];
            foreach ($enchantments as $enchantmentConfig) {
                $contextType = $enchantmentConfig['config']['type'] ?? [];
    
                if (!in_array("DEFENSE_PROJECTILE", $contextType, true)) {
                    continue;
                }
    
                $level = $enchantmentConfig['level'] ?? 1;
                $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
                $enchantName = $enchantmentConfig['name'] ?? 'unknown';
    
                $extraData = [
                    'enchant-level' => $level,
                    'chance'        => $chance,
                    'enchant-name'  => $enchantName
                ];
    
                $filteredEnchantments[] = $enchantmentConfig;
            }
    
            if (!empty($filteredEnchantments)) {
                $trigger = new GenericTrigger();
                $trigger->execute($attacker, $victim, $filteredEnchantments, 'DEFENSE_PROJECTILE', $extraData);
            }
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onPlayerEat(PlayerItemConsumeEvent $event): void {
        $player = $event->getPlayer();
    
        $item = $event->getItem();
    
        $enchantments = Utils::extractEnchantmentsFromItems([$item]);
    
        if (empty($enchantments)) {
            return;
        }
    
        $filteredEnchantments = [];
        foreach ($enchantments as $enchantmentConfig) {
            $contextType = $enchantmentConfig['config']['type'] ?? [];
    
            if (!in_array("EAT", $contextType, true)) {
                continue;
            }
    
            $level = $enchantmentConfig['level'] ?? 1;
            $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
            $enchantName = $enchantmentConfig['name'] ?? 'unknown';
    
            $extraData = [
                'enchant-level' => $level,
                'chance'        => $chance,
                'enchant-name'  => $enchantName
            ];
    
            $filteredEnchantments[] = $enchantmentConfig;
        }
    
        if (!empty($filteredEnchantments)) {
            $trigger = new GenericTrigger();
            $trigger->execute($player, null, $filteredEnchantments, 'EAT', $extraData);
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onEntityMiscDamage(EntityDamageEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }
    
        $entity = $event->getEntity();
        if (!$entity instanceof Living) {
            return;
        }
    
        $config = GeneralUtils::getConfiguration($this->plugin, "enchantments.yml");
        $miscTriggers = ["FALL_DAMAGE", "EXPLOSION", "FIRE"];
        $armorItems = $entity->getArmorInventory()->getContents();
    
        foreach ($miscTriggers as $trigger) {
            $effects = Utils::getEffectsFromItems($armorItems, $trigger, $config);
    
            foreach ($effects as $effect) {
                if (($trigger === "FALL_DAMAGE" && $event->getCause() === EntityDamageEvent::CAUSE_FALL) ||
                    ($trigger === "EXPLOSION" && $event->getCause() === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION)||
                    ($trigger === "FIRE" && ($event->getCause() === EntityDamageEvent::CAUSE_FIRE || $event->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK))) {
    
                    $enchantments = Utils::extractEnchantmentsFromItems($armorItems);
    
                    if (empty($enchantments)) {
                        continue;
                    }
    
                    $filteredEnchantments = [];
                    foreach ($enchantments as $enchantmentConfig) {
                        $contextType = $enchantmentConfig['config']['type'] ?? [];
    
                        if (!in_array($trigger, $contextType, true)) {
                            continue; 
                        }
    
                        $level = $enchantmentConfig['level'] ?? 1;
                        $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
                        $enchantName = $enchantmentConfig['name'] ?? 'unknown';
    
                        $extraData = [
                            'enchant-level' => $level,
                            'chance'        => $chance,
                            'enchant-name'  => $enchantName
                        ];
    
                        $filteredEnchantments[] = $enchantmentConfig;
                    }
    
                    if (!empty($filteredEnchantments)) {
                        $triggerObject = new GenericTrigger();
                        $triggerObject->execute($entity, null, $filteredEnchantments, $trigger, $extraData);

                        if (isset($effect['type']) && $effect['type'] === "CANCEL_EVENT") {
                            $chance = $effect['chance'] ?? 100;
                    
                            if (mt_rand(0, 100) <= $chance) {
                                $extraData = ['chance' => $chance];
                    
                                $conditionsMet = $this->handleConditions($effect['conditions'] ?? [], $entity, null, $trigger, $extraData);
                                if ($conditionsMet) {
                                    $event->cancel();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * @priority HIGHEST
     */
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
    
    /**
     * @priority HIGHEST
     */
    public function onArrowHit(ProjectileHitEntityEvent $event): void {
        $projectile = $event->getEntity();
        $hitEntity = $event->getEntityHit();
        $shooter = $projectile->getOwningEntity();

        if (!$shooter instanceof Player || !$hitEntity instanceof Living || !$projectile instanceof Arrow) {
            return;
        }

        $bow = $shooter->getInventory()->getItemInHand();
        if ($bow->isNull()) {
            return;
        }

        $enchantments = Utils::extractEnchantmentsFromItems([$bow]);
        
        if (empty($enchantments)) {
            return;
        }

        $filteredEnchantments = [];
        foreach ($enchantments as $enchantmentConfig) {
            $contextType = $enchantmentConfig['config']['type'] ?? [];
            
            if (!in_array("ARROW_HIT", $contextType, true)) {
                continue;
            }

            $level = $enchantmentConfig['level'] ?? 1;
            $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
            $enchantName = $enchantmentConfig['name'] ?? 'unknown';

            $extraData = [
                'enchant-level' => $level,
                'chance' => $chance,
                'enchant-name' => $enchantName
            ];

            $filteredEnchantments[] = $enchantmentConfig;
        }

        if (!empty($filteredEnchantments)) {
            $trigger = new GenericTrigger();
            $trigger->execute($shooter, $hitEntity, $filteredEnchantments, 'ARROW_HIT', $extraData);
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onEntityDeath(EntityDeathEvent $event): void {
        $victim = $event->getEntity();
        $config = GeneralUtils::getConfiguration($this->plugin, "enchantments.yml");

        if (!$victim instanceof Living || $victim->isAlive()) {
            return;
        }

        $cause = $victim->getLastDamageCause();

        if ($cause instanceof EntityDamageByEntityEvent) {
            $attacker = $cause->getDamager();

            if ($attacker instanceof Player) {
                $item = $attacker->getInventory()->getItemInHand();
                $enchantments = Utils::getEffectsFromItems([$item], "DEATH", $config);

                if (empty($enchantments)) {
                    return;
                }

                $filteredEnchantments = [];
                foreach ($enchantments as $enchantmentConfig) {
                    $contextType = $enchantmentConfig['config']['type'] ?? [];

                    if (!in_array("DEATH", $contextType, true)) {
                        continue;
                    }

                    $level = $enchantmentConfig['level'] ?? 1;
                    $chance = $enchantmentConfig['config']['levels'][$level]['chance'] ?? 100;
                    $enchantName = $enchantmentConfig['name'] ?? 'unknown';

                    $extraData = [
                        'enchant-level' => $level,
                        'chance' => $chance,
                        'enchant-name' => $enchantName
                    ];

                    $filteredEnchantments[] = $enchantmentConfig;
                }

                if (!empty($filteredEnchantments)) {
                    $trigger = new GenericTrigger();
                    $trigger->execute($attacker, $victim, $filteredEnchantments, 'DEATH', $extraData);
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onPlayerHeld(PlayerItemHeldEvent $event): void {
        $player = $event->getPlayer();
        $oldItem = $player->getInventory()->getItemInHand();
        $newItem = $event->getItem();
    
        if (!$oldItem->isNull()) {
            $oldEnchantments = Utils::extractEnchantmentsFromItems([$oldItem]);
            foreach ($oldEnchantments as $enchantment) {
                Utils::removeEnchantmentEffects($player, $enchantment);
            }
        }
        
        if ($newItem instanceof Armor) {
            return;
        }
        
        if (!$newItem->isNull()) {
            $newEnchantments = Utils::extractEnchantmentsFromItems([$newItem]);
            if (!empty($newEnchantments)) {
                (new HeldTrigger())->execute($player, null, $newEnchantments, "HELD", []);
            }
        }
    }
    
    /**
     * @priority HIGHEST
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();

        if (!$player instanceof Player) {
            return;
        }

        foreach ($transaction->getActions() as $action) {
            if ($action instanceof SlotChangeAction) {
                $inventory = $action->getInventory();

                if ($inventory instanceof ArmorInventory) {
                    $newItem = $action->getTargetItem();
                    $oldItem = $action->getSourceItem();

                    if (!$oldItem->isNull()) {
                        $oldEnchantments = Utils::extractEnchantmentsFromItems([$oldItem]);
                        foreach ($oldEnchantments as $enchantment) {
                            Utils::removeEnchantmentEffects($player, $enchantment);
                        }
                    }

                    if (!$newItem->isNull()) {
                        $newEnchantments = Utils::extractEnchantmentsFromItems([$newItem]);
                        $filteredEnchantments = array_filter($newEnchantments, function(array $enchantmentData): bool {
                            return isset($enchantmentData['config']['type']) && in_array("EFFECT_STATIC", (array)$enchantmentData['config']['type'], true);
                        });
    
                        if (!empty($filteredEnchantments)) {
                            (new EffectStaticTrigger())->execute($player, null, $filteredEnchantments, "EFFECT_STATIC", []);
                        }
                    }
                }
            }
        }
    }
}
