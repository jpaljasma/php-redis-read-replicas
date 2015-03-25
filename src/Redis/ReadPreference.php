<?php
/**
 * Created by PhpStorm.
 * User: jaan
 * Date: 3/24/15
 * Time: 1:10 PM
 */

namespace JP\Redis;

/**
 * Class ReadPreference
 *
 * Read preferences
 * @package JP\Redis
 */
final class ReadPreference {

    /**
     * Read from any available node
     */
    const RP_ANY = 0;

    /**
     * Always read from primary
     */
    const RP_PRIMARY = 1;

    /**
     * Always read from secondary
     */
    const RP_SECONDARY = 2;

    /**
     * Try to read from primary, fall back to secondary if unavailable
     */
    const RP_PRIMARY_PREFERRED = 4;

    /**
     * Try to read from secondary, fall back to primary if unavailable
     */
    const RP_SECONDARY_PREFERRED = 8;

    /**
     * No constructor
     */
    private function __construct() {}

    /**
     * No cloning
     */
    private function __clone() {}

} 