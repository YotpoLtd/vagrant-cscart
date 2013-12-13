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

namespace Tygh;

use Tygh\Registry;

class CompanySingleton
{
    /**
     * @var array Array of object instances
     */
    protected static $_instance;

    /**
     * @var int Company identifier
     */
    protected $_company_id;

    /**
     * Returns object instance of this class or create it if it is not exists.
     * @static
     * @param  int              $company_id Company identifier
     * @return CompanySingleton
     */
    public static function instance($company_id = 0)
    {
        $class_name = get_called_class();
        if (empty(self::$_instance[$class_name])) {
            self::$_instance[$class_name] = new $class_name();
        }

        self::$_instance[$class_name]->setCompany($company_id);

        return self::$_instance[$class_name];
    }

    public function setCompany($company_id)
    {
        if (empty($company_id) && Registry::get('runtime.company_id')) {
            $this->_company_id = Registry::get('runtime.company_id');
        } else {
            $this->_company_id = $company_id;
        }
    }

    public function getCompanyCondition($db_field)
    {
        $company_id = $this->_company_id;

        if (!$this->_company_id) {
            $company_id = '';
        }

        return fn_get_company_condition($db_field, true, $company_id);
    }

    /**
     * We Can create object only inside it
     */
    protected function __construct() {}
}
