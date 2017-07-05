<?php
/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * BannerLogic - Frontend Helper Class
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @licence    LGPL
 * @filesource
 * @package    Banner
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\Banner;

/**
 * Class BannerLogic
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 */
 class BannerLogic
{

    /**
     * Get weighting for single banner
     * 
     * @param    $arrAllBannersBasic    [id,weighting]
     *
     * @return integer    0|1|2|3    0 on error
     */
    public static function getSingleWeighting($arrAllBannersBasic)
    {
        $arrPrio = array();
        $arrPrioW = array();
        $arrWeights = array_flip($arrAllBannersBasic);
    
        //welche Wichtungen gibt es?
        if (array_key_exists(1, $arrWeights)) {
            $arrPrioW[1] = 1;
        };
        if (array_key_exists(2, $arrWeights)) {
            $arrPrioW[2] = 2;
        };
        if (array_key_exists(3, $arrWeights)) {
            $arrPrioW[3] = 3;
        };
    
        $arrPrio[0] = array('start'=>0,  'stop'=>0);
        $arrPrio[1] = array('start'=>1,  'stop'=>90);
        $arrPrio[2] = array('start'=>91, 'stop'=>150);
        $arrPrio[3] = array('start'=>151,'stop'=>180);
        if ( !array_key_exists(2, $arrPrioW) )
        {
            // no prio 2 banner
            $arrPrio[2] = array('start'=>0,  'stop'=>0);
            $arrPrio[3] = array('start'=>91, 'stop'=>120);
        }
        $intPrio1 = (count($arrPrioW)) ? min($arrPrioW) : 0 ;
        $intPrio2 = (count($arrPrioW)) ? max($arrPrioW) : 0 ;
    
        //wenn Wichtung vorhanden, dann per Zufall eine auswählen
        if ($intPrio1>0)
        {
            $intWeightingHigh = mt_rand($arrPrio[$intPrio1]['start'],$arrPrio[$intPrio2]['stop']);
    
            // 1-180 auf 1-3 umrechnen
            if ($intWeightingHigh<=$arrPrio[3]['stop'])
            {
                $intWeighting=3;
            }
            if ($intWeightingHigh<=$arrPrio[2]['stop'])
            {
                $intWeighting=2;
            }
            if ($intWeightingHigh<=$arrPrio[1]['stop'])
            {
                $intWeighting=1;
            }
        }
        else
        {
            $intWeighting=0;
        }
        return $intWeighting;
    }
}
