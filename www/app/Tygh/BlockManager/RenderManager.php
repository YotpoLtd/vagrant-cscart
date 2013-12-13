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

namespace Tygh\BlockManager;

use Tygh\Debugger;
use Tygh\Registry;

class RenderManager
{
    const ADMIN = 'admin';
    const CUSTOMER = 'customer';

    /**
     * Current rendered location data
     * @var array Location data
     */
    private $_location;

    /**
     * Containers from current rendered location
     * @var array List of containers data
     */
    private $_containers;

    /**
     * Grids from current rendered location
     * @var array List of grids data
     */
    private $_grids;

    /**
     * Blocks from current rendered location
     * @var array List of blocks data
     */
    private $_blocks;

    /**
     * Current rendered area
     * @var string Current rendered area
     */
    private $_area;

    /**
     * Link to global Smarty object
     * @var Core Link to global Smarty object
     */
    private $_view;

    /**
     * Current theme name
     * @var string Current theme name
     */
    private $_theme;

    /**
     * @var array|bool
     */
    private $_dynamic_object_scheme;

    /**
     * @var array
     */
    private $_parent_grid;

    /**
     * Loads location data, containers, grids and blocks
     *
     * @param string $dispatch       URL dispatch (controller.mode.action)
     * @param string $area           Area ('A' for admin or 'C' for custom
     * @param array  $dynamic_object
     * @param int    $location_id
     * @param string $lang_code      2 letters language code
     */
    public function __construct($dispatch, $area, $dynamic_object = array(), $location_id = 0, $lang_code = DESCR_SL)
    {
        Debugger::checkpoint('Start render location');
        // Try to get location for this dispatch
        if ($location_id > 0) {
            $this->_location = Location::instance()->getById($location_id, $lang_code);
        } else {
            $this->_location = Location::instance()->get($dispatch, $dynamic_object, $lang_code);
        }

        $this->_area = $area;

        if (!empty($this->_location)) {
            if (isset($dynamic_object['object_id']) && $dynamic_object['object_id'] > 0) {
                $this->_containers = Container::getListByArea($this->_location['location_id'], 'C');
            } else {
                $this->_containers = Container::getListByArea($this->_location['location_id'], $this->_area);
            }

            $this->_grids = Grid::getList(array(
                'container_ids' => Container::getIds($this->_containers)
            ));

            $blocks = Block::instance()->getList(
                array('?:bm_snapping.*','?:bm_blocks.*', '?:bm_blocks_descriptions.*'),
                Grid::getIds($this->_grids),
                $dynamic_object,
                null,
                null,
                $lang_code
            );

            $this->_blocks = $blocks;

            $this->_view = Registry::get('view');
            $this->_theme = self::_getThemePath($this->_area);
            $this->_dynamic_object_scheme = SchemesManager::getDynamicObject($this->_location['dispatch'], 'C');
        }
    }

    /**
     * Renders current location
     * @return string HTML code of rendered location
     */
    public function render()
    {
        if (!empty($this->_location)) {

            $this->_view->assign('containers', array(
                'top_panel' => $this->_renderContainer($this->_containers['TOP_PANEL']),
                'header' => $this->_renderContainer($this->_containers['HEADER']),
                'content' => $this->_renderContainer($this->_containers['CONTENT']),
                'footer' => $this->_renderContainer($this->_containers['FOOTER']),
            ));

            Debugger::checkpoint('End render location');

            return $this->_view->fetch($this->_theme . 'location.tpl');
        } else {
            return '';
        }
    }

    /**
     * Renders container
     * @param  array  $container Container data to be rendered
     * @return string HTML code of rendered container
     */
    private function _renderContainer($container)
    {
        static $layout_width = 0;
        if (empty($layout_width)) {
            $layout_width = Registry::get('runtime.layout.width');
        }

        $content = '';
        $container['width'] = $layout_width;

        $this->_view->assign('container', $container);

        if (isset($this->_grids[$container['container_id']]) && ($this->_area == 'A' || $container['status'] != 'D')) {
            $grids = $this->_grids[$container['container_id']];

            $grids = fn_build_hierarchic_tree($grids, 'grid_id');
            $this->_parent_grid = array();

            foreach ($grids as $grid) {
                $content .= $this->_renderGrid($grid);
            }

            $this->_view->assign('content', $content);

            return $this->_view->fetch($this->_theme . 'container.tpl');

        } else {
            $this->_view->assign('content', '');

            if ($this->_area == 'A') {
                return $this->_view->fetch($this->_theme . 'container.tpl');
            }
        }

    }

