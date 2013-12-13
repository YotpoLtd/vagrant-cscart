<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

namespace Tygh\Backend\Session;

class Redis implements IBackend
{

    private $r;
    private $_config;

    /**
     * Init backend
     *
     * @param array $config global configuration params
     *
     * @return bool true if backend was init correctly, false otherwise
     */
    public function __construct($config)
    {
        $this->r = new \Redis();
        $this->_config = array(
            'redis_server' => $config['session_redis_server'],
            'saas_uid' => !empty($config['saas_uid']) ? $config['saas_uid'] : null,
        );

        if ($this->r->connect($this->_config['redis_server']) == true) {
            $this->r->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

            return true;
        }

        return false;
    }

    /**
     * Read session data
     *
     * @param string $sess_id session ID
     *
     * @return mixed session data if exist, false otherwise
     */
    public function read($sess_id)
    {
        $session = $this->r->hGetAll($this->_id($sess_id));

        if (!empty($session)) {
            return $session['data'];
        }

        return false;
    }

    /**
     * Write session data
     *
     * @param string $sess_id session ID
     * @param array  $data    session data
     *
     * @return boolean always true
     */
    public function write($sess_id, $data)
    {
        $this->r->hmSet($this->_id($sess_id), $data);
        $this->r->setTimeout($this->_id($sess_id), SESSIONS_STORAGE_ALIVE_TIME); // here we do not separate active and stored sessions storages

        return true;
    }

    /**
     * Update session ID
     *
     * @param string $old_id old session ID
     * @param array  $new_id new session ID
     *
     * @return boolean always true
     */
    public function regenerate($old_id, $new_id)
    {
        $this->r->rename($this->_id($old_id), $this->_id($new_id));

        return true;
    }

    /**
     * Delete session data
     *
     * @param string $sess_id session ID
     *
     * @return boolean always true
     */
    public function delete($sess_id)
    {
        $this->r->del($this->_id($sess_id));

        return true;
    }

    /**
     * Garbage collector (do nothing as redis takes care about deletion of expired keys)
     *
     * @param int $max_lifetime session lifetime
     *
     * @return boolean always true
     */
    public function gc($max_lifetime)
    {
        return true;
    }

    /**
     * Generate prefix for session id to separate sessions with same ID but from different stores
     *
     * @param string $sess_id session ID
     *
     * @return string prefixed session ID
     */
    private function _id($sess_id)
    {
        return 'session:' . (!empty($this->_config['saas_uid']) ? $this->_config['saas_uid'] : ':') . $sess_id;
    }
}
