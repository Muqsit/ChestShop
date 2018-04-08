# ChestShop
[![](https://poggit.pmmp.io/shield.state/ChestShop)](https://poggit.pmmp.io/p/ChestShop)

Chest shop allows you to create Chest GUI based shops - a widely used feature in minigames such as MoneyWars, SkyBlock and SkyWars.
If you are looking for a compressed .phar file, go here: https://poggit.pmmp.io/ci/Muqsit/ChestShop/ChestShop

**NOTE:** ChestShop depends upon EconomyAPI for transactions, you must have **EconomyAPI** plugin installed before running the plugin.

### Basic Features
- Ability to sell items along with their NBT tags.
- Categories! Create shop categories to sort out your items.
- Optional double-tap-to-buy feature for those who value their money.

### How To Use?
Download the compiled .phar file from poggit and drop it into your server's `plugins/` folder.
**NOTE:** You must either be OP or have the permission `chestshop.command.admin` to use the commands: `/cs addcategory`, `/cs removecategory` and `/cs additem`

#### Adding a category
Categories are the front-page of the `/chestshop` command. If you do not have any categories, the `/chestshop` command will send you an empty chest GUI. To add a category, use `/cs addcategory <category name>` while holding an item. The item that you are holding will be used to represent the category in `/chestshop`.
![](https://i.imgur.com/8cPouEf.png)

Now let's see how `/chestshop` looks.

![](https://imgur.com/iRWAJ6a.png)

Neato! Let's add some items to our category using `/cs additem <category name> <cost>`.

![](https://i.imgur.com/fF8gPap.png)

Awesome! You might be wondering what the 2 papers and one chest is doing in the last row of the GUI. The two papers turn towards the left/right page in case you have more than 45 items in your category. The chest in the middle of the two papers bring you back to the list of categories (`/chestshop`).
