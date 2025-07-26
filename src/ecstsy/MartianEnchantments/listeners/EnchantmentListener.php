<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\listeners;

use ecstsy\MartianEnchantments\Loader;
use ecstsy\MartianEnchantments\triggers\EffectStaticTrigger;
use ecstsy\MartianEnchantments\triggers\GenericTrigger;
use ecstsy\MartianEnchantments\triggers\HeldTrigger;
use ecstsy\MartianEnchantments\utils\TriggerHelper;
use ecstsy\MartianEnchantments\utils\Utils;
use ecstsy\MartianEnchantments\libs\ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianEnchantments\utils\managers\CooldownManager;
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
use pocketmine\scheduler\ClosureTask;

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
    public function onAnyProjectileHit(ProjectileHitEntityEvent $event): void {
        $projectile = $event->getEntity();
        $victim = $event->getEntityHit();
        $shooter = $projectile->getOwningEntity();

        if (!$projectile instanceof Projectile) {
            return;
        }

        if (!$victim instanceof Living) {
            return;
        }

        if (!$shooter instanceof Player) {
            return;
        }

        $armorItems = $victim->getArmorInventory()->getContents();
        $enchants = Utils::extractEnchantmentsFromItems($armorItems);

        $filtered = [];
        $extraData = [];
        foreach ($enchants as $cfg) {
            $types = $cfg['config']['type'] ?? [];

            if (!in_array("DEFENSE_PROJECTILE", $types, true)) {
                continue;
            }

            $level = $cfg['level'] ?? 1;
            $filtered[] = $cfg;
            $extraData = [
                'enchant-name' => $cfg['name'] ?? 'unknown',
                'enchant-level' => $level,
                'chance' => $cfg['config']['levels'][$level]['chance'] ?? 100,
            ];
        }

        if (!empty($filtered)) {
            (new GenericTrigger())->execute($shooter, $victim, $filtered, 'DEFENSE_PROJECTILE', $extraData);
        } 
    }

    /**
     * @priority HIGHEST
     */
    public function onPlayerEat(PlayerItemConsumeEvent $event): void {
        $player = $event->getPlayer();

        $armorItems = $player->getArmorInventory()->getContents();
        $enchantments = Utils::extractEnchantmentsFromItems($armorItems);

        if (empty($enchantments)) {
            return;
        }

        $filteredEnchantments = [];
        $extraData = [];

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
                'chance' => $chance,
                'enchant-name' => $enchantName
            ];

            $filteredEnchantments[] = $enchantmentConfig;
        }

        if (!empty($filteredEnchantments)) {
            (new GenericTrigger())->execute($player, null, $filteredEnchantments, 'EAT', $extraData);
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

        $cause = $event->getCause();
        $armorItems = $entity->getArmorInventory()->getContents();

        $triggers = [
            "FALL_DAMAGE" => EntityDamageEvent::CAUSE_FALL,
            "EXPLOSION" => [EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION],
            "FIRE" => [EntityDamageEvent::CAUSE_FIRE, EntityDamageEvent::CAUSE_FIRE_TICK],
        ];

        foreach ($triggers as $trigger => $expected) {
            $matchesCause = is_array($expected)
                ? in_array($cause, $expected, true)
                : $cause === $expected;
            if (!$matchesCause) {
                continue;
            }

            $allEnchants = Utils::extractEnchantmentsFromItems($armorItems);
            $toTrigger = [];
            $extraData = [];

            foreach ($allEnchants as $enchant) {
                $types = $enchant['config']['type'] ?? [];
                if (!in_array($trigger, $types, true)) {
                    continue;
                }

                $level = $enchant['level'] ?? 1;
                $levelCfg = $enchant['config']['levels'][$level] ?? [];
                $chance = $levelCfg['chance'] ?? 100;
                $name = $enchant['name']    ?? 'unknown';

                $extraData = [
                    'enchant-name' => $name,
                    'enchant-level' => $level,
                    'chance' => $chance,
                ];

                foreach ($levelCfg['effects'] as $effect) {
                    if (($effect['type'] ?? '') !== "CANCEL_EVENT") continue;
                    if (CooldownManager::isOnCooldown($entity, $name)) continue;

                    if (mt_rand(1, 100) <= $chance) {
                        $conds = $effect['conditions'] ?? [];
                        $condsMet = empty($conds)
                            ? true
                            : $this->handleConditions($conds, $entity, null, $trigger, $extraData);
                        if ($condsMet) {
                            $event->cancel();
                            CooldownManager::setCooldown($entity, $name, $levelCfg['cooldown'] ?? 0);
                            return;
                        }
                    }
                }

                $toTrigger[] = $enchant;
            }

            if (!empty($toTrigger)) {
                (new GenericTrigger())->execute($entity, null, $toTrigger, $trigger, $extraData);
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

        if (!$projectile instanceof Arrow || !$shooter instanceof Player || !$hitEntity instanceof Living) {
            return;
        }

        $bow = $shooter->getInventory()->getItemInHand();
        if (!$bow->isNull()) {
            Utils::handleArrowHitEnchants($shooter, $hitEntity, [$bow]);
        } 

        $armorItems = $hitEntity->getArmorInventory()->getContents();
        Utils::handleArrowHitEnchants($shooter, $hitEntity, $armorItems);
    }

    /**
     * @priority HIGHEST
     */
    public function onEntityDeath(EntityDeathEvent $event): void {
        $victim = $event->getEntity();
        $config = GeneralUtils::getConfiguration($this->plugin, "enchantments.yml");

        echo "[DEATH] Event triggered for entity: " . $victim->getName() . PHP_EOL;

        if (!$victim instanceof Living || $victim->isAlive()) {
            echo "[DEATH] Skipped: not a living or still alive" . PHP_EOL;
            return;
        }

        $cause = $victim->getLastDamageCause();
        $attacker = null;

        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                $attacker = $damager;
            }
        }

        $victimItems = [];
        if ($victim instanceof Player) {
            $victimItems[] = $victim->getInventory()->getItemInHand();
        }
        $victimItems = array_merge($victimItems, $victim->getArmorInventory()->getContents());

        $victimEnchants = Utils::getEffectsFromItems($victimItems, "DEATH", $config);

        if (!empty($victimEnchants)) {
            echo "[DEATH] Found " . count($victimEnchants) . " victim-side DEATH enchants" . PHP_EOL;
            (new GenericTrigger())->execute($victim, $attacker, $victimEnchants, "DEATH");
        } else {
            echo "[DEATH] No victim-side DEATH enchants" . PHP_EOL;
        }

        if ($attacker instanceof Player) {
            $attackerItems = [$attacker->getInventory()->getItemInHand()];
            $attackerEnchants = Utils::getEffectsFromItems($attackerItems, "DEATH", $config);

            if (!empty($attackerEnchants)) {
                echo "[DEATH] Found " . count($attackerEnchants) . " attacker-side DEATH enchants" . PHP_EOL;
                (new GenericTrigger())->execute($attacker, $victim, $attackerEnchants, "DEATH");
            } else {
                echo "[DEATH] No attacker-side DEATH enchants" . PHP_EOL;
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
     * 
     * TODO: Make reliability better, occasionally
     *  doesnt detect shift clicking to equip &
     *  never detects right clicking in hand...
     * 
     * MartianEnchants/Utils.php (line 255-305) (is paired with armor inv listener)
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
