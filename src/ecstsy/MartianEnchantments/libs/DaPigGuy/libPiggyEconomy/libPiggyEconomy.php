<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\libs\DaPigGuy\libPiggyEconomy;

use ecstsy\MartianEnchantments\libs\DaPigGuy\libPiggyEconomy\exceptions\MissingProviderDependencyException;
use ecstsy\MartianEnchantments\libs\DaPigGuy\libPiggyEconomy\exceptions\UnknownProviderException;
use ecstsy\MartianEnchantments\libs\DaPigGuy\libPiggyEconomy\providers\BedrockEconomyProvider;
use ecstsy\MartianEnchantments\libs\DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use ecstsy\MartianEnchantments\libs\DaPigGuy\libPiggyEconomy\providers\EconomySProvider;
use ecstsy\MartianEnchantments\libs\DaPigGuy\libPiggyEconomy\providers\EssentialsXProvider;
use ecstsy\MartianEnchantments\libs\DaPigGuy\libPiggyEconomy\providers\EXPProvider;
use ecstsy\MartianEnchantments\libs\DaPigGuy\libPiggyEconomy\providers\XPProvider;

class libPiggyEconomy
{
    public static bool $hasInitiated = false;

    /**
     * @var string[] $economyProviders
     * @phpstan-var array<class-string<EconomyProvider>>
     */
    public static array $economyProviders;

    public static function init(): void
    {
        if (!self::$hasInitiated) {
            self::$hasInitiated = true;

            self::registerProvider(["economys", "economyapi"], EconomySProvider::class);
            self::registerProvider(["bedrockeconomy"], BedrockEconomyProvider::class);
            self::registerProvider(["xp", "xplevel"], XPProvider::class);
            self::registerProvider(["essentialsx", "essentialx"], EssentialsXProvider::class);
            self::registerProvider(["exp", "experience"], EXPProvider::class);
        }
    }

    /**
     * @phpstan-param class-string<EconomyProvider> $economyProvider
     */
    public static function registerProvider(array $providerNames, string $economyProvider): void
    {
        foreach ($providerNames as $providerName) {
            if (isset(self::$economyProviders[strtolower($providerName)])) continue;
            self::$economyProviders[strtolower($providerName)] = $economyProvider;
        }
    }

    /**
     * @throws UnknownProviderException
     * @throws MissingProviderDependencyException
     */
    public static function getProvider(array $providerInformation): EconomyProvider
    {
        if (!isset(self::$economyProviders[strtolower($providerInformation["provider"])])) {
            throw new UnknownProviderException("Provider " . $providerInformation["provider"] . " not found.");
        }
        $provider = self::$economyProviders[strtolower($providerInformation["provider"])];
        if (!$provider::checkDependencies()) {
            throw new MissingProviderDependencyException("Dependencies for provider " . $providerInformation["provider"] . " not found.");
        }
        return new $provider($providerInformation);
    }
}