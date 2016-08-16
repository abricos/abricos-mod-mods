<?php

/**
 * @package Abricos
 * @subpackage Mods
 * @author Alexander Kuzmin <roosit@abricos.org>
 */
class ModsQuery {

    public static function ElementDownloadCounterUpdate(Ab_Database $db, $elName){
        $sql = "
			INSERT INTO ".$db->prefix."mods_download
			(elementname, counter, dateline, upddate) VALUES (
				'".bkstr($elName)."',
				1,
				".TIMENOW.",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				counter=counter+1,
				upddate=".TIMENOW."
		";
        $db->query_write($sql);
    }

    public static function ElementDownloadInfoList(Ab_Database $db, $elName = ''){
        $sql = "
			SELECT
				elementname as nm,
				counter as cnt,
				version as vs
			FROM ".$db->prefix."mods_download
		";
        return $db->query_read($sql);
    }

}