    /**
     * Renders grid
     * @param  int    $grid Grid data to be rendered
     * @return string HTML code of rendered grid
     */
    private function _renderGrid($grid)
    {
        $content = '';

        if ($this->_area == 'A' || $grid['status'] != 'D') {
            if (isset($grid['children']) && !empty($grid['children'])) {
                $grid['children'] = fn_sort_array_by_key($grid['children'], 'grid_id');
                $parent_grid = $this->_parent_grid;
                $this->_parent_grid = $grid;

                foreach ($grid['children'] as $child_grid) {
                    $content .= $this->_renderGrid($child_grid);
                }

                $this->_parent_grid = $parent_grid;
            } else {
                $content .= $this->renderBlocks($grid);
            }
        }

        $this->_view->assign('content', $content);
        $this->_view->assign('parent_grid', $this->_parent_grid);
        $this->_view->assign('grid', $grid);

        return $this->_view->fetch($this->_theme . 'grid.tpl');
    }

    /**
     * Renders blocks in grid
     * @param  array  $grid Grid data
     * @return string HTML code of rendered blocks
     */
    public function renderBlocks($grid)
    {
        $content = '';

        if (isset($this->_blocks[$grid['grid_id']])) {
            foreach ($this->_blocks[$grid['grid_id']] as $block) {
                $block['status'] = self::correctStatusForDynamicObject($block, $this->_dynamic_object_scheme);

                /**
                 * Actions before render block
                 * @param array $grid Grid data
                 * @param array $block Block data
                 * @param object $this Current RenderManager object
                 * @param string $content Rendered content of blocks
                 */
                fn_set_hook('render_blocks', $grid, $block, $this, $content);

                if ($this->_area == 'C' && $block['status'] == 'D') {
                    // Do not render block in frontend if it disabled
                    continue;
                }

                $content .= self::renderBlock($block, $grid, $this->_area);
            }
        }

        return $content;
    }

    /**
     * Corrects status if this block has different status for some dynamic object
     * @param array $block Block data
     * @param $dynamic_object_scheme
     * @return string Status A or D
     */
    public static function correctStatusForDynamicObject($block, $dynamic_object_scheme)
    {
        $status = $block['status'];
        // If dynamic object defined correct status
        if (!empty($dynamic_object_scheme['key'])) {
            $status = 'A';
            $object_key = $dynamic_object_scheme['key'];

            if ($block['status'] == 'A' && in_array($_REQUEST[$object_key], $block['items_array'])) {
                // If block enabled globally and disabled for some dynamic object
                $status = 'D';
            } elseif ($block['status'] == 'D' && !in_array($_REQUEST[$object_key], $block['items_array'])) {
                // If block disabled globally and not enabled for some dynamic object
                $status = 'D';
            }
        }

        return $status;
    }

    /**
     * Renders block
     * @static
     * @param  array  $block             Block data to be rendered
     * @param  string $content_alignment Alignment of block (float left, float, right, width 100%)
     * @param  string $area              Area ('A' for admin or 'C' for custom
     * @return string HTML code of rendered block
     */
    /**
     * Renders block
     *
     * @static
     * @param  array  $block       Block data to be rendered
     * @param  array  $parent_grid Parent grid data
     * @param  string $area        Area name
     * @return string
     */
    public static function renderBlock($block, $parent_grid = array(), $area = 'C')
    {
        if (SchemesManager::isBlockExist($block['type'])) {
            $view = Registry::get('view');

            $view->assign('parent_grid', $parent_grid);
            $view->assign('content_alignment', $parent_grid['content_align']);

            if ($area == 'C') {
                return self::renderBlockContent($block);
            } elseif ($area == 'A') {
                $scheme = SchemesManager::getBlockScheme($block['type'], array());
                if (!empty($scheme['single_for_location'])) {
                    $block['single_for_location'] = true;
                }
                $view->assign('block_data', $block);

                return $view->fetch(self::_getThemePath($area) . 'block.tpl');
            }
        }

        return '';
    }

