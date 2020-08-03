-- #!sqlite
-- #{ chestshop

-- #  { init_categories
CREATE TABLE IF NOT EXISTS categories(
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(128) NOT NULL UNIQUE,
  button BLOB NOT NULL
);
-- #  }
-- #  { init_category_contents
CREATE TABLE IF NOT EXISTS category_contents(
    category_id INT UNSIGNED NOT NULL,
    slot INT UNSIGNED NOT NULL,
    item BLOB NOT NULL,
    price FLOAT UNSIGNED NOT NULL,
    PRIMARY KEY(category_id, slot),
    FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);
-- #  }

-- #  { load_categories
SELECT id, name, button FROM categories;
-- #  }

-- #  { load_category_contents
-- #    :category_id int
SELECT slot, item, price FROM category_contents WHERE category_id=:category_id;
-- #  }

-- #  { add_category
-- #    :name string
-- #    :button string
INSERT INTO categories(name, button) VALUES(:name, X:button);
-- #  }

-- #  { remove_category
-- #    :id int
DELETE FROM categories WHERE id=:id;
-- #  }

-- #  { add_category_content
-- #    :category_id int
-- #    :slot int
-- #    :item string
-- #    :price float
INSERT OR REPLACE INTO category_contents(category_id, slot, item, price) VALUES(:category_id, :slot, X:item, :price);
-- #  }

-- #  { remove_category_content
-- #    :category_id int
-- #    :slot int
DELETE FROM category_contents WHERE category_id=:category_id AND slot=:slot;
-- #  }

-- #  { remove_category_contents
-- #    :category_id int
DELETE FROM category_contents WHERE category_id=:category_id;
-- #  }
-- #}