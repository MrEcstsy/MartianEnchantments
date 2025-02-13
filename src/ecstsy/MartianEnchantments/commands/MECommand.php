<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\commands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\MartianEnchantments\commands\subcommands\AboutSubCommand;
use ecstsy\MartianEnchantments\commands\subcommands\EnchantSubCommand;
use ecstsy\MartianEnchantments\commands\subcommands\GiveBookSubCommand;
use ecstsy\MartianEnchantments\commands\subcommands\GiveItemSubcommand;
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

        $this->registerSubCommand(new GiveItemSubcommand(Loader::getInstance(), "giveitem", "Give Plugin Items"));
        $this->registerSubCommand(new AboutSubCommand(Loader::getInstance(), "about", "Information about plugin"));
        $this->registerSubCommand(new EnchantSubCommand(Loader::getInstance(), "enchant", "Enchant held item"));
        $this->registerSubCommand(new UnenchantSubCommand(Loader::getInstance(), "unenchant", "Unenchant held item"));
        $this->registerSubCommand(new ListSubCommand(Loader::getInstance(), "list", "List all enchantments"));
        $this->registerSubCommand(new GiveBookSubCommand(Loader::getInstance(), "givebook", "Give enchantment book"));
        $this->registerSubCommand(new InfoSubCommand(Loader::getInstance(), "info", "Info about enchantment"));
        $this->registerSubCommand(new ReloadSubCommand(Loader::getInstance(), "reload", "Reload plugin configuration"));
        $this->registerSubCommand(new GiveRCBookSubCommand(Loader::getInstance(), "givercbook", "Give RC enchantment book"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage('MartianEnchantments Commands MV(minified version for console)');
            $sender->sendMessage('/me reload - reload the plugins configuration');
            $sender->sendMessage('/me giveitem <player> <item> - Give various plugin items');
            $sender->sendMessage('/me give <player> <enchantment> <level> - Give Enchantment book with enchant');
            $sender->sendMessage('/me givebook <player> <enchantment> <level> <count> <success> <destroy> - Give book with specific rates');
            return;
        }

        $page = $args['page'] ?? 1;
        $msgs = [
            "&r&f  /me market &7- &eCommunity Enchantments",
            "&r&f  /me enchanter &7- &eOpen Enchanter",
            "&r&f  /asets &7- &eSets commands",
            "&r&f  /megive &7- &eGive Custom Enchanted Items",
            "&r&f  /tinkerer &7- &eOpen Tinkerer",
            "&r&f  /gkits &7- &eOpen GKits",
            "&r&f  /me about &7- &eInformation about plugin",
            "&r&f  /me enchant &2<enchantment> <level> &7- &eEnchant held item",
            "&r&f  /me unenchant &2<enchantment> &7- &eUnenchant held item",
            "&r&f  /me list &9[page] &7- &eList all enchantments",
            "&r&f  /me admin &9[page]&f/&9[enchant to search for] &7- &eOpen Admin Inventory",
            "&r&f  /me giveitem &2<player> <item> <amount> &7- &eGive Plugin Items",
            "&r&f  /me greset &2<player> <gkit> &7- &eReset GKit for player",
            "&r&f  /me tinkereritem &2<player> <amount> &7- &eGive Tinkerer's reward item to player'",
            "&r&f  /me give &2<player> <enchantment> <level> &7- &eGive Enchantment Book",
            "&r&f  /me setSouls &2<amount> &7- &eSet Souls on Held Item",
            "&r&f  /me info &2<enchantment> &7- &eInformation about Enchantment",
            "&r&f  /me reload &7- &eReload the plugin configuration",
            "&r&f  /me magicdust &2<group> <rate> <player> <amount> &7- &eGive Magic Dust with specific rate",
            "&r&f  /me givebook &2<player> <enchantment> <level> <count> <success> <destroy> &7- &eGive Book with specific rates",
            "&r&f  /me givercbook &2<type> <player> <amount>  &7- &eGive Right-click books",
            "&r&f  /me premade &7- &eView premade plugin configurations",
            "&r&f  /me giverandombook &2<player> <group> &a[amount] &7- &eGives random book from tier",
            "&r&f  /me open &2<player> <enchanter/tinkerer/alchemist> &7- &eForce-open GUI",
            "&r&f  /me lastchanged &7- &eShows all enchants that were added/removed the last time /me reload was run",
            "&r&f  /me zip &7- &eZips up AE's data folder",
        ];

        $totalItems = count($msgs);
        $totalPages = ceil($totalItems / self::ITEMS_PER_PAGE);

        if ($page < 1 || $page > $totalPages) {
            $sender->sendMessage(C::RED . "Invalid page number. Please choose between 1 and " . $totalPages . ".");
            return;
        }

        $start = ($page - 1) * self::ITEMS_PER_PAGE;
        $end = min($start + self::ITEMS_PER_PAGE, $totalItems);

        $header = C::YELLOW . "[<]" . C::DARK_GRAY . " +-----< " . C::GOLD . "MartianEnchantments " . C::WHITE . "(Page $page) " . C::DARK_GRAY . ">-----+" . C::YELLOW . " [>]";
        $footer = C::YELLOW . "[<]" . C::DARK_GRAY . " +-----< " . C::GOLD . "MartianEnchantments " . C::WHITE . "(Page $page) " . C::DARK_GRAY . ">-----+" . C::YELLOW . " [>]";

        $sender->sendMessage($header);
        $sender->sendMessage(" ");

        for ($i = $start; $i < $end; $i++) {
            $sender->sendMessage(C::colorize($msgs[$i]));
        }

        if ($page === 1) {
            $sender->sendMessage(" "); 
            $sender->sendMessage(C::DARK_GREEN . "  <> " . C::WHITE . "- Required Arguments; " . C::BLUE . "[] " . C::WHITE . "- Optional Arguments");
        }

        $sender->sendMessage(C::GRAY . "* Navigate through help pages using " . C::WHITE . "/me <page>");
        $sender->sendMessage($footer);
    }

    public function getUsage(): string {
        return Loader::getInstance()->getLanguageManager()->getNested("commands.main.unknown-command");
    }
    public function getPermission(): string {
        return 'martianenchantments.default';
    }
}