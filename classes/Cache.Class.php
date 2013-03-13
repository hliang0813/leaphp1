<?php
if (!defined('LEAP_START')) {
    trigger_error('Access denied !', E_USER_ERROR);
}

/**
 * 类名：Cache
 * 描述：封闭的缓存操作类
 * @author hliang
 * @copyright Copyright (c) 2011- neusoft
 * @version 0.1
 */
class Cache extends Memcached {

    private $config;
    private $normal;
    private $servers = array();

    /**
     * 函数名：__construct
     * 描述：构造函数
     * @param string $config
     */
    public function __construct($config = null) {
        if (!$config) {
            // 如果没有指定KeyValue服务器，则默认使用Memcached
            $config = 'Memcached';
        }
        $this->config = $this->readCacheConfig($config);
        if (isset($this->config['hamode']) && $this->config['hamode'] == true) {
            $this->normal = false;
            foreach($this->config['servers'] as $server) {
                $config = $this->config;
                $sinfo = explode("$", $server);
                $config['servers'] = array($sinfo[0]);
                $this->servers[] = array(
                    "cache" => new Memcached($config),
                    "min" => $sinfo[1],
                    "max" => $sinfo[2]
                );
            }
        } else {
            $this->normal = true;
            parent::__construct($this->config);
        }
        // 处理默认过期时间
        if (isset($this->config['expire'])) {
            $expires = @explode(",", str_replace(" ", "", $this->config['expire']));
            foreach ($expires as $expire) {
                $exp = @explode("$", $expire);
                $this->expire[$exp[0]] = $exp[1];
            }
        }
        if (!isset($this->expire['DEFAULT'])) {
            $this->expire["DEFAULT"] = 10 * 60;
        }
    }

    /**
     * 函数名：readCacheConfig
     * 描述：读取缓存配置文件
     * @param string $config
     */
    private function readCacheConfig($config = null) {
        $cache_config_file =  CONFIG_DIR . DS . "cache" . DS . "CacheConfig.ini";
        if (file_exists($cache_config_file)) {
            $cache_config = parse_ini_file($cache_config_file, true);
            if (!array_key_exists($config, $cache_config)) {
                throw new LeapException('Could not find the right statuement in Cache configure file.', 824209020);
            }
            $host_ary = @explode(",", str_replace(" ", "", $cache_config[$config]['servers']));
            $cache_config[$config]['servers'] = $host_ary;
            return $cache_config[$config];
        } else {
            throw new LeapException('Could not find Cache configure file.', 824209019);
        }
    }

    /**
     * 根据$key来选择Cache节点
     * @param <type> $key
     * @param <type> $sort 是否平衡负载
     * @return <type> 节点数组，顺序遵照平衡负载选项
     */
    private function getServersByKey($key, $sort = true) {
        $result = array();
        $uid = substr($key, 0, strpos($key, "/"));
        foreach($this->servers as $server) {
            if($uid >= $server['min'] && $uid <= $server['max']) {
                $result[] = $server['cache'];
            }
        }
        if($sort) {
            // 暂时没有考虑权重，随机分配
            shuffle($result);
        }
        return $result;
    }
    
    private function decideExpireTimeout($key, &$expire) {
        if (!isset($expire)) {
            foreach ($this->expire as $cKey => $cExp) {
                if (substr($key, strrpos($key, "/") + 1) == $cKey) {
                    $expire = $cExp;
                    return;
                }
            }
            $expire = $this->expire['DEFAULT'];
        }
    }

    /**
     * GET操作，负载平衡
     * @param <type> $key
     * @return <type> 所有主备均失败返回false
     */
    public function get($key) {
        if ($this->normal) {
            return parent::get($key);
        } else {
            foreach($this->getServersByKey($key) as $server) {
//                var_dump($server->_servers);
                $value = $server->get($key);
                if($value !== false)
                    return $value;
            }
            return false;
        }
    }

