<?php

use yii\db\Migration;

/**
 * Class m190307_091423_create_galleries
 */
class m190307_091423_create_galleries extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        /**
         * Create `galleries` table
         */
        $this->createTable('galleries', [
            'gallery_id' => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'image_id' => 'int(10) UNSIGNED NOT NULL DEFAULT 0',
            'user_id' => 'int(10) UNSIGNED NOT NULL DEFAULT 0',
            'slug' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'title' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'description' => 'text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL',
            'orientation' => 'tinyint(1) UNSIGNED NOT NULL DEFAULT \'0\' COMMENT \'1: straight; 2:lesbian; 3:shemale; 4:gay;\'',
            'on_index' => 'tinyint(1) UNSIGNED NOT NULL DEFAULT 1',
            'likes' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 1',
            'dislikes' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'images_num' => 'tinyint(3) UNSIGNED NOT NULL DEFAULT 0',
            'comments_num' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'views' => 'mediumint(8) UNSIGNED NOT NULL DEFAULT 0',
            'max_ctr' => 'double UNSIGNED NOT NULL DEFAULT 0',
            'template' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'status' => 'tinyint(3) UNSIGNED NOT NULL DEFAULT 0',
            'published_at' => 'timestamp NULL DEFAULT NULL',
            'created_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ], $tableOptions);

        $this->createIndex('slug', 'galleries', 'slug', true);
        $this->createIndex('user_id', 'galleries', 'user_id');
        $this->createIndex('published_at', 'galleries', ['published_at', 'status']);
        $this->createIndex('views', 'galleries', 'views');
        $this->createIndex('likes', 'galleries', 'likes');
        $this->createIndex('max_ctr', 'galleries', 'max_ctr');
        $this->execute("ALTER TABLE `galleries` ADD FULLTEXT KEY `title` (`title`,`description`)");

        /**
         * Create `galleries_categories` table
         */
        $this->createTable('galleries_categories', [
            'category_id' => 'smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'image_id' => 'int(10) UNSIGNED NOT NULL DEFAULT 0',
            'ordering' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 1',
            'slug' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'meta_title' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'meta_description' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'title' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'h1' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'description' => 'text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL',
            'seotext' => 'text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL',
            'param1' => 'text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL',
            'param2' => 'text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL',
            'param3' => 'text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL',
            'galleries_num' => 'int(10) UNSIGNED NOT NULL DEFAULT 0',
            'popularity' => 'double(5,2) UNSIGNED NOT NULL DEFAULT \'0.00\'',
            'on_index' => 'tinyint(1) UNSIGNED NOT NULL DEFAULT 1',
            'enabled' => 'tinyint(1) UNSIGNED NOT NULL DEFAULT 0',
            'created_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ], $tableOptions);

        $this->createIndex('slug', 'galleries_categories', 'slug', true);
        $this->createIndex('title', 'galleries_categories', 'title');
        $this->createIndex('ordering', 'galleries_categories', 'ordering');
        $this->createIndex('enabled', 'galleries_categories', 'enabled');
        $this->createIndex('popularity', 'galleries_categories', 'popularity');

        /**
         * Create `galleries_categories_map` table
         */
        $this->createTable('galleries_categories_map', [
            'category_id' => 'smallint(5) UNSIGNED NOT NULL',
            'gallery_id' => 'int(10) UNSIGNED NOT NULL DEFAULT 0',
        ], $tableOptions);

        $this->createIndex('category_id', 'galleries_categories_map', 'category_id');
        $this->createIndex('gallery_id', 'galleries_categories_map', 'gallery_id');

        /**
         * Create `galleries_categories_stats` table
         */
        $this->createTable('galleries_categories_stats', [
            'category_id' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'date' => 'date NOT NULL',
            'hour' => 'tinyint(2) UNSIGNED NOT NULL DEFAULT 0',
            'clicks' => 'mediumint(8) UNSIGNED NOT NULL DEFAULT 1',
        ], $tableOptions);

        $this->addPrimaryKey('category_id', 'galleries_categories_stats', ['category_id', 'date', 'hour']);

        /**
         * Create `galleries_images` table
         */
        $this->createTable('galleries_images', [
            'image_id' => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'gallery_id' => 'int(10) UNSIGNED NOT NULL DEFAULT 0',
            'ordering' => 'smallint(3) UNSIGNED NOT NULL DEFAULT 0',
            'hash' => 'char(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'path' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'source_url' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'enabled' => 'tinyint(1) UNSIGNED NOT NULL DEFAULT 0',
            'created_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ], $tableOptions);

        $this->createIndex('gallery_id', 'galleries_images', ['gallery_id', 'ordering']);
        $this->createIndex('source_url', 'galleries_images', 'source_url');
        $this->createIndex('enabled', 'galleries_images', 'enabled');

        /**
         * Create `galleries_crops` table
         */
        $this->createTable('galleries_crops', [
            'crop_id' => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'name' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'comment' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'command' => 'text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL',
            'created_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ], $tableOptions);

        $this->createIndex('name', 'galleries_crops', 'name', true);

        /**
         * Create `galleries_import_feeds` table
         */
        $this->createTable('galleries_import_feeds', [
            'feed_id' => 'smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'name' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'description' => 'varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
            'delimiter' => 'varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'|\'',
            'enclosure' => 'varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'"\'',
            'fields' => 'json DEFAULT NULL',
            'skip_first_line' => 'tinyint(1) UNSIGNED NOT NULL DEFAULT 1',
            'skip_duplicate_urls' => 'tinyint(1) UNSIGNED NOT NULL DEFAULT 1',
            'skip_new_categories' => 'tinyint(1) UNSIGNED NOT NULL DEFAULT 1',
            'external_images' => 'tinyint(1) UNSIGNED NOT NULL DEFAULT 1',
            'template' => 'varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'',
        ], $tableOptions);
        
        /**
         * Create `galleries_related` table
         */
        $this->createTable('galleries_related', [
            'gallery_id' => 'int(10) UNSIGNED NOT NULL',
            'related_id' => 'int(10) UNSIGNED NOT NULL',
        ], $tableOptions);

        $this->createIndex('gallery_id', 'galleries_related', 'gallery_id');
        $this->createIndex('related_id', 'galleries_related', 'related_id');

        /**
         * Create `galleries_stats` table
         */
        $this->createTable('galleries_stats', [
            'gallery_id' => 'int(10) UNSIGNED NOT NULL',
            'image_id' => 'int(10) UNSIGNED NOT NULL',
            'category_id' => 'smallint(5) UNSIGNED NOT NULL',
            'is_tested' => 'tinyint(3) UNSIGNED NOT NULL DEFAULT 0',
            'current_index' => 'tinyint(3) UNSIGNED NOT NULL DEFAULT 0',
            'current_shows' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'current_clicks' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'shows0' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'clicks0' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'shows1' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'clicks1' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'shows2' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'clicks2' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'shows3' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'clicks3' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'shows4' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'clicks4' => 'smallint(5) UNSIGNED NOT NULL DEFAULT 0',
            'total_shows' => 'mediumint(8) UNSIGNED GENERATED ALWAYS AS ((((((`shows0` + `shows1`) + `shows2`) + `shows3`) + `shows4`) + 1)) VIRTUAL',
            'total_clicks' => 'mediumint(8) UNSIGNED GENERATED ALWAYS AS (((((`clicks0` + `clicks1`) + `clicks2`) + `clicks3`) + `clicks4`)) VIRTUAL',
            'ctr' => 'double GENERATED ALWAYS AS ((`total_clicks` / `total_shows`)) VIRTUAL',
        ], $tableOptions);

        $this->addPrimaryKey('gallery_id', 'galleries_stats', ['gallery_id', 'image_id', 'category_id']);
        $this->createIndex('image_id', 'galleries_stats', ['image_id', 'category_id']);
        $this->createIndex('category_id', 'galleries_stats', ['category_id', 'is_tested', 'ctr']);
        $this->createIndex('gallery_id', 'galleries_stats', ['gallery_id', 'image_id', 'ctr']);
        $this->createIndex('category_id_2', 'galleries_stats', ['category_id', 'ctr']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190307_091423_create_galleries cannot be reverted.\n";

        return false;
    }
}
