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

if (!file_exists('reindex_config.php')){
	echo "File 'reindex_config.php' is missing.
 Example of this file is placed at cron folder of the plugin.\n";
        exit(0);
}
include_once('reindex_config.php');

define('SPHINXSEARCH_PLUGIN_DIR', dirname(dirname(__FILE__)));
define('SPHINXSEARCH_REINDEX_FILENAME', dirname(dirname(SPHINXSEARCH_PLUGIN_DIR)).'/uploads/need_reindex');

if (count($argv) != 2 || !in_array($argv[1], array('delta', 'main'))){
	echo " Reindexer of Sphinx index files, by Percona Ltd.
 Usage: php reindex.php [delta|main]
 Example: reindex.php delta
 It reindexes your delta index\n";
	exit(0);
}

if (file_exists(SPHINXSEARCH_REINDEX_FILENAME) || $argv[1] == 'main')
{
	$command = PATH_TO_SPHINX_INDEXER." --config ".PATH_TO_SPHINX_CONFIG." ".SPHINX_INDEX_NAME.$argv[1]." --rotate ";
	system($command, $retval);
	
	echo "\ndone...\n";	
	if (file_exists(SPHINXSEARCH_REINDEX_FILENAME)){
		unlink(SPHINXSEARCH_REINDEX_FILENAME);
	}
	exit($retval);
}
else {
	echo "nothing to index...\n";
	exit(0);
}
