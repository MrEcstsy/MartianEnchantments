<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\utils;

use ecstsy\MartianEnchantments\commands\MECommand;
use ecstsy\MartianEnchantments\conditions\BlockBelowCondition;
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
use ecstsy\MartianEnchantments\enchantments\CustomEnchantments;
use ecstsy\MartianEnchantments\enchantments\Groups;
use ecstsy\MartianEnchantments\libs\ecstsy\MartianUtilities\managers\LanguageManager;
use ecstsy\MartianEnchantments\libs\ecstsy\MartianUtilities\UtilityHandler;
use ecstsy\MartianEnchantments\Loader;
use ecstsy\MartianEnchantments\triggers\EffectStaticTrigger;
use ecstsy\MartianEnchantments\triggers\GenericTrigger;
use ecstsy\MartianEnchantments\triggers\HeldTrigger;
use ecstsy\MartianEnchantments\utils\registries\ConditionRegistry;
use ecstsy\MartianEnchantments\utils\registries\EffectRegistry;
use ecstsy\MartianEnchantments\libs\ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianEnchantments\libs\JackMD\ConfigUpdater\ConfigUpdater;
use ecstsy\MartianEnchantments\libs\muqsit\invmenu\InvMenuHandler;
use ecstsy\MartianEnchantments\listeners\EnchantmentListener;
use ecstsy\MartianEnchantments\listeners\ItemListener;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\entity\Entity;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;

final class Utils {
    public const FAKE_ENCH_ID = -1;

    public static LanguageManager $languageManager;
    
    public const CFG_VERSIONS = [
        "config" => 2,
        "en-us" => 2,
    ];

    public static function initAll(): void {
        $loader = Loader::getInstance();
        $files = ["enchantments.yml", "groups.yml"];
        
        foreach ($files as $file) {
            $loader->saveResource($file);
        }

        ConfigUpdater::checkUpdate($loader, $loader->getConfig(), "version", self::CFG_VERSIONS["config"]);

        self::saveAllFilesInDirectory($loader, "locale", [
            "en-us.yml",
            "es-es.yml"
        ]);

        self::saveAllFilesInDirectory($loader, "armorSets", [
            "starter.yml",
            "legendary.yml"
        ]);

        self::saveAllFilesInDirectory($loader, "menus", [
            "enchanter.yml",
            "tinkerer.yml"
        ]);

        $config = GeneralUtils::getConfiguration($loader, "config.yml");
        $language = $config->getNested("settings.language");

        self::$languageManager = new LanguageManager($loader, $language);
        $loader->getLogger()->info("MartianEnchantments enabled with language: " . $language);

        $loader->getServer()->getCommandMap()->unregister($loader->getServer()->getCommandMap()->getCommand("me"));

        $loader->getServer()->getCommandMap()->registerAll("MartianEnchantments", [
            new MECommand($loader, "martianenchantments", "View the martian enchantments commands", ["mes", "me"]),
        ]);

        $listeners = [
            new EnchantmentListener($loader),
            new ItemListener(),
        ];

        foreach ($listeners as $listener) {
            $loader->getServer()->getPluginManager()->registerEvents($listener, $loader);
        }

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($loader);
        }

        if(!UtilityHandler::isRegistered()) {
            UtilityHandler::register($loader);
        }
        
        if ($config->getNested("economy.enabled") === true) {
            // implement
        } else {
            $loader->getLogger()->warning("Economy support is disabled.");
        }
        
        Utils::initRegistries();
        Groups::init();
        CustomEnchantments::getAll();
    }

    public static function saveAllFilesInDirectory(PluginBase $plugin, string $directory, array $files): void {
        foreach ($files as $file) {
            $path = "$directory/$file";

            try {
                $plugin->saveResource($path);
            } catch (\Throwable $e) {
                $plugin->getLogger()->warning("Failed to save resource: $path");
            }
        }
    }

    public static function initRegistries(): void {
        $conditions = [
            //"VICTIM_HEALTH" => new VictimHealthCondition(),
            "IS_SNEAKING" => new IsSneakingCondition(),
            "IS_HOLDING" => new IsHoldingCondition(),
            "BLOCK_BELOW" => new BlockBelowCondition(),
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
        if (!$player instanceof Player || $slot > 3) return;

        $newItem = $inventory->getItem($slot);
        self::processArmorChange($player, $oldItem, $newItem, $slot);
    }

    public static function processArmorChange(Player $player, Item $oldItem, Item $newItem, int $slot): void {
        if (!$oldItem->isNull()) {
            EffectTracker::clearSlotEffects($player, $slot); // Remove old effects regardless of enchant type
        }

        if (!$newItem->isNull() && $newItem instanceof Armor) {
            $enchantments = self::extractEnchantmentsFromItems([$newItem]);

            $staticEnchants = array_filter($enchantments, function(array $enchantment): bool {
                return in_array("EFFECT_STATIC", (array)($enchantment['config']['type'] ?? []), true);
            });

            if (!empty($staticEnchants)) {
                (new EffectStaticTrigger())->execute(
                    $player,
                    null,
                    $staticEnchants,
                    "EFFECT_STATIC",
                    ['slot' => $slot, 'source' => 'armor']
                );
            }
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

    public static function handleArrowHitEnchants(Entity $attacker, ?Entity $victim, array $items): void {
        $enchants = Utils::extractEnchantmentsFromItems($items);
        $filtered = [];
        $extra = [];

        foreach ($enchants as $cfg) {
            $types = $cfg['config']['type'] ?? [];

            if (!in_array("ARROW_HIT", $types, true)) {
                continue;
            }

            $level = $cfg['level'] ?? 1;
            $chance = $cfg['config']['levels'][$level]['chance'] ?? 100;
            $name = $cfg['name'] ?? 'unknown';

            $extra = [
                'enchant-level' => $level,
                'chance' => $chance,
                'enchant-name' => $name,
            ];

            $filtered[] = $cfg;
        }

        if (!empty($filtered)) {
            (new GenericTrigger())->execute($attacker, $victim, $filtered, 'ARROW_HIT', $extra);
        }
    }

    public static function resolveBookChances(array $config, ?int $forcedSuccess, ?int $forcedDestroy): array {

        if ($forcedSuccess !== null && $forcedDestroy !== null) {
            return [$forcedSuccess, $forcedDestroy];
        }

        if ($config['random'] ?? false) {
            return [mt_rand(0, 100), mt_rand(0, 100)];
        }

        $success = explode("-", (string)($config['success'] ?? "100"));
        $destroy = explode("-", (string)($config['destroy'] ?? "0"));

        $successChance = isset($success[1]) ? mt_rand((int)$success[0], (int)$success[1]) : (int)$success[0];
        $destroyChance = isset($destroy[1]) ? mt_rand((int)$destroy[0], (int)$destroy[1]) : (int)$destroy[0];

        return [$successChance, $destroyChance];
    }
}
