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
use ecstsy\MartianEnchantments\utils\registries\ConditionRegistry;
use ecstsy\MartianEnchantments\utils\registries\EffectRegistry;
use ecstsy\MartianEnchantments\utils\registries\TriggerRegistry;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\data\bedrock\EnchantmentIdMap;
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
        $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(CustomEnchantmentIds::FAKE_ENCH_ID)));
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
                
                $enchantmentsToApply[] = [
                    'name'   => $key,       
                    'level'  => $level,
                    'config' => $enchantmentConfig,
                ];
            }
        }
        
        return $enchantmentsToApply;
    }
    
    public static function getEffectsFromItems(array $items, string $trigger, Config $config): array {
        return self::extractEnchantments($items, function ($itemData) use ($trigger, $config) {
            $identifier = strtolower($itemData->getType()->getName());
            $configData = $config->get($identifier);
            if ($configData && in_array($trigger, $configData['type'], true)) {
                return $configData['levels'][$itemData->getLevel()]['effects'] ?? [];
            }
            return [];
        });
    }    

    public static function getConditionsFromItems(array $items, string $trigger, Config $config): array {
        return self::extractEnchantments($items, function ($itemData) use ($trigger, $config) {
            $identifier = strtolower($itemData->getType()->getName());
            $configData = $config->get($identifier);
            if ($configData && in_array($trigger, $configData['type'], true)) {
                return $configData['levels'][$itemData->getLevel()]['conditions'] ?? [];
            }
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
    
}