<?php
/**
 * Created by PhpStorm.
 * User: jaan
 * Date: 3/24/15
 * Time: 1:05 PM
 */

namespace JP\Redis;


class ReplicaSetClient {

    const ROLE_READ = 1;
    const ROLE_WRITE = 2;

    const EXT_REDIS = 'redis';
    const ERR_NO_REDIS_EXT = 'Install PHP redis extension before using JP\Redis\ReplicaSetClient.';

    const OPT_READ_PREFERENCE = 'read_preference';

    private $_readPreference = ReadPreference::RP_PRIMARY;

    /**
     * @var Client
     */
    private $_primaryClient = null;

    /**
     * @var Client[]
     */
    private $_secondaryClients = [];

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
     * @param array $servers Array of servers
     * @param int $readPreference Read preference, defaults to a primary
     * @throws \Exception
     */
    function __construct( $servers, $readPreference = null )
    {
        if (!extension_loaded(self::EXT_REDIS)) {
            throw new \Exception(self::ERR_NO_REDIS_EXT);
        }

        // first server is always a primary
        $primary = array_shift($servers);
        $this->_primaryClient = new Client($primary);

        // configure any secondary server(s) if any
        if(sizeof($servers)) {
            foreach($servers as $secondary) {
                $this->_secondaryClients[] = new Client($secondary);
            }
        }

        if(null !== $readPreference) $this->setReadPreference($readPreference);
    }

    /**
     *
     * @param int $role Indicate whether you need adapter for reading or writing
     * @return \Redis
     */
    public function getAdapter( $role = self::ROLE_WRITE ) {
        if(
            ReadPreference::RP_PRIMARY !== $this->_readPreference  &&
            sizeof($this->_secondaryClients) > 0 &&
            self::ROLE_READ === $role
        ) {
            $sec = null;
            while(null === $sec && sizeof($this->_secondaryClients) > 0) {
                $numSecondaryServers = sizeof($this->_secondaryClients);
                // pick a read-only adapter based on read preference
                switch($this->_readPreference) {
                    case ReadPreference::RP_ANY:
                        $n = mt_rand(0, $numSecondaryServers);
                        if( $n > 0 ) {
                            $cli = $this->_secondaryClients[$n-1];
                            try {
                                $sec = $cli->getAdapter();
                                return $sec;
                            }
                            catch(\Exception $ex) {
                                // could not connect
                                unset($this->_secondaryClients[$n-1]);
                                $this->_secondaryClients = array_values($this->_secondaryClients);
                                $sec = null;
                            }
                        }
                        else {
                            return $this->_primaryClient->getAdapter();
                        }
                        break;
                    case ReadPreference::RP_SECONDARY:
                    case ReadPreference::RP_SECONDARY_PREFERRED:
                        $n = mt_rand(0, $numSecondaryServers-1);
                        $cli = $this->_secondaryClients[$n];
                        try {
                            $sec = $cli->getAdapter();
                            return $sec;
                        }
                        catch(\Exception $ex) {
                            // could not connect
                            unset($this->_secondaryClients[$n]);
                            $this->_secondaryClients = array_values($this->_secondaryClients);
                            $sec = null;
                        }
                        break;
                    default:
                        // fall back to primary
                        return $this->_primaryClient->getAdapter();
                        break;
                }
            }
            if(!$sec) {
                // no secondaries available, connect to primary
                return $this->_primaryClient->getAdapter();
            }
        }
        else {
            // just primary
            return $this->_primaryClient->getAdapter();
        }
    }

    /**
     * Returns Redis adapter for writing
     * @return \Redis
     */
    public function getWriteAdapter() {
        return $this->getAdapter(self::ROLE_WRITE);
    }

    /**
     * Returns Redis adapter for reading
     * @return \Redis
     */
    public function getReadAdapter() {
        return $this->getAdapter(self::ROLE_READ);
    }

}