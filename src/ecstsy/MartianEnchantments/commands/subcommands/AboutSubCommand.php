<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;
use ecstsy\MartianEnchantments\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class AboutSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $sender->sendMessage(TextFormat::colorize("&r&6ME &eVersion: &f" . Loader::getInstance()->getDescription()->getVersion()));
    }

    public function getPermission(): string
    {
        return "martianenchantments.default";
    }
}