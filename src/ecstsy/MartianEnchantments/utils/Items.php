<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\utils;

use ecstsy\MartianEnchantments\enchantments\CustomEnchantment;
use ecstsy\MartianEnchantments\enchantments\Groups;
use ecstsy\MartianEnchantments\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

final class Items {

    public static function createEnchantmentBook(Enchantment $enchantment, int $level = 1, ?int $forcedSuccessChance = null, ?int $forcedDestroyChance = null): ?Item {
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $bookConfig = $config->getNested("enchantment-book", []);
        $bookItemType = $bookConfig['item']['type'] ?? 'enchanted_book';
        $chancesConfig = $config->getNested("chances", []);
        $enchantmentConfig = GeneralUtils::getConfiguration(Loader::getInstance(), "enchantments.yml")->getAll();
        $item = StringToItemParser::getInstance()->parse($bookItemType)->setCount(1);
    
        $rarity = $enchantment->getRarity();
        $color = Groups::translateGroupToColor($rarity);
        $groupName = Groups::getGroupName($rarity);
    
        $enchantmentData = $enchantmentConfig[$enchantment->getName()];
        $name = str_replace(
            ['{group-color}', '{enchant-no-color}', '{level}'],
            [$color, str_replace("{group-color}", $color, $enchantmentData['display']), GeneralUtils::getRomanNumeral($level)],
            $bookConfig['name']
        );
    
        $descriptionLines = [];
        $appliesTo = "";
        if ($enchantment instanceof CustomEnchantment) {
            $enchantConfig = GeneralUtils::getConfiguration(Loader::getInstance(), "enchantments.yml");
            $enchantData = $enchantConfig->get($enchantment->getName(), []);
            $descriptionLines = $enchantData['description'] ?? [];
            $appliesTo = $enchantData['applies-to'] ?? "Unknown";
        }
    
        $descriptionText = implode("\n", $descriptionLines);  
    
        $loreLines = [];
        foreach ($bookConfig['lore'] as $line) {
            $line = str_replace(
                ['{group-color}', '{enchant-no-color}', '{level}', '{success}', '{destroy}', '{applies-to}', '{max-level}', '{description}'],
                [$color, ucfirst($enchantment->getName()), GeneralUtils::getRomanNumeral($level), '{success}', '{destroy}', $appliesTo, $enchantment->getMaxLevel(), $descriptionText],
                $line
            );
            $loreLines[] = TextFormat::colorize($line);
        }
    
        $item->setCustomName(TextFormat::colorize($name));
        $item->setLore($loreLines);

        $root = $item->getNamedTag();
        $bookTag = new CompoundTag();

        $bookTag->setString("enchant_book", strtolower($enchantment->getName()));
        $bookTag->setInt("level", $level);
    
        if ($forcedSuccessChance !== null && $forcedDestroyChance !== null) {
            $successChance = $forcedSuccessChance;
            $destroyChance = $forcedDestroyChance;
        } elseif ($chancesConfig['random'] ?? false) {
            $successChance = mt_rand(0, 100);
            $destroyChance = mt_rand(0, 100);
        } else {
            $successRange = explode("-", $chancesConfig['success'] ?? "100");
            $destroyRange = explode("-", $chancesConfig['destroy'] ?? "0");
    
            $successChance = isset($successRange[1]) ? mt_rand((int)$successRange[0], (int)$successRange[1]) : (int)$successRange[0];
            $destroyChance = isset($destroyRange[1]) ? mt_rand((int)$destroyRange[0], (int)$destroyRange[1]) : (int)$destroyRange[0];
        }
    
        $bookTag->setInt("successrate", $successChance);
        $bookTag->setInt("destroyrate", $destroyChance);
    
        $root->setTag("MartianEnchantments", $bookTag);

        foreach ($loreLines as &$line) {
            $line = str_replace(
                ['{success}', '{destroy}'],
                [$successChance, $destroyChance],
                $line
            );
        }
        $item->setLore($loreLines);
    
        return $item;
    }   

