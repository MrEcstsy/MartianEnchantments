<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\commands\subcommands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\MartianEnchantments\enchantments\CustomEnchantment;
use ecstsy\MartianEnchantments\enchantments\CustomEnchantmentManager;
use ecstsy\MartianEnchantments\enchantments\CustomEnchantments;
use ecstsy\MartianEnchantments\Loader;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class EnchantSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->setPermissionMessage(Loader::getInstance()->getLanguageManager()->getNested("commands.no-permission"));
        $this->registerArgument(0, new RawStringArgument("enchantment", true));
        $this->registerArgument(1, new IntegerArgument("level", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only!"));
            return;
        }

        if (!isset($args["enchantment"]) || !isset($args["level"])) {
            $sender->sendMessage(C::colorize(str_replace("{usage}", $this->getUsage(), Loader::getInstance()->getLanguageManager()->getNested("commands.invalid-usage"))));
            return;
        }

        $enchantName = $args["enchantment"];
        $level = (int)$args["level"];
        $item = $sender->getInventory()->getItemInHand();
    
        $enchant = CustomEnchantments::getEnchantmentByName($enchantName) ?? null;
        if ($enchant === null || !($enchant instanceof CustomEnchantment)) {
            $sender->sendMessage(C::colorize(str_replace("{enchant}", $enchantName, Loader::getInstance()->getLanguageManager()->getNested("commands.invalid-enchant"))));
            return;
        }
    
        if ($item->getTypeId() === VanillaItems::AIR()->getTypeId()) {
            $sender->sendMessage(C::colorize(Loader::getInstance()->getLanguageManager()->getNested("commands.not-holding-item")));
            return;
        }
    
        if ($level < 1 || $level > $enchant->getMaxLevel()) {
            $maxLevel = $enchant->getMaxLevel();
            $levelsArray = range(1, $maxLevel);
            $levels = implode(", ", $levelsArray);
            $sender->sendMessage(C::colorize(str_replace("{levels}", $levels, Loader::getInstance()->getLanguageManager()->getNested("commands.invalid-level"))));
            return;
        }

        $existingEnchantment = $item->getNamedTag()->getCompoundTag("MartianCES")?->getInt($enchant->getName(), -1);
        if ($existingEnchantment !== -1) {
            if ($existingEnchantment === $level) {
                CustomEnchantmentManager::removeEnchantment($item, $enchant);
                $sender->sendMessage(C::colorize(str_replace("{enchant}", $enchant->getName(), Loader::getInstance()->getLanguageManager()->getNested("commands.main.enchant.removed"))));
            } else {
                CustomEnchantmentManager::applyEnchantment($item, $enchant, $level);
                $message = $level > $existingEnchantment
                    ? str_replace(["{enchant}", "{previous-level}", "{level}"], [$enchant->getName(), $existingEnchantment, $level], Loader::getInstance()->getLanguageManager()->getNested("commands.main.enchant.upgraded"))
                    : str_replace(["{enchant}", "{previous-level}", "{level}"], [$enchant->getName(), $existingEnchantment, $level], Loader::getInstance()->getLanguageManager()->getNested("commands.main.enchant.downgraded"));
                $sender->sendMessage(C::colorize($message));
            }
        } else {
            CustomEnchantmentManager::applyEnchantment($item, $enchant, $level);
            $sender->sendMessage(C::colorize(str_replace("{enchant}", $enchant->getName(), Loader::getInstance()->getLanguageManager()->getNested("commands.main.enchant.added"))));
        }
    
        $sender->getInventory()->setItemInHand($item);
    }

    public function getUsage(): string {
        return "/me enchant <enchantment> <level>";
    }

    public function getPermission(): ?string
    {
        return "martianenchantments.enchant";
    }
}