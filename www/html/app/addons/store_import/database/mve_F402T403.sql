ALTER TABLE `?:companies` DROP KEY `catalog_mode`;
ALTER TABLE `?:companies` DROP `catalog_mode`;
ALTER TABLE `?:settings_vendor_values` CHANGE `value` `value` text NOT NULL;

DELETE FROM `?:settings_descriptions` WHERE object_id IN (SELECT object_id FROM `?:settings_objects` WHERE name IN ('product_detailed_image_width', 'product_detailed_image_height')) AND object_type='O';
DELETE FROM `?:settings_objects` WHERE name IN ('product_detailed_image_width', 'product_detailed_image_height');

DROP TABLE IF EXISTS ?:settings_vendor_values_upg;
CREATE TABLE `?:settings_vendor_values_upg` (
  `object_id` mediumint(8) unsigned NOT NULL auto_increment,
  `company_id` int(11) unsigned NOT NULL,
  `name` varchar(128) NOT NULL default '',
  `section_name` varchar(128) NOT NULL default '',
  `value` text NOT NULL default '',
  PRIMARY KEY  (`object_id`, `company_id`)
) Engine=MyISAM DEFAULT CHARSET UTF8;

INSERT INTO ?:settings_vendor_values_upg
    SELECT
        ?:settings_objects.object_id,
        ?:settings_vendor_values.company_id,
        ?:settings_objects.name,
        ?:settings_sections.name as section_name,
        ?:settings_vendor_values.value
    FROM ?:settings_objects
    LEFT JOIN ?:settings_sections ON ?:settings_sections.section_id = ?:settings_objects.section_id
    INNER JOIN ?:settings_vendor_values ON ?:settings_vendor_values.object_id = ?:settings_objects.object_id;

DELETE FROM ?:settings_vendor_values WHERE object_id IN (
    SELECT object_id FROM ?:settings_objects WHERE section_id IN (
        SELECT section_id FROM ?:settings_sections WHERE type = 'ADDON'
    )
);

DROP TABLE IF EXISTS ?:settings_objects_upg;
CREATE TABLE `?:settings_objects_upg` (
  `object_id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `section_name` varchar(128) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`object_id`)
) Engine=MyISAM DEFAULT CHARSET UTF8;

INSERT INTO ?:settings_objects_upg
    SELECT
        ?:settings_objects.object_id,
        ?:settings_objects.name,
        ?:settings_sections.name as section_name,
        ?:settings_objects.value
    FROM ?:settings_objects
    LEFT JOIN ?:settings_sections ON ?:settings_sections.section_id = ?:settings_objects.section_id;

DELETE FROM ?:settings_descriptions WHERE object_type = 'V' AND object_id IN (
    SELECT variant_id FROM ?:settings_variants WHERE object_id IN (
        SELECT object_id FROM ?:settings_objects WHERE section_id IN (
            SELECT section_id FROM ?:settings_sections WHERE type = 'ADDON'
        )
    )
);

DELETE FROM ?:settings_descriptions WHERE object_type = 'O' AND object_id IN (
    SELECT object_id FROM ?:settings_objects WHERE section_id IN (
        SELECT section_id FROM ?:settings_sections WHERE type = 'ADDON'
    )
);

DELETE FROM ?:settings_descriptions WHERE object_type = 'S' AND object_id IN (
    SELECT section_id FROM ?:settings_sections WHERE parent_id IN (
        SELECT section_id FROM ?:settings_sections WHERE type = 'ADDON'
    )
);

DELETE FROM ?:settings_descriptions WHERE object_type = 'S' AND object_id IN (
    SELECT section_id FROM ?:settings_sections WHERE type = 'ADDON'
);

DELETE FROM ?:settings_variants WHERE object_id IN (
    SELECT object_id FROM ?:settings_objects WHERE section_id IN (
        SELECT section_id FROM ?:settings_sections WHERE type = 'ADDON'
    )
);

DELETE FROM ?:settings_objects WHERE section_id IN (
    SELECT section_id FROM ?:settings_sections WHERE type = 'ADDON'
);

DELETE s1, s2 FROM ?:settings_sections s1 LEFT JOIN ?:settings_sections as s2 ON s2.parent_id = s1.section_id WHERE s1.type = 'ADDON';

