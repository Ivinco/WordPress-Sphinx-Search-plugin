<?php
/*
    WordPress Sphinx Search Plugin by Ivinco (opensource@ivinco.com), 2011.
    If you need commercial support, or if you’d like this plugin customized for your needs, we can help.

    Visit plugin website for the latest news:
    http://www.ivinco.com/software/wordpress-sphinx-search-plugin  

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

class SphinxService
{
    /**
     * @access private
     * @var object
     */
    var $_config = null;

    function SphinxService(SphinxSearch_Config $config)
    {
        $this->__construct($config);
    }
    
    function  __construct(SphinxSearch_Config $config)
    {
        $this->_config = $config;
    }

    /**
      * Start Sphinx search daemon
      *
      * @return string - output of start command
      */
    function start()
    {
        $this->stop(); //kill daemon if runned
        $command = $this->_config->get_option('sphinx_searchd'). " --config ".
                 $this->_config->get_option('sphinx_conf');
     	exec($command, $output, $retval);
        if ($retval !=0 || preg_match("#ERROR:#i", implode(" ", $output))){
            return array('err' => "Can not start searchd, try to start it manually.".
                    '<br/>Command: ' . $command);
        }
     	//echo implode("<br/>", $output);
     	$options['sphinx_running'] = 'true';
        $this->_config->update_admin_options($options);
     	return true;
    }
     /**
      * Stop Sphinx search daemon
      *
      * @return string - output of stop command
      */
    function stop()
    {
     	//stop Sphinx search daemon
        $output = '';
     	if ($this->is_sphinx_running()) {
            $command = $this->_config->get_option('sphinx_searchd'). " --config ".
                 $this->_config->get_option('sphinx_conf') . " --stop";
            exec($command, $output, $retval);
            if ($retval != 0 || preg_match("#ERROR:#", implode(" ", $output))){
                return array('err' => "Can not stop searchd, try to stop it manually. ".
                    '<br/>Command: ' . $command);
            }
            //echo implode("<br/>", $output);
     	}
     	$options['sphinx_running'] = 'false';

     	$this->_config->update_admin_options($options);
     	return true;
    }

    /**
      * Check running sphinx search daemon or not
      *
      * @return boolean
      */
     function is_sphinx_running()
     {
         if (file_exists($this->_config->get_option('sphinx_searchd_pid'))){
             $pid = file_get_contents($this->_config->get_option('sphinx_searchd_pid'));
             $pid = trim($pid);
             if ( file_exists("/proc/$pid") ){
                 return true;
             }             
         }
         return false;
     }
     /**
      * Parse sphinx conf and grab path to search pid file
      *
      * @param string $sphinx_conf filename
      * @return string
      */
     function get_searchd_pid_filename($sphinx_conf)
     {
     	$content = file_get_contents($sphinx_conf);
     	if (preg_match("#\bpid_file\s+=\s+(.*)\b#", $content, $m))
     	{
     		return $m[1];
     	}
     	return '';
     }

     /**
      * Parse sphinx conf and fetch path to main index
      *
      * @param string $sphinx_conf filename
      * @return string
      */
     function get_main_index_path($sphinx_conf)
     {
     	$content = file_get_contents($sphinx_conf);
        $sphinx_index_path = '';
     	if (preg_match("#\bpath\s+=\s+(.*)\b#", $content, $m))
     	{
            $sphinx_index_path = $m[1];
     	}
     	if (!$sphinx_index_path ||
            !file_exists($sphinx_index_path) ||
            !is_readable($sphinx_index_path)){
            $sphinx_path = $this->_config->get_option('sphinx_path');
            $sphinx_index_path = $sphinx_path . '/var/data';
            if (!file_exists($sphinx_index_path) || !is_readable($sphinx_index_path)){
                return '';
            }
        }
        return $sphinx_index_path;
     }

     function need_reindex($flag)
     {
     	if ($flag){
     		$fp = fopen(SPHINXSEARCH_REINDEX_FILENAME, 'w+');
     		fwrite($fp, '1');
     		fclose($fp);
     	}else{
     		if (file_exists(SPHINXSEARCH_REINDEX_FILENAME)){
     			unlink(SPHINXSEARCH_REINDEX_FILENAME);
     		}
     	}
     }

      /**
	 * Run Sphinx indexer to reindex content
	 *
	 * @return bool
	 */
    function reindex($index_name = '')
    {
     	if (!file_exists($this->_config->get_option('sphinx_searchd')) ||
            !file_exists($this->_config->get_option('sphinx_conf')) ||
            !file_exists($this->_config->get_option('sphinx_indexer'))){
            return  array('err' =>'Indexer: configuration files not found.');
	}elseif ('' == $this->_config->get_option('sphinx_index')){            
            return  array('err' =>'Indexer: Sphinx index prefix is not specified.');
	}else {
            $command = $this->_config->get_option('sphinx_indexer').
                    "  --rotate --config ".$this->_config->get_option('sphinx_conf');

            if(empty($index_name)){
                //reindex all indexes with restart searchd
                $command .= " ".
                    $this->_config->get_option('sphinx_index')."delta ".
                    $this->_config->get_option('sphinx_index')."main ".
                    $this->_config->get_option('sphinx_index')."stats ";
            } elseif (!empty ($index_name)) {
                //reindex only specified index with restart searchd
                $command .= " ".$this->_config->get_option('sphinx_index').$index_name;
            }
            
            exec($command, $output, $retval);
            //echo implode("<br/>", $output);
            if ($retval !=0 || preg_match("#ERROR:#", implode(" ", $output))){
                return  array('err' =>'Indexer: reindexing error, try to run it manually.' .
                    '<br/>Command: ' . $command);
            }
	}
	$this->need_reindex(false);
	$this->_config->update_admin_options();
	return true;
     }

    /**
    * Reindex delta indexes
    *
    */
    function reindex_delta()
    {
        $this->reindex('delta');
    }

    /**
    * Reindex main indexes
    *
    */
    function reindex_main()
    {
        $this->reindex('main');
    }

    function get_index_modify_time()
    {
        $sphinx_conf = $this->_config->get_option('sphinx_conf');
        $sphinx_index_path = $this->get_main_index_path($sphinx_conf);
        if (!$sphinx_index_path){
            return false;
        }

        $index_name = $this->_config->get_option('sphinx_index').'main.spi';
        $index_filename = $sphinx_index_path . '/'. $index_name;
        if (!file_exists($index_filename) || !is_readable($index_filename) ){
            return false;
        }

        $time = filemtime($index_filename);
        return $time;
    }
}

function GetCorrectMTime($filePath)
{

    $time = filemtime($filePath);

    $isDST = (date('I', $time) == 1);
    $systemDST = (date('I') == 1);

    $adjustment = 0;

    if($isDST == false && $systemDST == true)
        $adjustment = 3600;

    else if($isDST == true && $systemDST == false)
        $adjustment = -3600;

    else
        $adjustment = 0;

    return ($time + $adjustment);
}