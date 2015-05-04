<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

namespace Tygh\Addons;

class XmlScheme2 extends AXmlScheme
{
    public function getSections()
    {
        $sections = array();
        if (isset($this->_xml->settings->sections->section)) {
            foreach ($this->_xml->settings->sections->section as $section) {
                $_section = array(
                    'id' => (string) $section['id'],
                    'name' => (string) $section->name,
                    'translations' => $this->_getTranslations($section),
                    'edition_type' => $this->_getEditionType($section)
                );

                if (!empty($section['outside_of_form'])) {
                    $_section['separate'] = true;
                }

                $sections[] = $_section;
            }
        }

        return $sections;
    }

    public function getSettings($section_id)
    {
        $settings = array();

        $section = $this->_xml->xpath("//section[@id='$section_id']");

        if (!empty($section) && is_array($section)) {
            $section = current($section);

            if (isset($section->items->item)) {
                foreach ($section->items->item as $setting) {
                    $settings[] = $this->_getSettingItem($setting);
                }
            }
        }

        return $settings;
    }

    public function getSettingsLayout()
    {
        return isset($this->_xml->settings['layout']) ? (string) $this->_xml->settings['layout'] : parent::getSettingsLayout();
    }

    protected function getQueries($mode = '')
    {
        $edition = PRODUCT_EDITION;

        if (empty($mode) || $mode == 'install') {
            return $this->_xml->xpath("//queries/*[(@for='install' or not(@for)) and (contains(@editions, '{$edition}') or not(@editions))]");
        } else {
            return $this->_xml->xpath("//queries/*[@for='" . $mode . "' and (contains(@editions, '{$edition}') or not(@editions))]");
        }
    }

    protected function _getLangVarsSectionName()
    {
        return '//language_variables';
    }

    public function getDefaultLanguage()
    {
        return isset($this->_xml->default_language) ? (string) $this->_xml->default_language : DEFAULT_LANGUAGE;
    }

    public function getDependencies()
    {
        return (isset($this->_xml->compatibility->dependencies)) ? explode(',', (string) $this->_xml->compatibility->dependencies) : array();
    }

    public function getConflicts()
    {
        return (isset($this->_xml->compatibility->conflicts)) ? explode(',', (string) $this->_xml->compatibility->conflicts) : array();
    }
}
