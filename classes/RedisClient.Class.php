<?php
visit_limit();

class RedisClient {

    private $redis;
    private $master = array();
    private $slaves = array();

    public function __construct($config = "joinclub") {
        $redis_config_file = CONFIG_DIR . DS . "cache" . DS . "RedisConfig.ini";
        if (!file_exists($redis_config_file))
            throw new LeapException("Redis config file not found...");
        $redis_config = parse_ini_file($redis_config_file, true);
        if (!isset($redis_config[$config]))
            throw new LeapException("Could not find the right redis configure {$config}...");
        $master = @explode(":", $redis_config[$config]['master']);
        $this->master['host'] = $master[0];
        $this->master['port'] = $master[1];
        $slaves = @explode(",", $redis_config[$config]['slaves']);
        if ($slaves) {
            foreach ($slaves as $slave) {
                $slave = @explode(":", $slave);
                $this->slaves[] = array(
                    'host' => $slave[0],
                    'port' => $slave[1]
                );
            }
        } else {
            $this->slaves[] = $this->master;
        }
        $this->redis = new Redis;
    }

    private function connect($readOnly = false) {
        if ($readOnly) {
            $rand = rand(0, count($this->slaves) - 1);
            $server = $this->slaves[$rand];
        } else {
            $server = $this->master;
        }
        $this->redis->pconnect($server['host'], $server['port']);
    }

    public function expire($key, $seconds) {
        $this->connect();
        $this->redis->expire($key, $seconds);
    }
    
    public function expireAt($key, $timestamp) {
        $this->connect();
        $this->redis->expireAt($key, $timestamp);
    }
    
    public function incr($key) {
        $this->connect();
        return $this->redis->incr($key);
    }

    public function decr($key) {
        $this->connect();
        return $this->redis->decr($key);
    }
    
    public function set($key, $value, $expire = null) {
        $this->connect();
        $this->redis->set($key, $value);
		if (is_numeric($expire)) {
			$this->redis->expire($key, $expire);
		}
    }
    
    public function get($key) {
        $this->connect(true);
        return $this->redis->get($key);
    }
	
	public function del($key) {
		$this->connect();
		$this->redis->del($key);	
	}

    public function sAdd($key, $member) {
        $this->connect();
        $this->redis->sAdd($key, $member);
    }
	
	public function sGet($key) {
		$this->connect(true);
		return $this->redis->sMembers($key);
	}
	
	public function sDel($key, $member) {
		$this->connect();
		$this->redis->sRem($key, $member);	
	}
    
    public function sIsMember($key, $member) {
        $this->connect(true);
        return $this->redis->sIsMember($key, $member);
    }
    
    public function zAdd($key, $score, $member) {
        $this->connect();
        $this->redis->zAdd($key, $score, $member);
    }
	
	public function zRem($key, $member) {
		$this->connect();
		return $this->redis->zRem($key, $member);
	}

    public function zCard($key) {
        $this->connect(true);
        return $this->redis->zCard($key);
    }
    
    public function zRemRangeByRank($key, $start, $stop) {
        $this->connect();
        $this->redis->zRemRangeByRank($key, $start, $stop);
    }
    
    public function zRemRangeByScore($key, $min, $max) {
        $this->connect();
        $this->redis->zRemRangeByScore($key, $min, $max);
    }
    
    public function zRangeByScore($key, $min, $max) {
        $this->connect(true);
        return $this->redis->zRangeByScore($key, $min, $max, array("withscores" => true));
    }
    
    public function zRevRange($key, $start, $stop) {
        $this->connect(true);
        return $this->redis->zRevRange($key, $start, $stop, true);
    }
    
    public function zRevRangeByScore($key, $max, $min) {
        $this->connect(true);
        return $this->redis->zRevRangeByScore($key, $max, $min, array("withscores" => true));
    }

    public function exists($key) {
        $this->connect(true);
        return $this->redis->exists($key);
    }

    public function hexists($key, $field) {
        $this->connect(true);
        return $this->redis->hexists($key, $field);
    }

    public function hset($key, $field, $value) {
        $this->connect();
        return $this->redis->hset($key, $field, $value);
    }

    public function hdel($key, $field) {
        $this->connect();
        return $this->redis->hdel($key, $field);
    }

    public function hget($key, $field) {
        $this->connect(true);
        return $this->redis->hget($key, $field);
    }

    public function hincrby($key, $field, $step = 1) {
        $this->connect();
        return $this->redis->hincrby($key, $field, $step);
    }

    public function hkeys($key) {
        $this->connect(true);
        return $this->redis->hkeys($key);
    }

    public function hlen($key) {
        $this->connect(true);
        return $this->redis->hlen($key);
    }

    public function hvals($key) {
        $this->connect(true);
        return $this->redis->hvals($key);
    }
}