    public static function createOrb(string $type, int $max, int $success = 100, int $amount = 1): ?Item {
        $item = VanillaItems::AIR()->setCount($amount);
        $cfg = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $defaultMax = $cfg->getNested("slots.max"); 
        $new = max(0, $max - $defaultMax);
    
        switch (strtolower($type)) {
            case "weapon":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.orb.weapon.material"))->setCount($amount);
    
                $item->setCustomName(TextFormat::colorize(str_replace('{max}', (string)$max, $cfg->getNested("items.orb.weapon.name"))));
    
                $lore = $cfg->getNested("items.orb.weapon.lore");
                $item->setLore(array_map(function ($line) use ($max, $new, $success) {
                    return TextFormat::colorize(str_replace(["{max}", "{new}", "{success}"], [$max, $new, $success], $line));
                }, $lore));
    
                $root = $item->getNamedTag();
                $orbTag = new CompoundTag();

                $orbTag->setString("advancedscrolls", "weapon");
                $orbTag->setInt("max", $max);
                $orbTag->setInt("new", $new);
                $orbTag->setInt("success", $success);

                $root->setTag("MartianEnchantments", $orbTag);
                break;
            case "armor":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.orb.armor.material"))->setCount($amount);
                
                $item->setCustomName(TextFormat::colorize(str_replace('{max}', (string)$max, $cfg->getNested("items.orb.armor.name"))));
    
                $lore = $cfg->getNested("items.orb.armor.lore");
                $item->setLore(array_map(function ($line) use ($max, $new, $success) {
                    return TextFormat::colorize(str_replace(["{max}", "{new}", "{success}"], [$max, $new, $success], $line));
                }, $lore));
                
                $root = $item->getNamedTag();
                $orbTag = new CompoundTag();

                $orbTag->setString("advancedscrolls", "armor");
                $orbTag->setInt("max", $max);
                $orbTag->setInt("new", $new);
                $orbTag->setInt("success", $success);

                $root->setTag("MartianEnchantments", $orbTag);
                break;
            case "tool":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.orb.tool.material"))->setCount($amount);

                $item->setCustomName(TextFormat::colorize(str_replace('{max}', (string)$max, $cfg->getNested("items.orb.tool.name"))));

                $lore = $cfg->getNested("items.orb.tool.lore");
                $item->setLore(array_map(function ($line) use ($max, $new, $success) {
                    return TextFormat::colorize(str_replace(["{max}", "{new}", "{success}"], [$max, $new, $success], $line));
                }, $lore));

                $root = $item->getNamedTag();
                $orbTag = new CompoundTag();

                $orbTag->setString("advancedscrolls", "tool");
                $orbTag->setInt("max", $max);
                $orbTag->setInt("new", $new);
                $orbTag->setInt("success", $success);

                $root->setTag("MartianEnchantments", $orbTag);
                break;
            default:
        }
    
        return $item;
    }

    public static function createScroll(string $scroll, int $amount = 1, int $rate = 100): ?Item {
        $item = VanillaItems::AIR()->setCount($amount);
        $cfg = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        switch ($scroll) {
            case "whitescroll":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.white-scroll.type"));

                if ($item !== null) {
                    $item->setCount($amount);
                    $item->setCustomName(TextFormat::colorize($cfg->getNested("items.white-scroll.name")));

                    $lore = $cfg->getNested("items.white-scroll.lore");
                    $item->setLore(array_map(function ($line) {
                        return TextFormat::colorize($line);
                    }, $lore));

                    $root = $item->getNamedTag();
                    $scrollTag = new CompoundTag();

                    $scrollTag->setString("advancedscrolls", "whitescroll");
                    $root->setTag("MartianEnchantments", $scrollTag);
                } else {
                    Loader::getInstance()->getLogger()->warning("Invalid parsed item for scroll: $scroll");
                }   
                break;
            case "blackscroll":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.black-scroll.type"))->setCount($amount);    
                $item->setCustomName(TextFormat::colorize($cfg->getNested("items.black-scroll.name")));
        
                $lore = $cfg->getNested("items.black-scroll.lore");
                $item->setLore(array_map(function ($line) use ($rate) {
                    return TextFormat::colorize(str_replace("{success}", (string)$rate, $line));
                }, $lore));
        
                $root = $item->getNamedTag();
                $scrollTag = new CompoundTag();

                $scrollTag->setString("advancedscrolls", "blackscroll");
                $scrollTag->setInt("blackscroll-success", $rate);

                $root->setTag("MartianEnchantments", $scrollTag);
                break;
            case "transmog":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.transmogscroll.type"))->setCount($amount);

                $item->setCustomName(TextFormat::colorize($cfg->getNested("items.transmogscroll.name")));

                $item->setLore(array_map(function ($line) {
                    return TextFormat::colorize($line);
                }, $cfg->getNested("items.transmogscroll.lore")));

                $root = $item->getNamedTag();
                $scrollTag = new CompoundTag();

                $scrollTag->setString("advancedscrolls", "transmog");

                $root->setTag("MartianEnchantments", $scrollTag);
                break;
            case "soulgem":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.soulgem.type"))->setCount($amount);

                $item->setCustomName(TextFormat::colorize($cfg->getNested("items.soulgem.name")));

                $lore = $cfg->getNested("items.soulgem.lore");
                $item->setLore(array_map(function ($line) {
                    return TextFormat::colorize($line);
                }, $lore));

                $root = $item->getNamedTag();
                $scrollTag = new CompoundTag();

                $scrollTag->setString("advancedscrolls", "soulgem");
                $scrollTag->setInt("souls", $rate);

                $root->setTag("MartianEnchantments", $scrollTag);
                break;
            case "itemnametag":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.itemnametag.type"))->setCount($amount);
                
                $item->setCustomName(TextFormat::colorize($cfg->getNested("items.itemnametag.name")));

                $lore = $cfg->getNested("items.itemnametag.lore");
                $item->setLore(array_map(function ($line) {
                    return TextFormat::colorize($line);
                }, $lore));

                $root = $item->getNamedTag();
                $scrollTag = new CompoundTag();

                $scrollTag->setString("advancedscrolls", "itemnametag");

                $root->setTag("MartianEnchantments", $scrollTag);
                break;
            case "blocktrak":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.blocktrak.type"))->setCount($amount);

                $item->setCustomName(TextFormat::colorize($cfg->getNested("items.blocktrak.name")));

                $lore = $cfg->getNested("items.blocktrak.lore");
                $item->setLore(array_map(function ($line) {
                    return TextFormat::colorize($line);
                }, $lore));

                $root = $item->getNamedTag();
                $scrollTag = new CompoundTag();

                $scrollTag->setString("advancedscrolls", "blocktrak");

                $root->setTag("MartianEnchantments", $scrollTag);
                break;
            case "stattrak":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.stattrak.type"))->setCount($amount);
                
                $item->setCustomName(TextFormat::colorize($cfg->getNested("items.stattrak.name")));

                $lore = $cfg->getNested("items.stattrak.lore");
                $item->setLore(array_map(function ($line) {
                    return TextFormat::colorize($line);
                }, $lore));

                $root = $item->getNamedTag();
                $scrollTag = new CompoundTag();

                $scrollTag->setString("advancedscrolls", "stattrak");

                $root->setTag("MartianEnchantments", $scrollTag);
                break;
            case "mobtrak":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.mobtrak.type"))->setCount($amount);
                
                $item->setCustomName(TextFormat::colorize($cfg->getNested("items.mobtrak.name")));

                $lore = $cfg->getNested("items.mobtrak.lore");
                $item->setLore(array_map(function ($line) {
                    return TextFormat::colorize($line);
                }, $lore));

                $root = $item->getNamedTag();
                $scrollTag = new CompoundTag();

                $scrollTag->setString("advancedscrolls", "mobtrak");

                $root->setTag("MartianEnchantments", $scrollTag);
                break;
            case "holywhitescroll":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.holywhitescroll.type"))->setCount($amount);

                $item->setCustomName(TextFormat::colorize($cfg->getNested("items.holywhitescroll.name")));

                $lore = $cfg->getNested("items.holywhitescroll.lore");
                $item->setLore(array_map(function ($line) {
                    return TextFormat::colorize($line);
                }, $lore));

                $root = $item->getNamedTag();
                $scrollTag = new CompoundTag();

                $scrollTag->setString("advancedscrolls", "holywhitescroll");

                $root->setTag("MartianEnchantments", $scrollTag);
                break;
            case "mystery":
                $item = StringToItemParser::getInstance()->parse($cfg->getNested("items.mystery-dust.type"))->setCount($amount);  
                
                
        }

        return $item;
    }

