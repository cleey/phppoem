<?php
namespace poem\session;
class redis extends \SessionHandler {
    protected $maxtime = '';
    protected $table   = '';

    public function open($savePath, $session_id) {
        $this->maxtime = ini_get('session.gc_maxlifetime');
        $this->table   = config('session_table') ?: "session";

        return true;
    }

    public function close() {
        return true;
    }

    public function read($session_id) {
        return redis()->get($this->table . $session_id);
    }

    public function write($session_id, $session_data) {
        return redis()->setex($this->table . $session_id, $this->maxtime, $session_data);
    }

    public function destroy($session_id) {
        return redis()->del($this->table . $session_id);
    }

    public function gc($sessMaxLifeTime) {
        return true;
    }
}