<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\commands\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\MartianEnchantments\enchantments\CustomEnchantment;
use ecstsy\MartianEnchantments\Loader;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class UnenchantSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("enchantment", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game command!"));
            return;
        }

        $enchant = isset($args["enchantment"]) ? $args["enchantment"] : null;
        $item = $sender->getInventory()->getItemInHand();

        $enchantment = StringToEnchantmentParser::getInstance()->parse($enchant);
        if ($enchantment !== null) {
            if ($enchantment instanceof CustomEnchantment) {
                if ($item->getTypeId() !== VanillaItems::AIR()->getTypeId()) {
                    if ($item->hasEnchantment($enchantment)) {
                        $item->removeEnchantment($enchantment);
                        $sender->getInventory()->setItemInHand($item);
                        $sender->sendMessage(C::colorize(str_replace("{enchant}", ucfirst($enchantment->getName()), Loader::getInstance()->getLanguageManager()->getNested("commands.main.unenchant.success"))));
                    } else {
                        $sender->sendMessage(C::colorize(str_replace("{enchant}", ucfirst($enchantment->getName()), Loader::getInstance()->getLanguageManager()->getNested("commands.main.unenchant.does-not-have-enchant"))));
                    }
                } else {
                    $sender->sendMessage(C::colorize(Loader::getInstance()->getLanguageManager()->getNested("commands.main.unenchant.not-holding-item")));
                }
            } else {
                $sender->sendMessage(C::colorize(str_replace("{enchant}", $enchant, Loader::getInstance()->getLanguageManager()->getNested("commands.main.unenchant.invalid-enchantment"))));
            }
        }
    }

    public function getPermission(): ?string
    {
        return "martianenchantments.unenchant";
    }
}