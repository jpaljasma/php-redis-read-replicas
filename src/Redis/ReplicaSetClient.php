<?php
/**
 * Created by PhpStorm.
 * User: jaan
 * Date: 3/24/15
 * Time: 1:05 PM
 */

namespace JP\Redis;


class ReplicaSetClient {

    private $_readPreference = ReadPreference::RP_PRIMARY;

    /**
     * @param int $readPreference
     * @return $this Provides fluent interface
     */
    public function setReadPreference($readPreference)
    {
        $this->_readPreference = $readPreference;
        return $this;
    }

    /**
     * @return int
     */
    public function getReadPreference()
    {
        return $this->_readPreference;
    }

    /**
     * @param array $options
     */
    function __construct( $options = [] )
    {

    }
}