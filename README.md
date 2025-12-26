# MartianEnchantments [![](https://poggit.pmmp.io/shield.state/MartianEnchantments)](https://poggit.pmmp.io/p/MartianEnchantments)
<a href="https://poggit.pmmp.io/p/MartianEnchantments"><img src="https://poggit.pmmp.io/shield.state/MartianEnchantments"></a> [![](https://poggit.pmmp.io/shield.api/MartianEnchantments)](https://poggit.pmmp.io/p/MartianEnchantments)
<a href="https://poggit.pmmp.io/p/MartianEnchantments"><img src="https://poggit.pmmp.io/shield.api/MartianEnchantments"></a>

MartianEnchantments is a custom enchantment system for PocketMine-MP, inspired by plugins like AdvancedEnchantments, but rewritten from scratch to work properly on modern PocketMine versions.

The main goal of this plugin is to provide a flexible, configuration-driven way to create and manage custom enchantments without relying on vanilla or PocketMine’s built-in enchantment system.

---

## ⚠️ Experimental Status

This plugin is currently **experimental**.

Early versions may contain bugs, unfinished features, or behavior changes between updates.  
If you encounter any issues, crashes, or have suggestions, please open an issue on GitHub:

https://github.com/MrEcstsy/MartianEnchantments/issues

---

## Core Features

### Config-Driven Enchantments

All enchantments are defined in `enchantments.yml`.

You can:
- Create new enchantments
- Modify existing enchantments
- Remove enchantments entirely

No code changes are required to manage enchantments.

Each enchantment supports:
- Custom display name and lore
- Descriptions
- Multiple levels
- Per-level chance and cooldown
- Conditions
- Multiple effects per level
- Trigger-based activation

This already provides more freedom than many existing custom enchantment plugins.

---

### NBT-Based Enchantment System

MartianEnchantments uses a **custom NBT-based enchantment system**.

Enchantments are **not** tied to vanilla or PocketMine enchantments, meaning:
- No vanilla enchant limitations
- No enchant ID conflicts
- No interference with default PM enchant logic
- Full control over enchant behavior

---

### Triggers

Enchantments activate through **triggers**, which define *when* an enchant attempts to run.

Currently implemented triggers include:
- ATTACK
- ATTACK_MOB
- DEFENSE
- DEFENSE_MOB
- DEFENSE_PROJECTILE
- EAT
- DEATH
- FALL_DAMAGE
- EXPLOSION
- FIRE
- HELD
- EFFECT_STATIC

More triggers can be added in future updates.

---

### Conditions

Enchantments may optionally include **conditions** that must be met before activation.

Examples include:
- Sneaking checks
- Item holding checks
- Other contextual requirements

Conditions are defined per level and are fully configurable.

---

### Effects

This version currently includes the following **implemented effects**:

- ACTION_BAR
- ADD_AIR
- ADD_FOOD
- ADD_HEALTH
- ADD_POTION
- BLOOD
- BURN
- DISABLE_ACTIVATION
- STEAL_HEALTH

More effects can be added later without breaking existing enchantments.

---

### Commands

The plugin includes multiple enchantment-related commands for managing enchantments and enchantment items.

Some enchantment items already exist, but **full functionality for them is still being implemented** and will be expanded in future updates.

`/mes` will show a bunch of commands but the only working ones are: about, enchant, givebook, giveitem, givercbook, info, list, reload, and unenchant.

---

### Developer Utilities

MartianEnchantments includes several developer-focused utilities, such as:
- A custom enchantment manager (add enchant, remove, etc)
- Methods for extracting enchantments from items
- Trigger execution helpers

These are mainly intended for developers who want to integrate or extend the system.

---

## Intended Use

MartianEnchantments is designed to:
- Create fully custom enchantments
- Avoid vanilla enchant limitations
- Provide a clean, modern CE system for PocketMine servers
- Serve as a solid base for enchantment-heavy gameplay

Even in its current state, the plugin is usable for its intended purpose.

---

## Future Plans

Planned or likely future additions include:
- More effects
- More triggers
- Enchantment GUIs (e.g. `/enchanter`)
- Expanded enchantment item functionality
- Additional condition types
- Cleanups

These features will be added incrementally through updates.