    /**
     * Renders block content
     * @static
     * @param  array  $block Block data for rendering content
     * @return string HTML code of rendered block content
     */
    public static function renderBlockContent($block)
    {
        // Do not render block if it disabled in the frontend
        if (isset($block['is_disabled']) && $block['is_disabled'] == 1) {
            return '';
        }

        $smarty = Registry::get('view');
        $_tpl_vars = $smarty->getTemplateVars(); // save state of original variables

        // By default block is displayed
        $display_block = true;

        self::_assignBlockSettings($block);

        // Assign block data from DB
        Registry::get('view')->assign('block', $block);

        $theme_path = self::getCustomerThemePath();

        $block_scheme = SchemesManager::getBlockScheme($block['type'], array());

        $cache_name = 'block_content_' . $block['block_id'] . '_' . $block['snapping_id'] . '_' . $block['type'] . '_' . $block['grid_id'] . '_' . $block['object_id'] . '_' . $block['object_type'];

        // Register cache
        self::_registerBlockCache($cache_name, $block_scheme);

        $block_content = '';

        if (isset($block_scheme['cache']) && Registry::isExist($cache_name) == true && self::allowCache()) {
            $block_content = Registry::get($cache_name);
        } else {
            if ($block['type'] == 'main') {
                $block_content = self::_renderMainContent();
            } else {
                Registry::get('view')->assign('title', $block['name']);

                if (!empty($block_scheme['content'])) {
                    foreach ($block_scheme['content'] as $template_variable => $field) {
                        /**
                         * Actions before render any variable of block content
                         * @param string $template_variable name of current block content variable
                         * @param array $field Scheme of this content variable from block scheme content section
                         * @param array $block_scheme block scheme
                         * @param array $block Block data
                         */
                        fn_set_hook('render_block_content_pre', $template_variable, $field, $block_scheme, $block);
                        $value = self::getValue($template_variable, $field, $block_scheme, $block);

                        // If block have not empty content - display it
                        if (empty($value)) {
                            $display_block = false;
                        }

                        Registry::get('view')->assign($template_variable, $value);
                    }
                }

                // Assign block data from scheme
                Registry::get('view')->assign('block_scheme', $block_scheme);
                if ($display_block && file_exists($theme_path . $block['properties']['template'])) {
                    $block_content = Registry::get('view')->fetch($block['properties']['template']);
                }
            }

            if (!empty($block['wrapper']) && file_exists($theme_path . $block['wrapper']) && $display_block) {
                Registry::get('view')->assign('content', $block_content);

                if ($block['type'] == 'main') {
                    Registry::get('view')->assign('title', !empty(\Smarty::$_smarty_vars['capture']['mainbox_title']) ? \Smarty::$_smarty_vars['capture']['mainbox_title'] : '', false);
                }
                $block_content = Registry::get('view')->fetch($block['wrapper']);
            } else {
                Registry::get('view')->assign('content', $block_content);
                $block_content = Registry::get('view')->fetch('views/block_manager/render/block.tpl');
            }

            if (isset($block_scheme['cache']) && $display_block == true && self::allowCache()) {
                Registry::set($cache_name, $block_content);
            }
        }

        $wrap_id = $smarty->getTemplateVars('block_wrap');

        $smarty->clearAllAssign();
        $smarty->assign($_tpl_vars); // restore original vars
        \Smarty::$_smarty_vars['capture']['title'] = null;

        if ($display_block == true) {
            if (!empty($wrap_id)) {
                $block_content = '<div id="' . $wrap_id . '">' . $block_content . '<!--' . $wrap_id . '--></div>';
            }

            return trim($block_content);
        } else {
            return '';
        }
    }

    /**
     * Returns true if cache used for blocks
     *
     * @static
     * @return bool true if we may use cahce, false otherwise
     */
    public static function allowCache()
    {
        $use_cache = true;
        if (Registry::ifGet('config.tweaks.disable_block_cache', false) || Registry::get('runtime.customizaton_mode.design') || Registry::get('runtime.customizaton_mode.translation')) {
            $use_cache = false;
        }

        return $use_cache;
    }

    /**
     * Renders content of main content block
     * @return string HTML code of rendered block content
     */
    private static function _renderMainContent()
    {
        $smarty = Registry::get('view');
        $content_tpl = $smarty->getTemplateVars('content_tpl');

        return !empty($content_tpl) ? $smarty->fetch($content_tpl) : '';
    }

