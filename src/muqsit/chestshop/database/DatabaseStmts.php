<?php

declare(strict_types=1);

namespace muqsit\chestshop\database;

interface DatabaseStmts{

	public const PREFIX = "chestshop.";

	public const INIT_CATEGORIES = self::PREFIX . "init_categories";
	public const INIT_CATEGORY_CONTENTS = self::PREFIX . "init_category_contents";

	public const LOAD_CATEGORIES = self::PREFIX . "load_categories";
	public const LOAD_CATEGORY_CONTENTS = self::PREFIX . "load_category_contents";

	public const ADD_CATEGORY = self::PREFIX . "add_category";
	public const ADD_CATEGORY_CONTENT = self::PREFIX . "add_category_content";

	public const REMOVE_CATEGORY = self::PREFIX . "remove_category";
	public const REMOVE_CATEGORY_CONTENT = self::PREFIX . "remove_category_content";
	public const REMOVE_CATEGORY_CONTENTS = self::PREFIX . "remove_category_contents";
}