    /**
     * 批量GET，负载均衡
     * @param <type> $keys
     * @return <type> 关联数组$key=>$value
     */
    public function get_multi($keys) {
        if($this->normal) {
            return parent::get_multi($keys);
        } else {
            $multi_servers = array();
            foreach($keys as $key) {
                $servers = $this->getServersByKey($key);
                if(count($servers) == 0) {
                    return false;
                }
                $server = $servers[0];
                $found = false;
                for($i = 0; $i < count($multi_servers); $i++) {
                    if($multi_servers[$i]['server'] == $server) {
                        $multi_servers[$i]['keys'][] = $key;
                        $found = true;
                        break;
                    }
                }
                if(!$found) {
                    $multi_servers[] = array(
                        "server" => $server,
                        "keys" => array($key)
                    );
                }
            }
            $result = array();
            foreach($multi_servers as $server) {
//                var_dump($server['server']->_servers);
//                var_dump($server['keys']);
//                echo "<hr/>";
                $result = array_merge($result, $server['server']->get_multi($server['keys']));
            }
            return $result;
        }
    }

    /**
     * SET操作
     * @param <type> $key
     * @param <type> $value
     * @param <type> $exp
     * @return boolean 主备只有全部成功，才视为成功，有任意一个失败，即返回false
     */
    public function set($key, $value, $exp = 0) {
        $this->decideExpireTimeout($key, $exp);
        if($this->normal) {
            return parent::set($key, $value, $exp);
        } else {
            foreach($this->getServersByKey($key) as $server) {
                if(!$server->set($key, $value, $exp))
                    return false;
            }
            return true;
        }
    }

    /**
     * REPLACE操作
     * @param <type> $key
     * @param <type> $value
     * @param <type> $exp
     * @return boolean 主备只有全部成功，才视为成功，有任意一个失败，即返回false
     */
    public function replace($key, $value, $exp = 0) {
        $this->decideExpireTimeout($key, $exp);
        if($this->normal) {
            return parent::replace($key, $value, $exp);
        } else {
            foreach($this->getServersByKey($key) as $server) {
                if(!$server->replace($key, $value, $exp))
                    return false;
            }
            return true;
        }
    }

    /**
     * ADD操作
     * @param <type> $key
     * @param <type> $value
     * @param <type> $exp
     * @return boolean 主备只有全部成功，才视为成功，有任意一个失败，即返回false
     */
    public function add($key, $value, $exp = 0) {
        $this->decideExpireTimeout($key, $exp);
        if($this->normal) {
            return parent::add($key, $value, $exp);
        } else {
            foreach($this->getServersByKey($key) as $server) {
                if(!$server->add($key, $value, $exp))
                    return false;
            }
            return true;
        }
    }

    /**
     * INCR操作
     * @param <type> $key
     * @param <type> $amt
     * @return boolean 主备只有全部成功，才视为成功，有任意一个失败，即返回false
     */
    public function incr($key, $amt = 1) {
        if($this->normal) {
            return parent::incr($key, $amt);
        } else {
            foreach($this->getServersByKey($key) as $server) {
                if(!$server->incr($key, $value, $exp))
                    return false;
            }
            return true;
        }
    }

    /**
     * DECR操作
     * @param <type> $key
     * @param <type> $amt
     * @return boolean 主备只有全部成功，才视为成功，有任意一个失败，即返回false
     */
    public function decr($key, $amt = 1) {
        if($this->normal) {
            return parent::decr($key, $amt);
        } else {
            foreach($this->getServersByKey($key) as $server) {
                if(!$server->decr($key, $value, $exp))
                    return false;
            }
            return true;
        }
    }

    /**
     * DELETE操作
     * @param <type> $key
     * @param <type> $time
     * @return <type> 主备只有全部成功，才视为成功，有任意一个失败，即返回false
     */
    public function delete($key, $time = 0) {
        if($this->normal) {
            return parent::delete($key, $amt);
        } else {
            foreach($this->getServersByKey($key) as $server) {
                if(!$server->delete($key, $value, $exp))
                    return false;
            }
            return true;
        }
    }

}