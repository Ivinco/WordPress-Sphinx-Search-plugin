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

class SphinxService
{
    private $_config = null;
    
    public function  __construct(SphinxSearch_Config $config)
    {
        $this->_config = $config;
    }

    /**
      * Start Sphinx search daemon
      *
      * @return string - output of start command
      */
    public function start()
    {
        $this->stop(); //kill daemon if runned
        $command = $this->_config->get_option('sphinx_searchd'). " --config ".
                 $this->_config->get_option('sphinx_conf');
     	exec($command, $output, $retval);
        if ($retval !=0 || preg_match("#ERROR:#i", implode(" ", $output))){
            return array('err' => "Can't start searchd, try to start it manually.".
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
    public function stop()
    {
     	//stop Sphinx search daemon
        $output = '';
     	if ($this->is_sphinx_running()) {
            $command = $this->_config->get_option('sphinx_searchd'). " --config ".
                 $this->_config->get_option('sphinx_conf') . " --stop";
            exec($command, $output, $retval);
            if ($retval != 0 || preg_match("#ERROR:#", implode(" ", $output))){
                return array('err' => "Can't stop searchd, try to stop it manually. ".
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
     public function is_sphinx_running()
     {
         if (file_exists($this->_config->get_option('sphinx_searchd_pid'))){
             return true;
         } else {             
             return false;
         }
     }
     /**
      * Parse sphinx conf and grab path to search pid file
      *
      * @param unknown_type $sphinx_conf
      * @return unknown
      */
     public function get_searchd_pid($sphinx_conf)
     {
     	$content = file_get_contents($sphinx_conf);
     	//pid_file		= {sphinx_path}/var/log/searchd.pid
     	if (preg_match("#\bpid_file\s+=\s+(.*)\b#", $content, $m))
     	{
     		return $m[1];
     	}
     	return '';
     }

     public function need_reindex($flag)
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
    function reindex()
    {
     	if (!file_exists($this->_config->get_option('sphinx_searchd')) ||
            !file_exists($this->_config->get_option('sphinx_conf')) ||
            !file_exists($this->_config->get_option('sphinx_indexer'))){
            return  array('err' =>'Indexer: configuration files not found.');
	}elseif ('' == $this->_config->get_option('sphinx_index')){
            return  array('err' =>'Indexer: Sphinx index prefix is not specified.');
	}else {
            if ($this->is_sphinx_running()) {
                $rotate = '--rotate ';
            }else {
                $rotate = '';
            }
            //reindex all indexes with restart searchd
            $command = $this->_config->get_option('sphinx_indexer').
                    " --config ".$this->_config->get_option('sphinx_conf'). " ".
                    $this->_config->get_option('sphinx_index')."delta ".
                    $this->_config->get_option('sphinx_index')."main $rotate ";
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
}