    /**
     * Renders or gets value of some variable of block content
     * @param  string $template_variable name of current block content variable
     * @param  array  $field             Scheme of this content variable from block scheme content section
     * @param  array  $block_scheme      block scheme
     * @param  array  $block             Block data
     * @return string Rendered block content variable value
     */
    public static function getValue($template_variable, $field, $block_scheme, $block)
    {
        $value = '';
        // Init value by default
        if (isset($field['default_value'])) {
            $value = $field['default_value'];
        }

        if (isset($block['content'][$template_variable])) {
            $value = $block['content'][$template_variable];
        }

        if ($field['type'] == 'enum') {
            $value = Block::instance()->getItems($template_variable, $block, $block_scheme);
        }

        if ($field['type'] == 'function' && !empty($field['function'][0]) && is_callable($field['function'][0])) {
            $callable = array_shift($field['function']);
            array_unshift($field['function'], $value, $block, $block_scheme);
            $value = call_user_func_array($callable, $field['function']);
        }

        return $value;
    }

    /**
     * Registers block cache
     * @param string $cache_name   Cache name
     * @param array  $block_scheme Block scheme data
     */
    private static function _registerBlockCache($cache_name, $block_scheme)
    {
        if (isset($block_scheme['cache'])) {
            $additional_level = '';

            $default_handlers = fn_get_schema('block_manager', 'block_cache_properties');

            if (isset($block_scheme['cache']['update_handlers']) && is_array($block_scheme['cache']['update_handlers'])) {
                $handlers = $block_scheme['cache']['update_handlers'];
            } else {
                $handlers = array();
            }

            $cookie_data = fn_get_session_data();
            $cookie_data['all'] = $cookie_data;

            $additional_level .= self::_generateAdditionalCacheLevel($block_scheme['cache'], 'request_handlers', $_REQUEST);
            $additional_level .= self::_generateAdditionalCacheLevel($block_scheme['cache'], 'session_handlers', $_SESSION);
            $additional_level .= self::_generateAdditionalCacheLevel($block_scheme['cache'], 'cookie_handlers', $cookie_data);
            $additional_level .= self::_generateAdditionalCacheLevel($block_scheme['cache'], 'auth_handlers', $_SESSION['auth']);
            $additional_level .= '|path=' . Registry::get('config.current_path');
            $additional_level = !empty($additional_level) ? md5($additional_level) : '';

            $handlers = array_merge($handlers, $default_handlers['update_handlers']);

            $cache_level = isset($block_scheme['cache']['cache_level']) ? $block_scheme['cache']['cache_level'] : Registry::cacheLevel('html_blocks');
            Registry::registerCache($cache_name, $handlers, $cache_level . '__' . $additional_level);
        }
    }

    /**
     * Generates additional cache levels by storage
     *
     * @param  array  $cache_scheme Block cache scheme
     * @param  string $handler_name Name of handlers frocm block scheme
     * @param  array  $storage      Storage to find params
     * @return string Additional chache level
     */
    private static function _generateAdditionalCacheLevel($cache_scheme, $handler_name, $storage)
    {
        $additional_level = '';

        if (!empty($cache_scheme[$handler_name]) && is_array($cache_scheme[$handler_name])) {
            foreach ($cache_scheme[$handler_name] as $param) {
                $param = fn_strtolower(str_replace('%', '', $param));
                if (isset($storage[$param])) {
                    $additional_level .= '|' . $param . '=' . md5(serialize($storage[$param]));
                }
            }
        }

        return $additional_level;
    }

    /**
     * Removes compiled block templates
     * @return bool
     */
    public static function deleteTemplatesCache()
    {
        static $is_deleted = false;

        if (!$is_deleted) {

            // mark cache as outdated
            Registry::setChangedTables('bm_blocks');
            // run cache routines
            Registry::save();

            $is_deleted = true;
        }

        return $is_deleted;
    }

    /**
     * Assigns block properties data to template
     * @param array $block Block data
     */
    private static function _assignBlockSettings($block)
    {
        if (isset($block['properties']) && is_array($block['properties'])) {
            foreach ($block['properties'] as $name => $value) {
                Registry::get('view')->assign($name, $value);
            }
        }

    }

    /**
     * Returns customer theme path
     * @static
     * @return string Path to customer theme folder
     */
    public static function getCustomerThemePath()
    {
        return fn_get_theme_path('[themes]/[theme]/templates/', 'C');
    }

    /**
     * Returns theme path for different areas
     * @static
     * @param  string $area Area ('A' for admin or 'C' for custom
     * @return string Path to theme folder
     */
    private static function _getThemePath($area = 'C')
    {
        if ($area == 'C') {
            $area = self::CUSTOMER;
        } elseif ($area == 'A') {
            $area = self::ADMIN;
        }

        return 'views/block_manager/render/';
    }
}
