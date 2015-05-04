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

$schema = array(
    'ult' => array( 
        'Ult\\F301_302',
        'Ult\\F302_303',
        'Ult\\F303_304',
        'Ult\\F304_305',
        'Ult\\F305_306',
        'Ult\\F306_401',
        'Ult\\F401_402',
        'Ult\\F402_403',
    ),
    'pro' => array(
        'Pro\\F224_225',
        'Pro\\F225_301',
        'Pro\\F301_302',
        'Pro\\F302_303',
        'Pro\\F303_304',
        'Pro\\F304_305',
        'Pro\\F305_306',
        'Pro\\F306_ult',
        'Ult\\F306_401',
        'Ult\\F401_402',
        'Ult\\F402_403',
    ),
    'mve' => array(
        'Mve\\F224_225',
        'Mve\\F225_301',
        'Mve\\F301_302',
        'Mve\\F302_303',
        'Mve\\F303_304',
        'Mve\\F304_305',
        'Mve\\F305_306',
        'Mve\\F306_401',
        'Mve\\F401_402',
        'Mve\\F402_403',
    ),
);

return $schema;
