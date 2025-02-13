<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\commands\subcommands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\MartianEnchantments\Loader;
use ecstsy\MartianEnchantments\utils\Items;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\utils\TextFormat as C;
use pocketmine\player\Player;

final class GiveBookSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->setPermissionMessage(Loader::getInstance()->getLanguageManager()->getNested("commands.no-permission"));
        $this->registerArgument(0, new RawStringArgument("name", false));
        $this->registerArgument(1, new RawStringArgument("enchantment", false));
        $this->registerArgument(2, new IntegerArgument("level", false));
        $this->registerArgument(3, new IntegerArgument("amount", false));
        $this->registerArgument(4, new IntegerArgument("success", false));
        $this->registerArgument(5, new IntegerArgument("destroy", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only!"));
            return;
        }

        $player = isset($args["name"]) ? PlayerUtils::getPlayerByPrefix($args["name"]) : null;
        $enchant = isset($args["enchantment"]) ? $args["enchantment"] : null;
        $level = isset($args["level"]) ? $args["level"] : null;
        $amount = isset($args["amount"]) ? $args["amount"] : null;
        $success = isset($args["success"]) ? $args["success"] : null;
        $destroy = isset($args["destroy"]) ? $args["destroy"] : null;

        if ($player !== null) {
            if ($enchant !== null) {
                if ($level !== null) {
                    if ($success !== null && $destroy !== null) {
                        $enchantment = StringToEnchantmentParser::getInstance()->parse($enchant);
                        
                        if ($player->getInventory()->canAddItem(Items::createEnchantmentBook($enchantment, $level))) {
                            $player->getInventory()->addItem(Items::createEnchantmentBook($enchantment, $level, $success, $destroy)->setCount($amount));
                            $sender->sendMessage(C::colorize(str_replace(["{enchant}", "{level}", "{player}", "{amount}"], [$enchant, $level, $player->getName(), $amount], Loader::getInstance()->getLanguageManager()->getNested("commands.main.givebook.success"))));
                            PlayerUtils::playSound($sender, "random.orb");
                        } else {
                            $sender->getWorld()->dropItem($sender->getPosition()->asVector3(), Items::createEnchantmentBook($enchantment, $level));
                        }
                    } else {
                        $sender->sendMessage(C::colorize(Loader::getInstance()->getLanguageManager()->getNested("commands.invalid-level")));
                    }
                }
            } else {
                $sender->sendMessage(C::colorize(Loader::getInstance()->getLanguageManager()->getNested("commands.invalid-enchantment")));
            }
        } else {
            $sender->sendMessage(C::colorize(Loader::getInstance()->getLanguageManager()->getNested("commands.invalid-player")));
        }
    }

    public function getUsage(): string {
        return "/me givebook <player> <enchant> <level> <amount> <success> <destroy>";
    }

    public function getPermission(): ?string
    {
        return "martianenchantments.give";
    }
}