<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\utils;

use ecstsy\AdvancedEnchantments\Enchantments\CustomEnchantmentIds;
use ecstsy\MartianEnchantments\conditions\IsHoldingCondition;
use ecstsy\MartianEnchantments\conditions\IsSneakingCondition;
use ecstsy\MartianEnchantments\effects\ActionBarEffect;
use ecstsy\MartianEnchantments\effects\AddAirEffect;
use ecstsy\MartianEnchantments\effects\AddFoodEffect;
use ecstsy\MartianEnchantments\effects\AddHealthEffect;
use ecstsy\MartianEnchantments\effects\AddPotionEffect;
use ecstsy\MartianEnchantments\effects\BloodEffect;
use ecstsy\MartianEnchantments\effects\BurnEffect;
use ecstsy\MartianEnchantments\effects\DisableActivationEffect;
use ecstsy\MartianEnchantments\effects\StealHealthEffect;
use ecstsy\MartianEnchantments\Loader;
use ecstsy\MartianEnchantments\triggers\EffectStaticTrigger;
use ecstsy\MartianEnchantments\triggers\GenericTrigger;
use ecstsy\MartianEnchantments\triggers\HeldTrigger;
use ecstsy\MartianEnchantments\utils\registries\ConditionRegistry;
use ecstsy\MartianEnchantments\utils\registries\EffectRegistry;
use ecstsy\MartianEnchantments\utils\registries\TriggerRegistry;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;

final class Utils {
    public const FAKE_ENCH_ID = -1;

    public static function initRegistries(): void {
        $triggers = [

        ];

        $conditions = [
            //"VICTIM_HEALTH" => new VictimHealthCondition(),
            "IS_SNEAKING" => new IsSneakingCondition(),
            "IS_HOLDING" => new IsHoldingCondition(),
        ];

        $effects = [
            'ADD_POTION' => new AddPotionEffect(),
            'ACTION_BAR' => new ActionBarEffect(),
            'ADD_AIR' => new AddAirEffect(),
            //'ADD_DURABILITY_ARMOR' => new AddDurabilityArmorEffect(),
            //'ADD_DURABILITY_CURRENT_ITEM' => new AddDurabilityCurrentItemEffect(),
            //'ADD_DURABILITY_ITEM' => new AddDurabilityItemEffect(),
            'ADD_FOOD' => new AddFoodEffect(),
            'ADD_HEALTH' => new AddHealthEffect(),
            'BLOOD' => new BloodEffect(),
            //'BOOST' => new BoostEffect(),
            'BURN' => new BurnEffect(),
            //'CACTUS' => new CactusEffect(),
            //'CONSOLE_COMMAND' => new ConsoleCommandEffect(),
            //'CURE' => new CureEffect(),
            //'CURE_PERMANENT' => new CurePermanentEffect(),
            'DISABLE_ACTIVATION' => new DisableActivationEffect(),
            'STEAL_HEALTH' => new StealHealthEffect(),
        ];

        foreach ($triggers as $trigger => $handler) {
            TriggerRegistry::register($trigger, $handler);
        }

        foreach ($conditions as $condition => $handler) {
            ConditionRegistry::register($condition, $handler);
        }

        foreach ($effects as $effect => $handler) {
            EffectRegistry::register($effect, $handler);
        }
    }

