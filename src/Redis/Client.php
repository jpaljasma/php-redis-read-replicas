<?php
/**
 * Created by PhpStorm.
 * User: jaan
 * Date: 3/25/15
 * Time: 11:47 AM
 */

namespace JP\Redis;


class Client {

    /**
     * @var \Redis
     */
    private $_adapter = null;

    /**
     * @var bool
     */
    private $_connected = false;

    const OPT_HOST = 'host';
    const OPT_PORT = 'port';
    const OPT_TIMEOUT = 'timeout';
    const OPT_DB_INDEX = 'db_index';
    const OPT_CACHE_PREFIX = 'cache_prefix';
    const OPT_MESSAGE = 'message';

    const INFO_CPU = 'CPU';

    private $_host = '127.0.0.1';
    private $_port = 6379;
    private $_timeout = 1.5;
    private $_dbIndex = 0;
    private $_maxConnectionAttempts = 3;

    private $_cpuLoad = 0.0;
    private $_cachePrefix = '';

    /**
     * @param string $cachePrefix
     * @return $this Provides fluent interface
     */
    public function setCachePrefix($cachePrefix)
    {
        // create Redis Cluster compatible cache prefix
        $this->_cachePrefix = sprintf('a{%x}z:',crc32($cachePrefix));
        return $this;
    }

    /**
     * @param array $server
     * @param array $options
     * @throws \Exception
     */
    function __construct( $server, $options = [] )
    {
        $this->_adapter = new \Redis();

        if(!is_array($server)) throw new \Exception('$server must be an array.');

        if(isset($server[self::OPT_HOST])) $this->_host = (string)$server[self::OPT_HOST];
        if(isset($server[self::OPT_PORT])) $this->_port = (int)$server[self::OPT_PORT];
        if(isset($server[self::OPT_TIMEOUT])) $this->_timeout = (float)$server[self::OPT_TIMEOUT];
        if(isset($server[self::OPT_DB_INDEX])) $this->_dbIndex = (float)$server[self::OPT_DB_INDEX];

        if(isset($options[self::OPT_CACHE_PREFIX])) $this->setCachePrefix($options[self::OPT_CACHE_PREFIX]);
    }

    /**
     * @return bool
     */
    protected function isConnected() {
        return $this->_connected;
    }

    /**
     * Connects to Redis instance
     *
     * @return bool
     * @throws \Exception
     */
    protected function connect() {
        if($this->isConnected()) return true;

        $result = false;
        $numAttempts = 0;
        $lastErr = null;

        while(!$result && ($numAttempts < $this->_maxConnectionAttempts)) {
            try {
                $result = @$this->_adapter->connect($this->_host, $this->_port, $this->_timeout);
                if(!$result) $lastErr = error_get_last();
            }
            catch(\Exception $e) {
                $result = false;
            }
            if(null !== $lastErr) {
                if(preg_match('/php_network_getaddresses/', $lastErr[self::OPT_MESSAGE])) {
                    // tried to connect to invalid host
                    throw new \Exception($lastErr[self::OPT_MESSAGE]);
                }
            }
            elseif(!$result) {
                usleep(100000*($numAttempts+1));
            }
            $numAttempts++;
        }

        if($result) {
            $this->_connected = true;
            $this->_adapter->select($this->_dbIndex);

            // fetch total CPU load stats
            $this->_cpuLoad = array_reduce($this->_adapter->info(self::INFO_CPU), function($carry, $item) { $carry += floatval($item); return $carry; }, $this->_cpuLoad);
            // use PHP serializer
            $this->_adapter->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

            // set cache prefix
            if($this->_cachePrefix && strlen($this->_cachePrefix)>0) $this->_adapter->setOption(\Redis::OPT_PREFIX, $this->_cachePrefix);
        }
        else {
            throw new \Exception('Could not connect to Redis after '.$this->_maxConnectionAttempts.' attempts.');
        }
        return $result;
    }

    /**
     * Connect to Redis server and return Redis instance
     * @return \Redis
     */
    public function getAdapter() {
        $this->connect();
        return $this->_adapter;
    }

}