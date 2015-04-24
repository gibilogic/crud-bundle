<?php

/**
 * @package     Gibilogic\CrudBundle
 * @subpackage  Entity
 * @author      GiBiLogic <info@gibilogic.com>
 * @authorUrl   http://www.gibilogic.com
 */

namespace Gibilogic\CrudBundle\Entity;

/**
 * Sluggable trait.
 */
trait SluggableTrait
{

    /**
     * Returns the slugified version of the string.
     *
     * @param string $string
     *
     * @return string
     */
    protected function slugify($string, $separator = '-')
    {
        if (empty($string)) {
            return null;
        }

        $replacement = array(
            'a' => array('à', 'á', 'â', 'ã', 'å', 'À', 'Á', 'Â', 'Ã', 'Å'),
            'ae' => array('æ', 'Æ', 'ä', 'Ä'),
            'and' => array('&amp;', '&'),
            'c' => array('ç', 'Ç', '©'),
            'd' => array('∂'),
            'e' => array('è', 'é', 'ê', 'ë', 'È', 'É', 'Ê', 'Ë', '€'),
            'i' => array('ì', 'í', 'î', 'ï', 'Ì', 'Í', 'Î', 'Ï'),
            'n' => array('ñ', 'Ñ'),
            'o' => array('ò', 'ó', 'ô', 'õ', 'ø', 'Ò', 'Ó', 'Ô', 'Õ', 'Ø'),
            'oe' => array('œ', 'Œ', 'ö', 'Ö'),
            'r' => array('®'),
            's' => array('$'),
            'ss' => array('ß'),
            'u' => array('ù', 'ú', 'û', 'µ', 'Ù', 'Ú', 'Û'),
            'ue' => array('ü', 'Ü'),
            'y' => array('ÿ', 'Ÿ', '¥'),
            'tm' => array('™'),
            'pi' => array('∏', 'π', 'Π'),
            ' ' => array("'", "`"),
        );

        $string = (string) str_replace(array("\r", "\n"), '', $string);
        foreach ($replacement as $output => $input) {
            $string = str_replace($input, $output, $string);
        }

        return preg_replace('#(' . $separator . '+)#', $separator, preg_replace('#[\s]+#', $separator, rtrim(trim(preg_replace('#[^a-z0-9.\s]#', ' ', mb_strtolower($string, mb_internal_encoding()))))));
    }
}
