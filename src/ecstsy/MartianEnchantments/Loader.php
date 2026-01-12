<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments;

use ecstsy\MartianEnchantments\utils\Utils;
use ecstsy\MartianEnchantments\libs\ecstsy\MartianUtilities\managers\LanguageManager;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\SingletonTrait;

final class Loader extends PluginBase {
    use SingletonTrait;

    private static ?ZippedResourcePack $pack;
    public $economyProvider;
    public const TYPE_DYNAMIC_PREFIX = "martianenchants:customsizedinvmenu_"; # The entire custom sized inv is from muqsit

    public function onLoad(): void {
        self::setInstance($this);
    }

    public function onEnable(): void {
        Utils::initAll();
    } 

    public function getDisable(): void {

    }

    public function getLanguageManager(): LanguageManager {
        return Utils::$languageManager;
    }
}
