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

$schema['central']['website']['items']['news'] = array(
    'attrs' => array(
        'class'=>'is-addon'
    ),
    'href' => 'news.manage',
    'position' => 200
);

$schema['central']['website']['items']['newsletters'] = array(
    'attrs' => array(
        'class'=>'is-addon'
    ),
    'href' => 'newsletters.manage',
    'position' => 201,
    'alt' => 'mailing_lists.manage,subscribers.manage',
    'subitems' => array(
        'newsletters' => array(
            'href' => 'newsletters.manage?type=N',
            'position' => 203
        ),
        'templates' => array(
            'href' => 'newsletters.manage?type=T',
            'position' => 204
        ),
        'autoresponders' => array(
            'href' => 'newsletters.manage?type=A',
            'position' => 205
        ),
        'campaigns' => array(
            'href' => 'newsletters.campaigns',
            'position' => 206
        ),
        'mailing_lists' => array(
            'href' => 'mailing_lists.manage',
            'position' => 207
        ),
        'subscribers_menu_item_text' => array(
            'href' => 'subscribers.manage',
            'position' => 208
        ),
    )
);

$schema['top']['addons']['items']['export_data']['subitems']['subscribers'] = array(
    'href' => 'exim.export?section=subscribers',
    'position' => 201
);

$schema['top']['addons']['items']['import_data']['subitems']['subscribers'] = array(
    'href' => 'exim.import?section=subscribers',
    'position' => 202
);

$schema['top']['administration']['items']['export_data']['subitems']['subscribers'] = array(
    'href' => 'exim.export?section=subscribers',
    'position' => 201
);

$schema['top']['administration']['items']['import_data']['subitems']['subscribers'] = array(
    'href' => 'exim.import?section=subscribers',
    'position' => 201
);

return $schema;
