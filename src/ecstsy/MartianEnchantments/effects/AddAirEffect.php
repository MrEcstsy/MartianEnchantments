<?php

namespace ecstsy\MartianEnchantments\effects;

use ecstsy\MartianEnchantments\utils\EffectInterface;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;

class AddAirEffect implements EffectInterface {

    public function apply(Entity $attacker, ?Entity $victim, array $data, array $effectData, string $context, array $extraData): void
    {
        if (isset($effectData['amount'])) {
            $target = $effectData['target'] === 'victim' ? $victim : $attacker;

            if (!$target instanceof Living) {
                return;
            }

            $target->setAirSupplyTicks(GeneralUtils::parseRandomNumber($effectData['amount']));
        }
    }
}
