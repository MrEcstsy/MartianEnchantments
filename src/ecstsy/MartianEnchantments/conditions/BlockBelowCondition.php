<?php

declare(strict_types=1);

namespace ecstsy\MartianEnchantments\conditions;

use ecstsy\MartianEnchantments\utils\ConditionInterface;
use pocketmine\entity\Entity;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;

final class BlockBelowCondition implements ConditionInterface {

    public function check(Entity $attacker, ?Entity $victim, array $conditionData, string $context, array $extraData): bool {
        $target = ($conditionData['target'] ?? 'attacker') === 'victim'
            ? $victim
            : $attacker;

        if (!$target instanceof Player) {
            return false;
        }

        $pos = $target->getPosition()->floor()->subtract(0, 1, 0);
        $block = $target->getWorld()->getBlock($pos);

        $item = StringToItemParser::getInstance()->parse((string)($conditionData['value'] ?? ''));
        if ($item === null) {
            return false;
        }

        return $block->getTypeId() === $item->getTypeId();
    }
}
