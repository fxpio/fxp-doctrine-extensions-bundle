<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\DoctrineExtensionsBundle\Util;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class SqlFilterUtil
{
    const ENABLE =  'enable';
    const DISABLE = 'disable';

    /**
     * Get the list of SQL Filter name must to be disabled.
     *
     * @param ObjectManager $om      The ObjectManager instance
     * @param array         $filters The list of SQL Filter
     * @param bool          $all     Force all SQL Filter
     *
     * @return array
     */
    public static function findFilters(ObjectManager $om, array $filters, $all = false)
    {
        if (!$om instanceof EntityManager || (empty($filters) && !$all)) {
            return array();
        }

        $all = ($all && !empty($filters)) ? false : $all;
        $enabledFilters = $om->getFilters()->getEnabledFilters();

        return self::doFindFilters($filters, $enabledFilters, $all);
    }

    /**
     * Do find filters.
     *
     * @param array $filters
     * @param array $enabledFilters
     * @param bool  $all
     *
     * @return array
     */
    public static function doFindFilters(array $filters, array $enabledFilters, $all)
    {
        $reactivateFilters = array();

        foreach ($enabledFilters as $name => $filter) {
            if (in_array($name, $filters) || $all) {
                $reactivateFilters[] = $name;
            }
        }

        return $reactivateFilters;
    }

    /**
     * Disable/Enable the SQL Filters.
     *
     * @param ObjectManager $om      The ObjectManager instance
     * @param string        $action  Value : disable|enable
     * @param array         $filters The list of SQL Filter
     */
    public static function actionFilter(ObjectManager $om, $action, array $filters)
    {
        if ($om instanceof EntityManager && (in_array($action, array(static::ENABLE, static::DISABLE)))) {
            foreach ($filters as $name) {
                $om->getFilters()->$action($name);
            }
        }
    }
}