    public static function applyDisplayEnchant(Item $item): void {
        $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(self::FAKE_ENCH_ID)));
    }

    public static function removeDisplayEnchant(Item $item): void {
        $item->removeEnchantment(EnchantmentIdMap::getInstance()->fromId(self::FAKE_ENCH_ID));
    }

    /**
     * Sends an error message to a player with standardized formatting.
     *
     * @param Player $player
     * @param string $message
     * @param array $context Additional context for the message.
     */
    public static function sendError(Player $player, string $message, array $context = []): void {
        $formattedMessage = C::RED . "Error: " . C::WHITE . $message;

        if (!empty($context)) {
            $formattedMessage .= C::GRAY . " (" . implode(", ", $context) . ")";
        }

        $player->sendMessage($formattedMessage);
    }

    /**
     * Extract enchantments from items and enrich them with their configuration.
     * 
     * @param Item[] $items
     * @return array
     */
    public static function extractEnchantmentsFromItems(array $items): array {
        $enchantmentsToApply = [];
        $enchantmentConfigs = GeneralUtils::getConfiguration(Loader::getInstance(), "enchantments.yml")->getAll();
        
        foreach ($items as $item) {
            if ($item->isNull()) {
                continue;
            }
            
            $root = $item->getNamedTag();
            $customEnchTag = $root->getCompoundTag("MartianCES");
            if ($customEnchTag === null) {
                continue;
            }
            
            foreach ($customEnchTag->getValue() as $enchantName => $levelTag) {
                $key = strtolower((string)$enchantName);
                if (!isset($enchantmentConfigs[$key])) {
                    continue;
                }
                
                $level = $levelTag->getValue();
                $enchantmentConfig = $enchantmentConfigs[$key];
                $enchantmentConfig['level'] = $level;
                $appliesTo = $enchantmentConfig['applies_to'] ?? [];

                $enchantmentsToApply[] = [
                    'name'   => $key,       
                    'level'  => $level,
                    'config' => $enchantmentConfig,
                    'applies_to' => $appliesTo
                ];
            }
        }
        
        return $enchantmentsToApply;
    }

    public static function getEffectsFromItems(array $items, string $trigger, Config $config): array {
        return self::extractEnchantments($items, function (array $itemData, Item $item) use ($trigger, $config) {
            $identifier = strtolower($itemData['name']);
            $configData = $config->get($identifier);
            if ($configData && in_array($trigger, $configData['type'], true)) {
                return $configData['levels'][$itemData['level']]['effects'] ?? [];
            }
            return [];
        });
    }
    
    public static function getConditionsFromItems(array $items, string $trigger, Config $config): array {
        return self::extractEnchantments($items, function (array $itemData, Item $item) use ($trigger, $config) {
            $identifier = strtolower($itemData['name']);
            $configData = $config->get($identifier);
            if ($configData && in_array($trigger, $configData['type'], true)) {
                return $configData['levels'][$itemData['level']]['conditions'] ?? [];
            }
            return [];
        });
    }

    public static function extractEnchantments(array $items, callable $callback): array {
        $result = [];
        foreach ($items as $item) {
            if ($item->isNull()) continue;
    
            $nbt = $item->getNamedTag()->getCompoundTag("MartianCES");
            if ($nbt === null) continue;
    
            foreach ($nbt->getValue() as $enchantName => $levelTag) {
                $level = $levelTag->getValue();
                $enchantmentData = [
                    'name' => $enchantName,
                    'level' => $level
                ];
                $result[] = $callback($enchantmentData, $item);
            }
        }
        return $result;
    }
    
    public static function updateGlowEffect(Item $item): void {
        $root = $item->getNamedTag();
        $martianCES = $root->getCompoundTag("MartianCES");
        $vanillaEnchants = $item->getEnchantments(); 
        
        if (($martianCES !== null && $martianCES->count() > 0) && count($vanillaEnchants) === 0) {
            self::applyDisplayEnchant($item); 
        } elseif (count($vanillaEnchants) > 0 || ($martianCES !== null && $martianCES->count() > 0)) {
            self::applyDisplayEnchant($item);
        } else {
            self::removeDisplayEnchant($item);
        }
    }

    public static function removeEnchantmentEffects(Player $player, array $enchantmentData): void {
        $level = $enchantmentData['level'] ?? 1;
        if (!isset($enchantmentData['config']['levels'][$level]['effects'])) {
            return;
        }
        
        $effects = $enchantmentData['config']['levels'][$level]['effects'];
        foreach ($effects as $effectData) {
            if (strtoupper($effectData['type'] ?? '') === 'ADD_POTION' && isset($effectData['potion'])) {
                $potionEffect = StringToEffectParser::getInstance()->parse($effectData['potion']);
                if ($potionEffect !== null) {
                    $player->getEffects()->remove($potionEffect);
                }
            }
        }
    }    
    
    public static function onInventorySlotChange(PlayerInventory $inventory, int $slot, Item $oldItem): void {
        $player = $inventory->getHolder();
        
        if ($player instanceof Player) {
            $heldSlot = $player->getInventory()->getHeldItemIndex();
            $newItem = $inventory->getItem($slot);
    
            if ($slot === $heldSlot) {
                if (!$oldItem->equals($newItem, false)) { 
                    if (!$oldItem->isNull()) {
                        $oldEnchantments = self::extractEnchantmentsFromItems([$oldItem]);
                        foreach ($oldEnchantments as $enchantment) {
                            self::removeEnchantmentEffects($player, $enchantment);
                        }
                    }
                }
    
                if (!$newItem->isNull()) {
                    $newEnchantments = self::extractEnchantmentsFromItems([$newItem]);
                    if (!empty($newEnchantments)) {
                        (new HeldTrigger())->execute($player, null, $newEnchantments, "HELD", []);
                    }
                }
            }
        }
    }    

    public static function onArmorSlotChange(ArmorInventory $inventory, int $slot, Item $oldItem): void {
        $player = $inventory->getHolder();
        if (!$player instanceof Player) return;

        $newItem = $inventory->getItem($slot);
        
        if ($slot > 3) return;

        self::processArmorChange($player, $oldItem, $newItem, $slot);
    }

    public static  function processArmorChange(Player $player, Item $oldItem, Item $newItem, int $slot): void {
        if (!$oldItem->isNull()) {
            self::handleArmorItem($player, $oldItem, $slot, true);
        }

        if (!$newItem->isNull() && $newItem instanceof Armor) {
            self::handleArmorItem($player, $newItem, $slot, false);
        }
    }

    public static function handleArmorItem(Player $player, Item $item, int $slot, bool $remove): void {
        $enchantments = self::extractEnchantmentsFromItems([$item]);
        
        foreach ($enchantments as $enchantmentData) {
            if ($enchantmentData['applies_to'] !== 'Armor') continue;

            if ($remove) {
                EffectTracker::clearSlotEffects($player, $slot);
            } else {
                (new EffectStaticTrigger())->execute(
                    $player,
                    null,
                    [$enchantmentData],
                    "EFFECT_STATIC",
                    ['slot' => $slot, 'source' => 'armor']
                );
            }
        }
    }

    public static function isValidTrigger(array $enchantmentData, string $trigger): bool {
        return isset($enchantmentData['config']['type']) && 
            in_array($trigger, (array)$enchantmentData['config']['type'], true);
    }
}
