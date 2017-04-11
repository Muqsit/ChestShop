# ChestShop
ChestShop for PocketMine-MP (pmmp) by Muqsit. Note that you must have **EconomyAPI** installed before running the plugin, else the plugin wont enable.
Chest shop allows you to create Chest GUI based shops - a widely used feature in minigames such as MoneyWars, SkyBlock and SkyWars.
If you are looking for a compressed .phar file, go here:https://github.com/Muqsit/ChestShop/releases
Features:
- Ability to add enchanted, custom named, custom NBT tagged items.
- Everything can be managed in-game on run time. No config modification needed.
- Two papers, named "Turn Left" and "Turn Right" are located at the end of the GUI to switch pages.
- ChestGUI (Block and inventory) is only sent to the command issuer. The GUI tile block is unbreakable, making it impossible for anyone to duplicate the chest contents.
- Usage of custom chest tiles and inventories rather than bulk events for performance.

Commands:
- /cs - Opens the ChestShop. (Permission: Everyone)
- /cs add [price] - Add the item in your hand to the ChestShop. (Permission: OP)
- /cs remove [page] [slot] - Remove item from a specific page and slot. (Permission: OP)
- /cs removebyid [itemid] [itemdamage] - Remove items with id [itemid] and damage [itemdamage] off ChestShop. (Permission: OP)
- /cs reload - Reloads the plugin (use this if you experience issues).
- /cs help - List all commands with their descriptions.

Thats it. Dont forget to star the repository.
