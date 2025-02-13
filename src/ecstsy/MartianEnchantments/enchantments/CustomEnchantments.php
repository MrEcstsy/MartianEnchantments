<?php

namespace ecstsy\MartianEnchantments\enchantments;

use CustomEnchantmentInstance;
use ecstsy\MartianEnchantments\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\event\EventPriority;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\utils\RegistryTrait;
use pocketmine\utils\TextFormat;

final class CustomEnchantments {
    use RegistryTrait;

    public static array $ids = [];

    public static array $rarities = [];

    /** @var CustomEnchant[] */
    public static array $enchants = [];

    protected static function setup(): void {
        SimplePacketHandler::createInterceptor(Loader::getInstance(), EventPriority::HIGH)
            ->interceptOutgoing(function (InventoryContentPacket $pk, NetworkSession $destination): bool {
                foreach ($pk->items as $i => $item) {
                    $pk->items[$i] = new ItemStackWrapper($item->getStackId(), self::display($item->getItemStack()));
                }
                return true;
            })
            ->interceptOutgoing(function (InventorySlotPacket $pk, NetworkSession $destination): bool {
                $pk->item = new ItemStackWrapper($pk->item->getStackId(), self::display($pk->item->getItemStack()));
                return true;
            })
            ->interceptOutgoing(function (InventoryTransactionPacket $pk, NetworkSession $destination): bool {
                $transaction = $pk->trData;
    
                foreach ($transaction->getActions() as $action) {
                    $action->oldItem = new ItemStackWrapper($action->oldItem->getStackId(), self::filter($action->oldItem->getItemStack()));
                    $action->newItem = new ItemStackWrapper($action->newItem->getStackId(), self::filter($action->newItem->getItemStack()));
                }
                return true;
            });
    
        self::registerEnchantments(); 
    }
    
    protected static function register(string $name, CustomEnchantment $enchantment): void {
        $key = strtoupper($name);
        
        self::$enchants[$key] = $enchantment;
        
        self::$rarities[$enchantment->getRarity()][] = $key;
        self::_registryRegister($name, $enchantment);
    }
    
    public static function getIdFromName(string $name) : ?int {
        return self::$ids[$name] ?? null;
    }

    public static function getAll() : array{
        /**
         * @var CustomEnchantment[] $result
         * @phpstan-var array<string, CustomEnchantment> $result
         */
        $result = self::_registryGetAll();
        return $result;
    }

    public static function display(ItemStack $itemStack): ItemStack {
        $item = TypeConverter::getInstance()->netItemStackToCore($itemStack);
        $root = $item->getNamedTag();
        $martianCES = $root->getCompoundTag("MartianCES");

        $additionalInformation = TextFormat::RESET . TextFormat::AQUA . $item->getName();
        $lore = [];
    
        if ($martianCES !== null) {
            foreach ($martianCES->getValue() as $enchantName => $levelTag) {
                $key = strtoupper($enchantName);
                if (!isset(self::getAll()[$key])) {
                    continue;
                }
                $enchantment = self::getEnchantmentByName($enchantName);
                if ($enchantment instanceof CustomEnchantment) {
                    $level = $levelTag->getValue();
                    $groupId = $enchantment->getRarity();
                    $color = Groups::translateGroupToColor($groupId);
                    $displayName = self::getEnchantmentDisplayName($enchantment->getName(), $color);
                    $enchantmentText = TextFormat::RESET . $displayName . " " . GeneralUtils::getRomanNumeral($level);
    
                    if ($item instanceof Armor) {
                        $lore[] = $enchantmentText;
                    } else {
                        $additionalInformation .= "\n" . $enchantmentText;
                    }
                }
            }
    
            self::preserveOriginalDisplayTag($item);
    
            if ($item instanceof Armor) {
                $existingLore = $item->getLore();
                $item->setLore(array_merge($existingLore, $lore));
            } else {
                $currentName = $item->getCustomName() !== "" ? $item->getCustomName() : $additionalInformation;
                $item->setCustomName($currentName);
            }
        }
    
        return TypeConverter::getInstance()->coreItemStackToNet($item);
    }

    /**
     * Retrieves the enchantment display name from the configuration.
     * 
     * @param string $enchantmentName
     * @param string $color
     * @return string
     */
    private static function getEnchantmentDisplayName(string $enchantmentName, string $color): string {
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "enchantments.yml");

        if ($config->exists($enchantmentName)) {
            $displayName = $config->getNested($enchantmentName . '.display');
            return str_replace('{group-color}', $color, $displayName);
        }

