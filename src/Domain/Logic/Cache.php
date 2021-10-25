<?php
declare(strict_types=1);

namespace Fastknife\Domain\Logic;

use Fastknife\Exception\CacheException;

class Cache
{
    protected $config;

    protected $driver;

    protected $methodMap = [
        'get' => 'get',
        'set' => 'set',
        'delete' => 'delete',
        'has' => 'has'
    ];

    public function __construct($config)
    {
        if (isset($config['methods'])) {
            $this->methodMap = array_merge($this->methodMap, $config['methods']);
        }
        $this->driver = $this->getDriver($config['constructor']);
    }

    public function getDriver($callback)
    {
        if ($callback instanceof \Closure) {
            $result = $callback();
        } else if (is_object($callback)) {
            $result = $callback;
        } else if (is_array($callback)) {
            $result = call_user_func($callback);
        } else if ($this->isSerialized($callback)) {
            $result = unserialize($callback);
        } else if (is_string($callback) && class_exists($callback)) {
            $result = new $callback;
        } else {
            throw new CacheException('缓存构造配置项错误：constructor');
        }
        return $result;
    }

    /**
     * 是否可以被反序列化
     * @param $data
     * @return bool
     */
    public function isSerialized($data): bool
    {
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }
        if (!preg_match('/^([adObis]):/', $data, $badions)) {
            return false;
        }
        switch ($badions[1]) {
            case 'a' :
            case 'O' :
            case 's' :
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }

    public function getDriverMethod($name)
    {
        return $this->methodMap[$name];
    }

    public function get($key, $default = null)
    {
        $method = $this->getDriverMethod('get');
        return $this->driver->$method($key, $default);
    }

    public function set($key, $value, $ttl = null)
    {
        $method = $this->getDriverMethod('set');
        return $this->driver->$method($key, $value, $ttl);
    }

    public function delete($key)
    {
        $method = $this->getDriverMethod('delete');
        return $this->driver->$method($key);
    }

    public function has($key)
    {
        $method = $this->getDriverMethod('has');
        return $this->driver->$method($key);
    }

}
