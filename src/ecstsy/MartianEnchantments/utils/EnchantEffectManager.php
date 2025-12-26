<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\utils;

use ecstsy\MartianEnchantments\utils\managers\EnchantmentDisableManager;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class EnchantEffectManager {
    use TriggerHelper;

    public function onArmorSlotChange(ArmorInventory $inventory, int $slot, Item $oldItem): void {
        $player = $inventory->getHolder();
        if (!$player instanceof Player) return;

        $newItem = $inventory->getItem($slot);
        if ($newItem->equals($oldItem, false)) return;

        $desired = $this->collectArmorDesired($player);
        $this->syncPotionEffects($player, $desired);
    }

    public function onInventorySlotChange(PlayerInventory $inventory, int $slot, Item $oldItem): void {
        $player = $inventory->getHolder();
        if (!$player instanceof Player) return;

        $heldSlot = $player->getInventory()->getHeldItemIndex();
        if ($slot !== $heldSlot) return;

        $newItem = $inventory->getItem($slot);
        if ($newItem->equals($oldItem, false)) return;

        $desired = $this->collectHeldDesired($player, $newItem);
        $this->syncPotionEffects($player, $desired);
    }

    public function updateHeldItemEffects(Player $player, Item $oldItem, Item $newItem): void {
        $desired = $this->collectHeldDesired($player, $newItem);
        $this->syncPotionEffects($player, $desired);
    }

    /**
     * Build desired potion effects from ALL armor pieces (EFFECT_STATIC only).
     *
     * @return array<string, array{effect:\pocketmine\entity\effect\Effect, amp:int}>
     */
    private function collectArmorDesired(Player $player): array {
        $desired = [];

        foreach ($player->getArmorInventory()->getContents() as $item) {
            if ($item->isNull()) continue;

            foreach (Utils::extractEnchantmentsFromItems([$item]) as $enchant) {
                $types = (array)($enchant['config']['type'] ?? []);
                if (!in_array("EFFECT_STATIC", $types, true)) continue;

                $enchantName = (string)$enchant['name'];
                $level = (int)($enchant['level'] ?? 1);

                if (EnchantmentDisableManager::isEnchantmentDisabled($enchantName, $player->getName())) {
                    continue;
                }

                $levelCfg = $enchant['config']['levels'][$level] ?? [];
                foreach (($levelCfg['effects'] ?? []) as $effectData) {
                    if (($effectData['type'] ?? '') !== 'ADD_POTION') continue;

                    $potionKey = strtolower((string)($effectData['potion'] ?? ''));
                    if ($potionKey === '') continue;

                    $effect = StringToEffectParser::getInstance()->parse($potionKey);
                    if ($effect === null) continue;

                    $amp = (int)($effectData['amplifier'] ?? 0);

                    if (!isset($desired[$potionKey]) || $desired[$potionKey]['amp'] < $amp) {
                        $desired[$potionKey] = ['effect' => $effect, 'amp' => $amp];
                    }
                }
            }
        }

        return $desired;
    }

    /**
     * Build desired potion effects from HELD item (HELD only) with conditions.
     *
     * @return array<string, array{effect:\pocketmine\entity\effect\Effect, amp:int}>
     */
    private function collectHeldDesired(Player $player, Item $heldItem): array {
        $desired = [];

        if ($heldItem->isNull()) return $desired;

        foreach (Utils::extractEnchantmentsFromItems([$heldItem]) as $enchant) {
            $types = (array)($enchant['config']['type'] ?? []);
            if (!in_array("HELD", $types, true)) continue;

            $enchantName = (string)$enchant['name'];
            $level = (int)($enchant['level'] ?? 1);

            if (EnchantmentDisableManager::isEnchantmentDisabled($enchantName, $player->getName())) {
                continue;
            }

            $levelCfg = $enchant['config']['levels'][$level] ?? [];

            foreach (($levelCfg['conditions'] ?? []) as $condition) {
                $extra = [];
                if (!$this->handleConditions((array)$condition, $player, null, 'HELD', $extra)) {
                    continue 2;
                }
            }

            foreach (($levelCfg['effects'] ?? []) as $effectData) {
                if (($effectData['type'] ?? '') !== 'ADD_POTION') continue;

                $potionKey = strtolower((string)($effectData['potion'] ?? ''));
                if ($potionKey === '') continue;

                $effect = StringToEffectParser::getInstance()->parse($potionKey);
                if ($effect === null) continue;

                $amp = (int)($effectData['amplifier'] ?? 0);

                if (!isset($desired[$potionKey]) || $desired[$potionKey]['amp'] < $amp) {
                    $desired[$potionKey] = ['effect' => $effect, 'amp' => $amp];
                }
            }
        }

        return $desired;
    }

    /**
     * Sync player potion effects to exactly match $desired.
     *
     * NOTE: This will remove any potion effects that are not in $desired.
     * If you also have other plugins giving effects, I can add a tag/filter.
     */
    private function syncPotionEffects(Player $player, array $desired): void {
        $effects = $player->getEffects();

        foreach ($effects->all() as $instance) {
            $type = $instance->getType();
            $key = strtolower($type->getName()->getText());

            if (!isset($desired[$key])) {
                $effects->remove($type);
            }
        }

        foreach ($desired as $data) {
            $effect = $data['effect'];
            $amp = $data['amp'];

            $current = $effects->get($effect);
            if ($current === null || $current->getAmplifier() < $amp) {
                $effects->add(new EffectInstance($effect, 2147483647, $amp, false));
            }
        }
    }
}
