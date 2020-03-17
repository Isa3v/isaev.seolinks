<?php
/**
 * @author Isaev Danil
 * @package Isaev\Seolinks
 * 
 * ORM сущность [isaev_seolinks]
 * Продробнее про работу с ORM битрикса 
 * @link [dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=05748]
 * Прежде чем вносить правки в этот файл, прочтите как работает ORM битрикса. 
 * Поле в БД автоматически не создается.
 */

namespace Isaev\Seolinks;

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class SeolinksTable extends \Bitrix\Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'isaev_seolinks';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('isaev.seolinks_LINKS_ID'),
            ),
            'ACTIVE' => array(
                'data_type' => 'boolean',
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('isaev.seolinks_LINKS_ACTIVE'),
            ),
            'SORT' => array(
                'data_type' => 'float',
                'title' => Loc::getMessage('isaev.seolinks_LINKS_SORT'),
            ),
            'FROM' => array(
                'data_type' => 'text',
                'required' => true,
                'title' => Loc::getMessage('isaev.seolinks_LINKS_FROM'),
            ),
            'TO' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('isaev.seolinks_LINKS_TO'),
            ),
            'REDIRECT' => array(
                'data_type' => 'boolean',
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('isaev.seolinks_LINKS_REDIRECT'),
            ),
            'META_H1' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('isaev.seolinks_LINKS_H1'),
            ),
            'CHAIN_ITEM' => array(
                'data_type' => 'boolean',
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('isaev.seolinks_LINKS_CHAIN_ITEM'),
            ),
            'META_TITLE' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('isaev.seolinks_LINKS_META_TITLE'),
            ),
            'META_DESCRIPTION' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('isaev.seolinks_LINKS_META_DESCRIPTION'),
            ),
            'TEXT' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('isaev.seolinks_LINKS_TEXT'),
            ),
            'TAG_NAME' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('isaev.seolinks_LINKS_TAG_NAME'),
            ),
            'GROUP_NAME' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('isaev.seolinks_LINKS_GROUP_NAME'),
            ),
        );
    }
}
