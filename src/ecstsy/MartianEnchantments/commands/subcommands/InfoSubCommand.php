<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\commands\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\MartianEnchantments\enchantments\CustomEnchantment;
use ecstsy\MartianEnchantments\enchantments\CustomEnchantments;
use ecstsy\MartianEnchantments\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class InfoSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("enchantment", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only."));
            return;
        }

        $enchant = isset($args["enchantment"]) ? $args["enchantment"] : null;
        $lang = Loader::getInstance()->getLanguageManager();

        if ($enchant !== null) {
            $enchantment = CustomEnchantments::getEnchantmentByName($enchant);
            if ($enchantment instanceof CustomEnchantment) {
                $infoMessages = $lang->getNested("commands.main");
                
                $enchantName = ucfirst($enchantment->getName());
                $description = $enchantment->getDescription();  
                $maxLevel = $enchantment->getMaxLevel();

                $enchantConfig = GeneralUtils::getConfiguration(Loader::getInstance(), "enchantments.yml");
                $enchantmentData = $enchantConfig->get(strtolower($enchantment->getName()), []);
                $appliesTo = $enchantmentData['applies-to'] ?? ["Unknown"];

                foreach ($infoMessages['info'] as $message) {
                    $message = str_replace(
                        ['{enchant}', '{description}', '{applies}', '{max-level}'],
                        [$enchantName, $description, $appliesTo, $maxLevel],
                        $message
                    );
                    $sender->sendMessage(C::colorize($message));
                }
            } else {
                $sender->sendMessage(C::colorize(str_replace("{enchant}", $enchant, $lang->getNested("commands.invalid-enchant"))));
                return;
            }
        } 
    }

    public function getPermission(): string
    {
        return "martianenchantments.info";
    }
}