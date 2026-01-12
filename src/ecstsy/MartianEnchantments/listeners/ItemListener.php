<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\listeners;

use ecstsy\MartianEnchantments\enchantments\CustomEnchantmentManager;
use ecstsy\MartianEnchantments\enchantments\CustomEnchantments;
use ecstsy\MartianEnchantments\enchantments\Groups;
use ecstsy\MartianEnchantments\libs\ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianEnchantments\libs\ecstsy\MartianUtilities\utils\PlayerUtils;
use ecstsy\MartianEnchantments\Loader;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat as C;

final class ItemListener implements Listener {

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $tag = $item->getNamedTag();

        if ($tag->getTag("MartianEnchantments") !== null) {
            $event->cancel();
        }
    }

    public function onPlayerItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $namedTag = $item->getNamedTag();
        $tag = $namedTag->getCompoundTag("MartianEnchantments");
        $language = Loader::getInstance()->getLanguageManager();

        if ($tag === null) return;

        if (!$tag->getTag("martianItem")) return;

        switch($tag->getString("martianItem")) {
            case "enchantment-book":
                $event->cancel();
                $enchant = strtolower($tag->getString("enchant-no-color", ""));
                $enchantment = CustomEnchantments::getEnchantmentByName($enchant);

                if ($enchantment === null) {
                    $player->sendMessage(C::colorize("&r&cUnknown enchantment!"));
                    return;
                }

                $level = $tag->getInt("level") ?: null;

                if ($level === null) {
                    $player->sendMessage(C::colorize("&r&cInvalid level!"));
                    return;
                }

                $header = $language->getNested("interact.enchantment-book.header");
                if ($header !== null) {
                    $player->sendMessage(C::colorize($header));
                }

                $lines = (array)$language->getNested("interact.enchantment-book.lines", []);

                $replacements = [
                    "{enchant}" => CustomEnchantments::getEnchantmentDisplayName(
                        $enchantment->getName(),
                        Groups::translateGroupToColor($enchantment->getRarity())
                    ),
                    "{applies}" => implode(", ", $enchantment->getApplicableItems()),
                    "{max-level}" => $enchantment->getMaxLevel(),
                    "{roman-level}" => GeneralUtils::getRomanNumeral($enchantment->getMaxLevel()),
                    "{description}" => $enchantment->getDescription()
                ];

                foreach ($lines as $line) {
                    $player->sendMessage(C::colorize(str_replace(
                        array_keys($replacements),
                        array_values($replacements),
                        $line
                    )));
                }
                break;
            default:
                break;
        }
    }

    public function onDragDropEnchant(InventoryTransactionEvent $event): void {
        $transaction = $event->getTransaction();
        $actions = array_values($transaction->getActions());

        if (count($actions) !== 2) {
            return;
        }

        $loader = Loader::getInstance();
        $config = GeneralUtils::getConfiguration($loader, "config.yml");
        $language = $loader->getLanguageManager();

        if (!$config->getNested("settings.enchantment-book.drag-drop-application", true)) {
            return;
        }

        $bookAction = null;
        $itemAction = null;
        $bookTag = null;
        $item = null;

        foreach ($actions as $action) {
            if (!$action instanceof SlotChangeAction) continue;

            $source = $action->getSourceItem();
            $tag = $source->getNamedTag()->getCompoundTag("MartianEnchantments");

            if ($tag !== null && $tag->getString("martianItem", "") === "enchantment-book") {
                $bookAction = $action;
                $bookTag = $tag;
            } elseif ($source->getTypeId() !== VanillaItems::AIR()->getTypeId()) {
                $itemAction = $action;
                $item = $source;
            }
        }

        if ($bookAction === null || $itemAction === null || $bookTag === null || $item === null) {
            return;
        }

        $event->cancel();
        $player = $transaction->getSource();

        $enchantKey = strtolower($bookTag->getString("enchant-no-color", ""));
        $level = $bookTag->getInt("level", 0);
        $success = $bookTag->getInt("success", 100);
        $destroy = $bookTag->getInt("destroy", 0);

        if ($enchantKey === "" || $level <= 0) {
            $player->sendMessage(C::colorize($language->getNested("commands.enchantment-not-found")));
            return;
        }

        $enchantment = CustomEnchantments::getEnchantmentByName($enchantKey);
        if ($enchantment === null) {
            $player->sendMessage(C::colorize(str_replace("{enchant}", $enchantKey, $language->getNested("commands.enchantment-not-found"))));
            return;
        }

        $limEnabled = $config->getNested("settings.enchantLimitation.enabled", true);
        
        if ($limEnabled) {
            $limLore = (string) $config->getNested("settings.enchantLimitation.lore", "");
            $limNbtKey = (string) $config->getNested("settings.enchantLimitation.NBT-tag", "unmodifiable");

            $blocked = false;

            if ($limLore !== "") {
                foreach ($item->getLore() as $line) {
                    if (C::clean($line) === C::clean(C::colorize($limLore))) {
                        $blocked = true;
                        break;
                    }
                }
            }

            if (!$blocked && $limNbtKey !== "") {
                if ($item->getNamedTag()->getTag($limNbtKey) !== null) {
                    $blocked = true;
                }
            }

            if ($blocked) {
                $player->sendMessage(C::colorize($language->getNested("enchant-limitations.cannot-be-modified")));
                return;
            }
        }

        $itemTag = $item->getNamedTag()->getCompoundTag("MartianEnchantments");
        $maxSlots = $itemTag?->getInt("maxEnchantSlots", 9) ?? 9;

        $currentEnchants = CustomEnchantmentManager::getEnchantments($item);

        $combiningEnabled = $config->getNested("settings.combining.enabled", false);
        $upgradeEnabled = $config->getNested("settings.combining.chances.upgrade", true);
        $useChances = $config->getNested("settings.combining.chances.use-chances", true);

        $existingLevel = $currentEnchants[$enchantKey] ?? 0;

        /** =====================
         *  COMBINING / UPGRADING
         *  ===================== */
        if ($existingLevel > 0) {
            if (!$combiningEnabled) {
                $player->sendMessage(C::colorize($language->getNested("applying.already-applied")));
                return;
            }

            if (!$upgradeEnabled) {
                $player->sendMessage(C::colorize($language->getNested("combining.something-went-wrong")));
                return;
            }

            if ($existingLevel >= $enchantment->getMaxLevel()) {
                $player->sendMessage(C::colorize($language->getNested("combining.already-max-level")));
                return;
            }

            if ($level !== $existingLevel) {
                $player->sendMessage(
                    C::colorize(
                        str_replace(
                            ["{enchant}", "{level}"],
                            [$enchantment->getName(), GeneralUtils::getRomanNumeral($existingLevel)],
                            $language->getNested("combining.requires-same-level")
                        )
                    )
                );
                return;
            }

            $targetLevel = min($existingLevel + 1, $enchantment->getMaxLevel());

            if (!$useChances || mt_rand(1, 100) <= $success) {
                CustomEnchantmentManager::applyEnchantment($item, $enchantment, $targetLevel);

                PlayerUtils::playSound($player, $config->getNested("settings.applying.cosmetics.applied.sound", "random.levelup"));

                $bookAction->getInventory()->setItem($bookAction->getSlot(), VanillaItems::AIR());
                $itemAction->getInventory()->setItem($itemAction->getSlot(), $item);

                $player->sendMessage(C::colorize(str_replace(["{enchant}", "{level}"], [
                    CustomEnchantments::getEnchantmentDisplayName($enchantment->getName(), Groups::translateGroupToColor($enchantment->getRarity())),
                    GeneralUtils::getRomanNumeral($targetLevel)
                ], $language->getNested("combining.success"))));
                return;
            }

            PlayerUtils::playSound($player, $config->getNested("settings.applying.cosmetics.failed.sound", "random.anvil.break"));

            $bookAction->getInventory()->setItem($bookAction->getSlot(), VanillaItems::AIR());
            $player->sendMessage(C::colorize($language->getNested("combining.failure")));
            return;
        }

        /** =====================
         *  NORMAL APPLICATION
         *  ===================== */
        $enchCfg = GeneralUtils::getConfiguration(Loader::getInstance(), "enchantments.yml");

        $enchKeyLower = $enchantKey; 
        $enchData = $enchCfg->get($enchKeyLower);

        if (!is_array($enchData)) {
            foreach ($enchCfg->getAll() as $k => $v) {
                if (strtolower((string)$k) === $enchKeyLower) {
                    $enchData = is_array($v) ? $v : null;
                    break;
                }
            }
        }

        $settings = is_array($enchData) ? ($enchData["settings"] ?? []) : [];
        $required = array_map("strtolower", (array)($settings["required-enchants"] ?? []));
        $blockedWith = array_map("strtolower", (array)($settings["not-applyable-with"] ?? []));

        $currentKeys = array_map("strtolower", array_keys($currentEnchants));

        if ($required !== []) {
            foreach ($required as $req) {
                if ($req === "") continue;

                if (!in_array($req, $currentKeys, true)) {
                    $player->sendMessage(C::colorize(str_replace(["{enchant1}", "{enchant2}"], [$enchantment->getName(), $req], $language->getNested("applying.requires-enchant"))));
                    return;
                }
            }
        }

        if ($blockedWith !== []) {
            foreach ($blockedWith as $blocked) {
                if ($blocked === "") continue;

                if (in_array($blocked, $currentKeys, true)) {
                    $player->sendMessage(C::colorize(str_replace(["{enchant1}", "{enchant2}"], [$enchantment->getName(), $blocked], $language->getNested("applying.not-applicable-with"))));
                    return;
                }
            }
        }

        if (count($currentEnchants) >= $maxSlots) {
            $player->sendMessage(C::colorize($language->getNested("slots.limit-reached")));
            return;
        }

        if (!$enchantment->matches($item)) {
            $player->sendMessage(C::colorize($language->getNested("applying.wrong-material")));
            return;
        }

        if (mt_rand(1, 100) <= $success) {
            $newLevel = min($level, $enchantment->getMaxLevel());
            CustomEnchantmentManager::applyEnchantment($item, $enchantment, $newLevel);

            PlayerUtils::playSound($player, $config->getNested("settings.applying.cosmetics.applied.sound", "random.levelup"));

            $player->sendMessage(C::colorize($language->getNested("applying.applied")));

            $bookAction->getInventory()->setItem($bookAction->getSlot(), VanillaItems::AIR());
            $itemAction->getInventory()->setItem($itemAction->getSlot(), $item);
            return;
        }

        if (mt_rand(1, 100) <= $destroy) {
            $itemAction->getInventory()->setItem($itemAction->getSlot(), VanillaItems::AIR());
            $bookAction->getInventory()->setItem($bookAction->getSlot(), VanillaItems::AIR());

            $player->sendMessage(C::colorize($language->getNested("destroy.book-failed")));
            return;
        }

        $bookAction->getInventory()->setItem($bookAction->getSlot(), VanillaItems::AIR());
        $player->sendMessage(C::colorize($language->getNested("chances.book-failed")));
    }
}
