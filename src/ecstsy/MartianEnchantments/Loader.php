<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments;

use ecstsy\MartianEnchantments\commands\MECommand;
use ecstsy\MartianEnchantments\enchantments\CustomEnchantments;
use ecstsy\MartianEnchantments\enchantments\Groups;
use ecstsy\MartianEnchantments\listeners\EnchantmentListener;
use ecstsy\MartianEnchantments\utils\Utils;
use ecstsy\MartianUtilities\managers\LanguageManager;
use ecstsy\MartianUtilities\UtilityHandler;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use JackMD\ConfigUpdater\ConfigUpdater;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\SingletonTrait;

final class Loader extends PluginBase {
    use SingletonTrait;

    private LanguageManager $languageManager;
    private static ?ZippedResourcePack $pack;
    public $economyProvider;
    public const TYPE_DYNAMIC_PREFIX = "martianenchants:customsizedinvmenu_"; # The entire custom sized inv is from muqsit
    public const CFG_VERSIONS = [
        "config" => 1,
        "en-us" => 1,
    ];

    public function onLoad(): void {
        self::setInstance($this);
    }

    public function onEnable(): void {
        $files = ["enchantments.yml", "groups.yml"];
        
        foreach ($files as $file) {
            $this->saveResource($file);
        }

        ConfigUpdater::checkUpdate($this, $this->getConfig(), "version", self::CFG_VERSIONS["config"]);

        $subDirectories = ["locale", "armorSets", "menus"];

        foreach ($subDirectories as $directory) {
            $this->saveAllFilesInDirectory($directory);
        }

        $config = GeneralUtils::getConfiguration($this, "config.yml");
        $language = $config->getNested("settings.language");

        $this->languageManager = new LanguageManager($this, $language);
        $this->getLogger()->info("MartianEnchantments enabled with language: " . $language);

        $this->getServer()->getCommandMap()->registerAll("MartianEnchantments", [
            new MECommand($this, "martianenchantments", "View the martian enchantments commands", ["mes"]),
        ]);

        $listeners = [
            new EnchantmentListener($this)
        ];

        foreach ($listeners as $listener) {
            $this->getServer()->getPluginManager()->registerEvents($listener, $this);
        }

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        if(!UtilityHandler::isRegistered()) {
            UtilityHandler::register($this);
        }
        
        if ($config->getNested("economy.enabled") === true) {
            // implement
        } else {
            $this->getLogger()->warning("Economy support is disabled.");
        }
        
        Utils::initRegistries();
        Groups::init();
        CustomEnchantments::getAll();
    } 

    public function getDisable(): void {

    }

    private function saveAllFilesInDirectory(string $directory): void {
        $resourcePath = $this->getFile() . "resources/$directory/";
        if (!is_dir($resourcePath)) {
            $this->getLogger()->warning("Directory $directory does not exist.");
            return;
        }

        $files = scandir($resourcePath);
        if ($files === false) {
            $this->getLogger()->warning("Failed to read directory $directory.");
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $this->saveResource("$directory/$file");
        }
    }

    public function getLanguageManager(): LanguageManager {
        return $this->languageManager;
    }
}
