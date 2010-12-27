<?php 
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
