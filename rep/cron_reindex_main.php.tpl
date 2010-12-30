<?php
/*
    Copyright 2008  &copy; Ivinco LTD  (email : opensource@ivinco.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
 * this is new indexing method, used since version 2.0
 * Wordpress Main index update file
 * Add the following cron job to update main index daily (at 0 hours and 5 minutes):
 */
// 5 0 * * * /usr/bin/php {path_to_sphinx}/cron/cron_reindex_main.php

define('PATH_TO_SPHINX_INDEXER', '{path_to_indexer}');
define('PATH_TO_SPHINX_CONFIG', '{path_to_config}');
define('SPHINX_INDEX_NAME', '{index_prefix}');

$command = PATH_TO_SPHINX_INDEXER." --config ".PATH_TO_SPHINX_CONFIG." ".SPHINX_INDEX_NAME."main --rotate ";
system($command, $retval);