    public static function createRCBook(string $group, int $amount = 1): Item {
        try {

            $groupId = Groups::getGroupId($group);

            if ($groupId === null) {
                throw new \InvalidArgumentException("Invalid group ID for group: $group");
            }

            $groupId = Groups::getGroupId($group);
            $color = Groups::translateGroupToColor($groupId);
            $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");

            if ($config === null) {
                throw new \RuntimeException("Configuration file not found.");
            }

            $bookConfig = $config->getNested("enchanter-books");

            if ($bookConfig === null) {
                throw new \RuntimeException("Enchanter books configuration not found.");
            }

            $type = $bookConfig['type'];
            $name = $bookConfig['name'];
            $lore = $bookConfig['lore'];
                
            if ($type === null || $name === null || $lore === null) {
                throw new \RuntimeException("Enchanter book type, name, or lore is missing in the configuration.");
            }

            $name = str_replace(['{group-color}', '{group-name}'], [$color, ucfirst($group)], $name);
            $lore = array_map(function($line) use ($color, $group) {
                return str_replace(['{group-color}', '{group-name}'], [$color, ucfirst($group)], $line);
            }, $lore);
                
            $item = StringToItemParser::getInstance()->parse($type);

            if ($item === null) {
                throw new \RuntimeException("Failed to parse item type: $type");
            }

            $item->setCount($amount);
            $item->setCustomName(TextFormat::colorize($name));
            $item->setLore(array_map(function($line) {
                return TextFormat::colorize($line);
            }, $lore));
                
            if ($bookConfig['force-glow']) {
                Utils::applyDisplayEnchant($item);
            }
                
            $root = $item->getNamedTag();
            $rcBookTag = new CompoundTag();

            $rcBookTag->setString("random_book", strtoupper($group)); 

            $root->setTag("MartianEnchantments", $rcBookTag);
            
            return $item;

        } catch (\InvalidArgumentException $e) {
            Loader::getInstance()->getLogger()->error($e->getMessage());
        } catch (\RuntimeException $e) {
            Loader::getInstance()->getLogger()->error($e->getMessage());
        } catch (\Exception $e) {
            Loader::getInstance()->getLogger()->error("An unexpected error occurred: " . $e->getMessage());
        }

        return VanillaItems::AIR();
    }

    
}