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

    const INFO_CPU = 'CPU';

    private $_host = '127.0.0.1';
    private $_port = 6379;
    private $_timeout = 2.0;
    private $_dbIndex = 0;
    private $_maxConnectionAttempts = 3;

    private $_cpuLoad = 0.0;

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

        while(!$result && ($numAttempts < $this->_maxConnectionAttempts)) {
            try {
                $result = $this->_adapter->connect($this->_host, $this->_port, $this->_timeout);
            }
            catch(\Exception $e) {
                var_dump($e);
                $result = false;
            }
            if(!$result) {
                usleep(300000*($numAttempts+1));
            }
            $numAttempts++;
        }

        if($result) {
            $this->_connected = true;
            $this->_adapter->select($this->_dbIndex);

            // fetch total CPU load stats
            $this->_cpuLoad = array_reduce($this->_adapter->info(self::INFO_CPU), function($carry, $item) { $carry += floatval($item); return $carry; }, $this->_cpuLoad);
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