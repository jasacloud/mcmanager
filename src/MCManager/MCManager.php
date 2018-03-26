<?php
define('MEMQ_TTL', 0);
class MCManager
{
    //private $config;
    private $m;
    private $client_type;
    private $key_encryption;
    //private $ci;
    protected $errors = [];

    public function __construct()
    {
        //$this->ci = &get_instance();
        // Load the memcached library config
        //$this->ci->load->config('memcached');
        //$this->config = $this->ci->config->item('memcached');

        // Lets try to load Memcache or Memcached Class
        $this->client_type = isset(MEMCACHED_CONFIG['engine']) ? MEMCACHED_CONFIG['engine'] : false;
        $this->key_encryption = isset(MEMCACHED_CONFIG['key_encryption']) ? MEMCACHED_CONFIG['key_encryption'] : false;

        if ($this->client_type) {
            // Which one should be loaded
            switch ($this->client_type) {
                case 'Memcached':
                    $this->m = new Memcached();
                    break;
                case 'Memcache':
                    $this->m = new Memcache();
                    // Set Automatic Compression Settings
                    if (MEMCACHED_CONFIG['auto_compress_tresh']) {
                        $this->setcompressthreshold(MEMCACHED_CONFIG['auto_compress_tresh'], MEMCACHED_CONFIG['auto_compress_savings']);
                    }
                    break;
            }
            //log_message('debug', 'Memcached Library: '.$this->client_type.' Class Loaded');
			//new Logger($_SERVER['DOCUMENT_ROOT'].'/log/Memcache.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.' #MCManager::__construct()#'.'Memcached Library: '.$this->client_type.' Class Loaded');

            $this->auto_connect();
        } else {
            //log_message('error', 'Memcached Library: Failed to load Memcached or Memcache Class');
			new Logger($_SERVER['DOCUMENT_ROOT'].'/log/Memcache.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.' #MCManager::__construct()#'.'Memcached Library: Failed to load Memcached or Memcache Class, Must be setting in /config/config.php');
        }
    }

    /*
    +-------------------------------------+
        Name: auto_connect
        Purpose: runs through all of the servers defined in
        the configuration and attempts to connect to each
        @param return : none
    +-------------------------------------+
    */

    private function auto_connect()
    {
        foreach (MEMCACHED_SERVER as $key => $server) {
            if (!$this->add_server($server)) {
                $this->errors[] = "Memcached Library: Could not connect to the server named $key";
                //log_message('error', 'Memcached Library: Could not connect to the server named "'.$key.'"');
                new Logger($_SERVER['DOCUMENT_ROOT'].'/log/Memcache.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.' #MCManager::auto_connect()#'.'Memcached Library: Could not connect to the server named "'.$key.'"');
            } else {
                //log_message('debug', 'Memcached Library: Successfully connected to the server named "'.$key.'"');
				//new Logger($_SERVER['DOCUMENT_ROOT'].'/log/Memcache.log', $_SERVER['PHP_SELF'].':'.__LINE__.'  '.' #MCManager::auto_connect()#'.'Memcached Library: Successfully connected to the server named "'.$key.'"');
            }
        }
    }

    /*
    +-------------------------------------+
        Name: add_server
        Purpose:
        @param return : TRUE or FALSE
    +-------------------------------------+
    */

    public function add_server($server)
    {
        extract($server);

        return $this->m->addServer($host, $port, $weight);
    }

    /*
    +-------------------------------------+
        Name: add
        Purpose: add an item to the memcache server(s)
        @param return : TRUE or FALSE
    +-------------------------------------+
    */

    public function add($key = null, $value = null, $expiration = null)
    {
        if (is_null($expiration)) {
            $expiration = MEMCACHED_CONFIG['expiration'];
        }
        if (is_array($key)) {
            foreach ($key as $multi) {
                if (!isset($multi['expiration']) || $multi['expiration'] == '') {
                    $multi['expiration'] = MEMCACHED_CONFIG['expiration'];
                }
                $this->add($this->key_name($multi['key']), $multi['value'], $multi['expiration']);
            }
        } else {
            switch ($this->client_type) {
                case 'Memcache':
                    $add_status = $this->m->add($this->key_name($key), $value, MEMCACHED_CONFIG['compression'], $expiration);
                    break;

                default:
                case 'Memcached':
                    $add_status = $this->m->add($this->key_name($key), $value, $expiration);
                    break;
            }

            return $add_status;
        }
    }

    /*
    +-------------------------------------+
        Name: set
        Purpose: similar to the add() method but uses set
        @param return : TRUE or FALSE
    +-------------------------------------+
    */

    public function set($key = null, $value = null, $expiration = null)
    {
        if (is_null($expiration)) {
            $expiration = MEMCACHED_CONFIG['expiration'];
        }
        if (is_array($key)) {
            foreach ($key as $multi) {
                if (!isset($multi['expiration']) || $multi['expiration'] == '') {
                    $multi['expiration'] = MEMCACHED_CONFIG['expiration'];
                }
				switch ($this->client_type) {
					case 'Memcache':
						$add_status = $this->m->set($this->key_name($multi['key']), $multi['value'], MEMCACHED_CONFIG['compression'], $multi['expiration']);
						break;
					default:
					case 'Memcached':
						$add_status = $this->m->set($this->key_name($multi['key']), $multi['value'], $multi['expiration']);
						break;
				}
            }
			
			return $add_status;
        } else {
            switch ($this->client_type) {
                case 'Memcache':
                    $add_status = $this->m->set($this->key_name($key), $value, MEMCACHED_CONFIG['compression'], $expiration);
                    break;

                default:
                case 'Memcached':
                    $add_status = $this->m->set($this->key_name($key), $value, $expiration);
                    break;
            }

            return $add_status;
        }
    }
	/*
    +-------------------------------------+
        Name: append
        Purpose: append value to existing key
        @param return : TRUE or FALSE
    +-------------------------------------+
    */

    public function append($key = null, $value = null, $expiration = null)
    {
        if (is_null($expiration)) {
            $expiration = MEMCACHED_CONFIG['expiration'];
        }
        if (is_array($key)) {
            foreach ($key as $multi) {
                if (!isset($multi['expiration']) || $multi['expiration'] == '') {
                    $multi['expiration'] = MEMCACHED_CONFIG['expiration'];
                }
                $this->append($this->key_name($multi['key']), $multi['value'], $multi['expiration']);
            }
        } else {
            switch ($this->client_type) {
                case 'Memcache':
                    $add_status = $this->m->append($this->key_name($key), $value, MEMCACHED_CONFIG['compression'], $expiration);
                    break;

                default:
                case 'Memcached':
                    $add_status = $this->m->append($this->key_name($key), $value, $expiration);
                    break;
            }

            return $add_status;
        }
    }

    /*
    +-------------------------------------+
        Name: get
        Purpose: gets the data for a single key or an array of keys
        @param return : array of data or multi-dimensional array of data
    +-------------------------------------+
    */

    public function get($key = null)
    {
        if ($this->m) {
            if (is_null($key)) {
                $this->errors[] = 'The key value cannot be NULL';

                return false;
            }

            if (is_array($key)) {
				switch($this->client_type){
					case "Memcache":
						foreach ($key as $n => $k) {
							$key[$n] = $this->key_name($k);
							$flag[] = MEMCACHED_CONFIG['compression'];
						}

						return $this->m->get($key,$flag);
					break;
					
					case 'Memcached': 
					default:
						foreach ($key as $n => $k) {
							$key[$n] = $this->key_name($k);
						}

						return $this->m->getMulti($key);
					break;
				}
            } else {
                return $this->m->get($this->key_name($key));
            }
        }

        return false;
    }
	
	public function getAllKeys(){
		return $this->m->getAllKeys();
	}

    /*
    +-------------------------------------+
        Name: delete
        Purpose: deletes a single or multiple data elements from the memached servers
        @param return : none
    +-------------------------------------+
    */

    public function delete($key, $expiration = null)
    {
        if (is_null($key)) {
            $this->errors[] = 'The key value cannot be NULL';

            return false;
        }

        if (is_null($expiration)) {
            $expiration = MEMCACHED_CONFIG['delete_expiration'];
        }

        if (is_array($key)) {
            foreach ($key as $multi) {
                $this->delete($multi, $expiration);
            }
        } else {
            return $this->m->delete($this->key_name($key), $expiration);
        }
    }

    /*
    +-------------------------------------+
        Name: replace
        Purpose: replaces the value of a key that already exists
        @param return : none
    +-------------------------------------+
    */

    public function replace($key = null, $value = null, $expiration = null)
    {
        if (is_null($expiration)) {
            $expiration = MEMCACHED_CONFIG['expiration'];
        }
        if (is_array($key)) {
            foreach ($key as $multi) {
                if (!isset($multi['expiration']) || $multi['expiration'] == '') {
                    $multi['expiration'] = MEMCACHED_CONFIG['expiration'];
                }
                $this->replace($multi['key'], $multi['value'], $multi['expiration']);
            }
        } else {
            switch ($this->client_type) {
                case 'Memcache':
                    $replace_status = $this->m->replace($this->key_name($key), $value, MEMCACHED_CONFIG['compression'], $expiration);
                    break;

                default:
                case 'Memcached':
                    $replace_status = $this->m->replace($this->key_name($key), $value, $expiration);
                    break;
            }

            return $replace_status;
        }
    }

    /*
    +-------------------------------------+
        Name: increment
        Purpose: increments a value
        @param return : none
    +-------------------------------------+
    */

    public function increment($key = null, $by = 1)
    {
        return $this->m->increment($this->key_name($key), $by);
    }

    /*
    +-------------------------------------+
        Name: decrement
        Purpose: decrements a value
        @param return : none
    +-------------------------------------+
    */

    public function decrement($key = null, $by = 1)
    {
        return $this->m->decrement($this->key_name($key), $by);
    }

    /*
    +-------------------------------------+
        Name: flush
        Purpose: flushes all items from cache
        @param return : none
    +-------------------------------------+
    */

    public function flush()
    {
        return $this->m->flush();
    }

    /*
    +-------------------------------------+
        Name: getversion
        Purpose: Get Server Vesion Number
        @param Returns a string of server version number or FALSE on failure.
    +-------------------------------------+
    */

    public function getversion()
    {
        return $this->m->getVersion();
    }

    /*
    +-------------------------------------+
        Name: getstats
        Purpose: Get Server Stats
        Possible: "reset, malloc, maps, cachedump, slabs, items, sizes"
        @param returns an associative array with server's statistics. Array keys correspond to stats parameters and values to parameter's values.
    +-------------------------------------+
    */

    public function getstats($type = 'items')
    {
        switch ($this->client_type) {
            case 'Memcache':
                $stats = $this->m->getStats($type);
                break;

            default:
            case 'Memcached':
                $stats = $this->m->getStats();
                break;
        }

        return $stats;
    }

    /*
    +-------------------------------------+
        Name: setcompresstreshold
        Purpose: Set When Automatic compression should kick-in
        @param return TRUE/FALSE
    +-------------------------------------+
    */

    public function setcompressthreshold($tresh, $savings = 0.2)
    {
        switch ($this->client_type) {
            case 'Memcache':
                $setcompressthreshold_status = $this->m->setCompressThreshold($tresh, $savings = 0.2);
                break;

            default:
                $setcompressthreshold_status = true;
                break;
        }

        return $setcompressthreshold_status;
    }

	/*
    +-------------------------------------+
        Name: setEncryption
        Purpose: Set the encryption for key
        @param return TRUE/FALSE
    +-------------------------------------+
    */

    public function setEncryption($encryption)
    {
        $this->key_encryption = $encryption;
		
		return $this->key_encryption;
    }
	
    /*
    +-------------------------------------+
        Name: key_name
        Purpose: standardizes the key names for memcache instances
        @param return : md5 key name
    +-------------------------------------+
    */

    private function key_name($key)
    {
		if($this->key_encryption){
		
			return md5(strtolower(MEMCACHED_CONFIG['prefix'].$key));
		}
		else{
		
			return MEMCACHED_CONFIG['prefix'].$key;
		}
    }

    /*
    +--------------------------------------+
        Name: isConected
        Purpose: Check if the memcache server is connected.
    +--------------------------------------+
    */

    public function isConnected()
    {
        foreach ($this->getstats() as $key => $server) {
            if ($server['pid'] == -1) {
                return false;
            }

            return true;
        }
    }
	
	 /*
    +--------------------------------------+
        Name: is_empty
        Purpose: Check if the MEMQ is emty or not.
    +--------------------------------------+
    */
	
	public function is_empty($queue){
	
		$head = $this->m->get($queue."_head");
		$tail = $this->m->get($queue."_tail");
			
		if($head >= $tail || $head === FALSE || $tail === FALSE)
			return TRUE;
		else 
			return FALSE;
	}
	public function enqueue($queue, $item){

		$id = $this->m->increment($queue."_tail");
		if($id === FALSE){
			if($this->m->add($queue."_tail", 1, MEMQ_TTL) === FALSE){
				$id = $this->m->increment($queue."_tail");
				if($id === FALSE) 
					return FALSE;
			}
			else{
				$id = 1;
				$this->m->add($queue."_head", $id, MEMQ_TTL);
			}
		}
		
		if($this->m->add($queue."_".$id, $item, MEMQ_TTL) === FALSE) 
			return FALSE;
		
		return $id;
	}
	public function dequeue($queue, $after_id=FALSE, $till_id=FALSE){
		
		if($after_id === FALSE && $till_id === FALSE){
			$tail = $this->m->get($queue."_tail");
			if(($id = $this->m->increment($queue."_head")) === FALSE) 
				return FALSE;
		
			if($id <= $tail){
				return $this->m->get($queue."_".($id-1));
			}
			else{
				$this->m->decrement($queue."_head");
				return FALSE;
			}
		}
		else if($after_id !== FALSE && $till_id === FALSE){
			$till_id = $this->m->get($queue."_tail");	
		}
		
		$item_keys = array();
		for($i=$after_id+1; $i<=$till_id; $i++) 
			$item_keys[] = $queue."_".$i;
		$null = NULL;
		
		return $this->m->getMulti($item_keys, $null, Memcached::GET_PRESERVE_ORDER); 
	}
}