        return $enchantmentName;
    }

    /**
     * Saves the original display tag to prevent overwriting vanilla item properties.
     * 
     * @param Item $item
     * @return void
     */
    private static function preserveOriginalDisplayTag(Item $item): void {
        $namedTag = $item->getNamedTag();
        
        if ($namedTag->getTag(Item::TAG_DISPLAY)) {
            $namedTag->setTag("OriginalDisplayTag", $namedTag->getTag(Item::TAG_DISPLAY)->safeClone());
        }
    }

    public static function filter(ItemStack $itemStack): ItemStack {
        $item = TypeConverter::getInstance()->netItemStackToCore($itemStack);
        $tag = $item->getNamedTag();
        if (count($item->getEnchantments()) > 0) $tag->removeTag(Item::TAG_DISPLAY);

        if ($tag->getTag("OriginalDisplayTag") instanceof CompoundTag) {
            $tag->setTag(Item::TAG_DISPLAY, $tag->getTag("OriginalDisplayTag"));
            $tag->removeTag("OriginalDisplayTag");
        }
        $item->setNamedTag($tag);
        return TypeConverter::getInstance()->coreItemStackToNet($item);
    }

    /**
     * @param EnchantmentInstance[] $enchantments
     * @return EnchantmentInstance[]
     */
    public static function sortEnchantmentsByRarity(array $enchantments): array
    {
        usort($enchantments, function (EnchantmentInstance $enchantmentInstance, EnchantmentInstance $enchantmentInstanceB) {
            $type = $enchantmentInstance->getType();
            $typeB = $enchantmentInstanceB->getType();
    
            $rarityA = ($type instanceof CustomEnchantment) ? $type->getRarity() : 0; 
            $rarityB = ($typeB instanceof CustomEnchantment) ? $typeB->getRarity() : 0; 
    
            return $rarityB - $rarityA; 
        });
    
        return $enchantments;
    }
    
    protected static function registerEnchantments(): void {
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "enchantments.yml");
        $enchantments = $config->getAll();

        foreach ($enchantments as $enchantmentName => $enchantmentData) {
            if (!isset($enchantmentData['display'], $enchantmentData['description'], $enchantmentData['group'])) {
                Loader::getInstance()->getLogger()->warning("Missing essential fields for enchantment: $enchantmentName. Skipping.");
                continue;
            }
    
            $name = strval($enchantmentName);
            $descriptionArray = (array) $enchantmentData['description'];
            $description = implode("\n", $descriptionArray);
            $rarity = (int) Groups::getGroupId($enchantmentData['group']);
            $maxLevel = self::getMaxLevel($enchantmentData);
            $flags = self::parseFlags($enchantmentData['applies-to']);
            $enchantment = new CustomEnchantment($name, $rarity, $description, $maxLevel, $flags);

            self::register($name, $enchantment);
        }
    }

    protected static function getMaxLevel(array $enchantmentData): int {
        if (!isset($enchantmentData['levels']) || empty($enchantmentData['levels'])) {
            throw new \InvalidArgumentException("Enchantment '" . $enchantmentData['display'] . "' does not define any levels.");
        }
    
        $levels = $enchantmentData['levels'];
        $maxLevel = max(array_keys($levels));
        return $maxLevel;
    }

    protected static function parseFlags(string $appliesTo): int {
        switch (strtolower($appliesTo)) {
            case 'Pickaxe':
                return ItemFlags::PICKAXE;
            case 'Sword':
                return ItemFlags::SWORD;
            case 'Chestplate':
                return ItemFlags::TORSO;
            case 'Leggings':
                return ItemFlags::LEGS;
            case 'Boots':
                return ItemFlags::FEET;
            case 'All':
                return ItemFlags::ALL;
            case 'Armor':
                return ItemFlags::ARMOR;
            case 'Axe':
                return ItemFlags::AXE;
            case 'Hoe':
                return ItemFlags::HOE;
            case 'Shovel':
                return ItemFlags::SHOVEL;
            case 'Shears':
                return ItemFlags::SHEARS;
            case 'Bow':
                return ItemFlags::BOW;
            case 'Trident':
                return ItemFlags::TRIDENT;
            default:
                return ItemFlags::NONE;
        }
    }

    public static function getEnchantmentByName(string $name): ?CustomEnchantment {
        $key = strtoupper($name);
        return self::$enchants[$key] ?? null;
    }
    
}