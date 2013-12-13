
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

INSERT INTO `?:payment_processors` (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES ('90', 'Pay4Later', 'pay4later.php', 'cc_outside.tpl', 'pay4later.tpl', 'N', 'P') ON DUPLICATE KEY UPDATE `processor_id` = `processor_id`;
INSERT INTO `?:payment_processors` (processor_id, processor, processor_script, processor_template, admin_template, callback, type) VALUES ('91', 'Yes Credit', 'yes_credit.php', 'cc_outside.tpl', 'yes_credit.tpl', 'N', 'P') ON DUPLICATE KEY UPDATE `processor_id` = `processor_id`;
INSERT INTO `?:status_data` (status, type, param, value) VALUES ('B', 'O', 'remove_cc_info', 'Y') ON DUPLICATE KEY UPDATE `status` = `status`;
INSERT INTO `?:status_data` (status, type, param, value) VALUES ('I', 'O', 'remove_cc_info', 'Y') ON DUPLICATE KEY UPDATE `status` = `status`;
INSERT INTO `?:status_data` (status, type, param, value) VALUES ('C', 'O', 'remove_cc_info', 'Y') ON DUPLICATE KEY UPDATE `status` = `status`;
INSERT INTO `?:status_data` (status, type, param, value) VALUES ('D', 'O', 'remove_cc_info', 'Y') ON DUPLICATE KEY UPDATE `status` = `status`;
INSERT INTO `?:status_data` (status, type, param, value) VALUES ('F', 'O', 'remove_cc_info', 'Y') ON DUPLICATE KEY UPDATE `status` = `status`;
INSERT INTO `?:status_data` (status, type, param, value) VALUES ('O', 'O', 'remove_cc_info', 'Y') ON DUPLICATE KEY UPDATE `status` = `status`;
INSERT INTO `?:status_data` (status, type, param, value) VALUES ('P', 'O', 'remove_cc_info', 'Y') ON DUPLICATE KEY UPDATE `status` = `status`;
INSERT INTO `?:status_data` (status, type, param, value) VALUES ('A', 'O', 'remove_cc_info', 'Y') ON DUPLICATE KEY UPDATE `status` = `status`;
DELETE FROM `?:settings_objects` WHERE object_id='200';

DELETE FROM `?:states` WHERE country_code='ES';
DELETE FROM `?:states` WHERE state_id='661';

REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(636, 'ES', 'C', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(637, 'ES', 'VI', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(638, 'ES', 'AB', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(639, 'ES', 'A', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(640, 'ES', 'AL', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(641, 'ES', 'O', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(642, 'ES', 'AV', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(643, 'ES', 'BA', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(644, 'ES', 'PM', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(645, 'ES', 'B', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(646, 'ES', 'BU', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(647, 'ES', 'CC', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(648, 'ES', 'CA', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(649, 'ES', 'S', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(650, 'ES', 'CS', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(651, 'ES', 'CE', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(652, 'ES', 'CR', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(653, 'ES', 'CO', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(654, 'ES', 'CU', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(655, 'ES', 'GI', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(656, 'ES', 'GR', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(657, 'ES', 'GU', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(658, 'ES', 'SS', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(659, 'ES', 'H', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(660, 'ES', 'HU', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(661, 'ES', 'J', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(662, 'ES', 'LO', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(663, 'ES', 'GC', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(664, 'ES', 'LE', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(665, 'ES', 'L', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(666, 'ES', 'LU', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(667, 'ES', 'M', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(668, 'ES', 'MA', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(669, 'ES', 'ML', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(670, 'ES', 'MU', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(671, 'ES', 'NA', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(672, 'ES', 'OR', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(673, 'ES', 'P', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(674, 'ES', 'PO', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(675, 'ES', 'SA', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(676, 'ES', 'TF', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(677, 'ES', 'SG', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(678, 'ES', 'SE', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(679, 'ES', 'SO', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(680, 'ES', 'T', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(681, 'ES', 'TE', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(682, 'ES', 'TO', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(683, 'ES', 'V', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(684, 'ES', 'VA', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(685, 'ES', 'BI', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(686, 'ES', 'ZA', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(687, 'ES', 'Z', 'A');
REPLACE INTO `?:states` (`state_id`, `country_code`, `code`, `status`) VALUES(688, 'BG', 'SF', 'A');

ALTER TABLE `?:settings_objects` CHANGE `value` `value` text NOT NULL;
DROP TABLE `?:sitemap_descriptions`;

UPDATE `?:language_values` SET value='It is strongly recommended that you rename the default <b>admin.php</b> script (check the <a href=\"http://kb.cs-cart.com/adminarea-protection\" target=\"_blank\">Knowledge base</a>) for security reasons.' WHERE lang_code='EN' AND name='warning_insecure_admin_script';
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'application_key', 'Application key') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'browser_upgrade_notice', '<p>We have detected that the browser you are using is not fully supported by the CS-Cart Admin Panel. You can view this site using your current browser but it may not display properly, and you may not be able to fully use all features.</p><br><p>CS-Cart Admin Panel is best viewed using the following browsers:</p><br><ul><li>&ndash; <a href=\"http://windows.microsoft.com/en-US/internet-explorer/products/ie/home\" target=\"_blank\">Internet Explorer 8 and above</a></li><li>&ndash; <a href=\"http://www.mozilla.org/en-US/\" target=\"_blank\">Mozilla Firefox (latest version)</a></li><li>&ndash; <a href=\"https://www.google.com/intl/en/chrome/browser/\" target=\"_blank\">Google Chrome (latest version)</a></li></ul><br><p>Click on one of the links to download the browser of your choice. Once the download has completed, install the browser by running the setup program.</p><br><p>If you cannot upgrade your browser now, you can still access CS-Cart Admin Panel, but you may not be able to fully use all features.<br><br><a href=\"[admin_index]\">Continue</a></p>') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'browser_upgrade_notice_title', 'Browser Upgrade Notice') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'cant_create_file', 'File could not be created') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'deposit_amount', 'Deposit amount') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'email_field_must_be_selected', 'The \'email\' field must be active at least in one of the Billing/Shipping address sections.') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'finance_product_code', 'Finance product code') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'gc_auto_charge', 'Enable auto charge') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'remove_cc_info', 'Remove CC info') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'text_pay4later_notice', '<b>Note</b>: In order to track your Pay4Later orders with the shopping cart software you have to take these steps:<br />
    <br />
    -&nbsp;Log in to Pay4Later BackOffice<br />
    -&nbsp;Click on the <u>\'Settings/Installations\'</u> link in the <u>\'Quick Links\'</u> section.<br />
    -&nbsp;Set <u>\'Return URL (Verified)\'</u> setting to:<br />
    <b>[verified_url]</b><br />
    -&nbsp;Set <u>\'Return URL (Decline)\'</u> setting to:<br />
    <b>[decline_url]</b><br />
    -&nbsp;Set <u>\'Return URL (Refer)\'</u> setting to:<br />
    <b>[refer_url]</b><br />
    -&nbsp;Set <u>\'Return URL (Cancel)\'</u> setting to:<br />
    <b>[cancel_url]</b><br />
    -&nbsp;Set <u>\'CSN URL\'</u> setting to:<br />
    <b>[process_url]</b><br />
    -&nbsp;Click on the <u>\'Save Changes\'</u> button<br />') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'text_payment_have_been_deleted', 'Payment have been deleted successfully.') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'text_payment_have_not_been_deleted', 'Payment cannot be deleted.') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;
INSERT INTO `?:language_values` (lang_code, name, value) VALUES ('EN', 'tt_views_block_manager_update_location_default', 'One location must be picked as default. Its Top and Bottom containers will be used in all locations.') ON DUPLICATE KEY UPDATE `lang_code` = `lang_code`;

DELETE FROM `?:state_descriptions` WHERE state_id IN (636,637,638,639,640,641,642,643,644,645,646,647,648,649,650,651,652,653,654,655,656,657,658,659,660,661,662,663,664,665,666,667,668,669,670,671,672,673,674,675,676,677,678,679,680,681,682,683,684,685,686,687,688);

REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(636, 'EN', 'A Coruña');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(637, 'EN', 'Álava');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(638, 'EN', 'Albacete');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(639, 'EN', 'Alicante');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(640, 'EN', 'Almería');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(641, 'EN', 'Asturias');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(642, 'EN', 'Ávila');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(643, 'EN', 'Badajoz');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(644, 'EN', 'Baleares');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(645, 'EN', 'Barcelona');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(646, 'EN', 'Burgos');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(647, 'EN', 'Cáceres');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(648, 'EN', 'Cádiz');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(649, 'EN', 'Cantabria');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(650, 'EN', 'Castellón');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(651, 'EN', 'Ceuta');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(652, 'EN', 'Ciudad Real');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(653, 'EN', 'Córdoba');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(654, 'EN', 'Cuenca');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(655, 'EN', 'Girona');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(656, 'EN', 'Granada');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(657, 'EN', 'Guadalajara');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(658, 'EN', 'Guipúzcoa');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(659, 'EN', 'Huelva');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(660, 'EN', 'Huesca');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(661, 'EN', 'Jaén');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(662, 'EN', 'La Rioja');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(663, 'EN', 'Las Palmas');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(664, 'EN', 'León');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(665, 'EN', 'Lleida');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(666, 'EN', 'Lugo');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(667, 'EN', 'Madrid');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(668, 'EN', 'Málaga');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(669, 'EN', 'Melilla');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(670, 'EN', 'Murcia');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(671, 'EN', 'Navarra');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(672, 'EN', 'Ourense');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(673, 'EN', 'Palencia');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(674, 'EN', 'Pontevedra');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(675, 'EN', 'Salamanca');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(676, 'EN', 'Santa Cruz de Tenerife');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(677, 'EN', 'Segovia');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(678, 'EN', 'Sevilla');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(679, 'EN', 'Soria');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(680, 'EN', 'Tarragona');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(681, 'EN', 'Teruel');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(682, 'EN', 'Toledo');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(683, 'EN', 'Valencia');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(684, 'EN', 'Valladolid');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(685, 'EN', 'Vizcaya');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(686, 'EN', 'Zamora');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(687, 'EN', 'Zaragoza');
REPLACE INTO `?:state_descriptions` (`state_id`, `lang_code`, `state`) VALUES(688, 'EN', 'Sofia');

UPDATE ?:destination_elements SET element = '688' WHERE element = '661' AND element_type = "S";
UPDATE ?:destination_elements SET element = '664' WHERE element = '660' AND element_type = "S";
UPDATE ?:destination_elements SET element = '661' WHERE element = '659' AND element_type = "S";
UPDATE ?:destination_elements SET element = '660' WHERE element = '658' AND element_type = "S";
UPDATE ?:destination_elements SET element = '659' WHERE element = '657' AND element_type = "S";
UPDATE ?:destination_elements SET element = '658' WHERE element = '656' AND element_type = "S";
UPDATE ?:destination_elements SET element = '657' WHERE element = '655' AND element_type = "S";
UPDATE ?:destination_elements SET element = '656' WHERE element = '654' AND element_type = "S";
UPDATE ?:destination_elements SET element = '655' WHERE element = '653' AND element_type = "S";
UPDATE ?:destination_elements SET element = '654' WHERE element = '652' AND element_type = "S";
UPDATE ?:destination_elements SET element = '653' WHERE element = '650' AND element_type = "S";
UPDATE ?:destination_elements SET element = '652' WHERE element = '649' AND element_type = "S";
UPDATE ?:destination_elements SET element = '650' WHERE element = '648' AND element_type = "S";
UPDATE ?:destination_elements SET element = '649' WHERE element = '647' AND element_type = "S";
UPDATE ?:destination_elements SET element = '648' WHERE element = '646' AND element_type = "S";
UPDATE ?:destination_elements SET element = '647' WHERE element = '645' AND element_type = "S";
UPDATE ?:destination_elements SET element = '646' WHERE element = '644' AND element_type = "S";
UPDATE ?:destination_elements SET element = '645' WHERE element = '643' AND element_type = "S";
UPDATE ?:destination_elements SET element = '643' WHERE element = '642' AND element_type = "S";
UPDATE ?:destination_elements SET element = '642' WHERE element = '641' AND element_type = "S";
UPDATE ?:destination_elements SET element = '641' WHERE element = '640' AND element_type = "S";
UPDATE ?:destination_elements SET element = '640' WHERE element = '639' AND element_type = "S";
UPDATE ?:destination_elements SET element = '639' WHERE element = '638' AND element_type = "S";
UPDATE ?:destination_elements SET element = '638' WHERE element = '637' AND element_type = "S";
UPDATE ?:destination_elements SET element = '637' WHERE element = '636' AND element_type = "S";
UPDATE ?:destination_elements SET element = '636' WHERE element = '651' AND element_type = "S";
