<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\commands;

use ecstsy\MartianEnchantments\libs\CortexPE\Commando\args\IntegerArgument;
use ecstsy\MartianEnchantments\libs\CortexPE\Commando\BaseCommand;
use ecstsy\MartianEnchantments\commands\subcommands\AboutSubCommand;
use ecstsy\MartianEnchantments\commands\subcommands\EnchantSubCommand;
use ecstsy\MartianEnchantments\commands\subcommands\GiveBookSubCommand;
use ecstsy\MartianEnchantments\commands\subcommands\GiveItemSubCommand;
use ecstsy\MartianEnchantments\commands\subcommands\GiveRCBookSubCommand;
use ecstsy\MartianEnchantments\commands\subcommands\InfoSubCommand;
use ecstsy\MartianEnchantments\commands\subcommands\ListSubCommand;
use ecstsy\MartianEnchantments\commands\subcommands\ReloadSubCommand;
use ecstsy\MartianEnchantments\commands\subcommands\UnenchantSubCommand;
use ecstsy\MartianEnchantments\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class MECommand extends BaseCommand {

    private const ITEMS_PER_PAGE = 14;

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new IntegerArgument('page', true));

        $this->registerSubCommand(new GiveItemSubCommand(Loader::getInstance(), "giveitem", "Give Martian items (admin)"));
        $this->registerSubCommand(new AboutSubCommand(Loader::getInstance(), "about", "Version & about"));
        $this->registerSubCommand(new EnchantSubCommand(Loader::getInstance(), "enchant", "Apply a custom enchant to held item"));
        $this->registerSubCommand(new UnenchantSubCommand(Loader::getInstance(), "unenchant", "Strip a custom enchant from held item"));
        $this->registerSubCommand(new ListSubCommand(Loader::getInstance(), "list", "Browse all custom enchants"));
        $this->registerSubCommand(new GiveBookSubCommand(Loader::getInstance(), "givebook", "Give a configured enchant book (admin)"));
        $this->registerSubCommand(new InfoSubCommand(Loader::getInstance(), "info", "Details for one custom enchant"));
        $this->registerSubCommand(new ReloadSubCommand(Loader::getInstance(), "reload", "Reload config & enchant data"));
        $this->registerSubCommand(new GiveRCBookSubCommand(Loader::getInstance(), "givercbook", "Give a right-click style book (admin)"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::DARK_AQUA . "MartianEnchantments" . C::GRAY . " — " . C::WHITE . "from console");
            $sender->sendMessage(C::GRAY . "  " . C::WHITE . "/me reload" . C::GRAY . " — reload config & data");
            $sender->sendMessage(C::GRAY . "  " . C::WHITE . "/me list [page]" . C::GRAY . " — list enchants (no held item required)");
            $sender->sendMessage(C::GRAY . "  " . C::WHITE . "/me info <enchant>" . C::GRAY . " — one enchant in depth");
            $sender->sendMessage(C::GRAY . "  " . C::WHITE . "/me giveitem" . C::GRAY . ", " . C::WHITE . "givebook" . C::GRAY . ", " . C::WHITE . "givercbook" . C::GRAY . " — admin, see in-game " . C::WHITE . "/me" . C::GRAY . " (player) for full usage");
            return;
        }

        $page = $args['page'] ?? 1;
        $msgs = [
            "&r&7This plugin &f&lMartianEnchantments&r&7: custom enchants, books, and item helpers for &fPocketMine-MP&7.",
            "&r&8————————————————",
            "&r&b  /me about &7- &fVersion and short description",
            "&r&b  /me list &3[page] &7- &fList registered custom enchants",
            "&r&b  /me info &3<enchant> &7- &fOne enchant: levels, triggers, text",
            "&r&b  /me enchant &2<enchant> <level> &7- &fApply to the item in hand (permission required)",
            "&r&b  /me unenchant &2<enchant> &7- &fRemove from the item in hand (permission required)",
            "&r&8—— &7Admin &8——",
            "&r&b  /me giveitem &2<args…> &7- &fGive plugin items to a player",
            "&r&b  /me givebook &2<args…> &7- &fGive a book (success/destroy and options)",
            "&r&b  /me givercbook &2<args…> &7- &fGive a right-click book item",
            "&r&b  /me reload &7- &fReload &eenchantments.yml&7, &egroups&7, locale, &fconfig",
        ];

        $totalItems = count($msgs);
        $totalPages = ceil($totalItems / self::ITEMS_PER_PAGE);

        if ($page < 1 || $page > $totalPages) {
            $sender->sendMessage(C::RED . "Invalid page number. Please choose between 1 and " . $totalPages . ".");
            return;
        }

        $start = ($page - 1) * self::ITEMS_PER_PAGE;
        $end = min($start + self::ITEMS_PER_PAGE, $totalItems);

        $header = C::DARK_GRAY . "— " . C::AQUA . "MartianEnchantments" . C::DARK_GRAY . " · " . C::GRAY . "help page " . C::WHITE . (string) $page . C::DARK_GRAY . " —";
        $footer = C::DARK_GRAY . "— " . C::GRAY . "Aliases: " . C::WHITE . "/me" . C::GRAY . ", " . C::WHITE . "/mes" . C::DARK_GRAY . " —";

        $sender->sendMessage($header);
        $sender->sendMessage(" ");

        for ($i = $start; $i < $end; $i++) {
            $sender->sendMessage(C::colorize($msgs[$i]));
        }

        if ($page === 1) {
            $sender->sendMessage(" ");
            $sender->sendMessage(C::DARK_GREEN . "  " . C::GREEN . "<...>" . C::WHITE . " required  ·  " . C::AQUA . "[..]" . C::WHITE . " optional");
        }

        if ($totalPages > 1) {
            $sender->sendMessage(C::DARK_GRAY . "» " . C::GRAY . "Page " . C::WHITE . (string) $page . C::GRAY . " of " . C::WHITE . (string) $totalPages . C::GRAY . ". Use " . C::WHITE . "/me " . (string)($page < $totalPages ? $page + 1 : 1) . C::GRAY . " for another page.");
        }
        $sender->sendMessage($footer);
    }

    public function getUsage(): string {
        return Loader::getInstance()->getLanguageManager()->getNested("commands.main.unknown-command");
    }
    public function getPermission(): string {
        return 'martianenchantments.default';
    }
}
