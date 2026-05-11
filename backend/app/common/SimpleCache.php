<?php
namespace app\common;

class SimpleCache
{
    private static string $cacheDir = '';
    private static array $lockHandles = [];

    private static function getCacheDir(): string
    {
        if (empty(self::$cacheDir)) {
            self::$cacheDir = runtime_path() . 'cache' . DIRECTORY_SEPARATOR;
            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0755, true);
            }
        }
        return self::$cacheDir;
    }

    public static function get(string $key, $default = null)
    {
        $file = self::getCacheDir() . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $content = file_get_contents($file);
        $data = unserialize($content);
        
        if (!is_array($data) || !isset($data['expire']) || !isset($data['value'])) {
            return $default;
        }
        
        if ($data['expire'] > 0 && $data['expire'] < time()) {
            @unlink($file);
            return $default;
        }
        
        return $data['value'];
    }

    public static function set(string $key, $value, int $ttl = 0): bool
    {
        $file = self::getCacheDir() . md5($key) . '.cache';
        
        $data = [
            'value' => $value,
            'expire' => $ttl > 0 ? time() + $ttl : 0,
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }

    public static function delete(string $key): bool
    {
        $file = self::getCacheDir() . md5($key) . '.cache';
        
        if (file_exists($file)) {
            return @unlink($file);
        }
        
        return true;
    }

    public static function increment(string $key, int $step = 1, int $ttl = 0): int
    {
        $file = self::getCacheDir() . md5($key) . '.cache';
        $lockFile = $file . '.lock';
        
        $lockHandle = fopen($lockFile, 'w');
        if (!$lockHandle) {
            return self::incrementWithoutLock($file, $step, $ttl);
        }
        
        if (!flock($lockHandle, LOCK_EX)) {
            fclose($lockHandle);
            return self::incrementWithoutLock($file, $step, $ttl);
        }
        
        try {
            $currentValue = 0;
            $existingTtl = $ttl;
            
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $data = unserialize($content);
                
                if (is_array($data) && isset($data['value'])) {
                    if (isset($data['expire']) && $data['expire'] > 0 && $data['expire'] < time()) {
                        $currentValue = 0;
                    } else {
                        $currentValue = (int) $data['value'];
                        if (isset($data['expire']) && $data['expire'] > 0) {
                            $existingTtl = $data['expire'] - time();
                            if ($existingTtl < 0) $existingTtl = $ttl;
                        }
                    }
                }
            }
            
            $newValue = $currentValue + $step;
            
            $data = [
                'value' => $newValue,
                'expire' => $existingTtl > 0 ? time() + $existingTtl : 0,
            ];
            
            file_put_contents($file, serialize($data));
            
            return $newValue;
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    private static function incrementWithoutLock(string $file, int $step, int $ttl): int
    {
        $currentValue = 0;
        
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = unserialize($content);
            
            if (is_array($data) && isset($data['value'])) {
                if (!isset($data['expire']) || $data['expire'] == 0 || $data['expire'] >= time()) {
                    $currentValue = (int) $data['value'];
                }
            }
        }
        
        $newValue = $currentValue + $step;
        
        $data = [
            'value' => $newValue,
            'expire' => $ttl > 0 ? time() + $ttl : 0,
        ];
        
        file_put_contents($file, serialize($data));
        
        return $newValue;
    }

    public static function setIfNotExists(string $key, $value, int $ttl = 0): bool
    {
        $file = self::getCacheDir() . md5($key) . '.cache';
        $lockFile = $file . '.lock';
        
        $lockHandle = fopen($lockFile, 'w');
        if (!$lockHandle) {
            return self::setIfNotExistsWithoutLock($file, $value, $ttl);
        }
        
        if (!flock($lockHandle, LOCK_EX)) {
            fclose($lockHandle);
            return self::setIfNotExistsWithoutLock($file, $value, $ttl);
        }
        
        try {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $data = unserialize($content);
                
                if (is_array($data) && isset($data['expire'])) {
                    if ($data['expire'] == 0 || $data['expire'] >= time()) {
                        return false;
                    }
                }
            }
            
            $data = [
                'value' => $value,
                'expire' => $ttl > 0 ? time() + $ttl : 0,
            ];
            
            return file_put_contents($file, serialize($data)) !== false;
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    private static function setIfNotExistsWithoutLock(string $file, $value, int $ttl): bool
    {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = unserialize($content);
            
            if (is_array($data) && isset($data['expire'])) {
                if ($data['expire'] == 0 || $data['expire'] >= time()) {
                    return false;
                }
            }
        }
        
        $data = [
            'value' => $value,
            'expire' => $ttl > 0 ? time() + $ttl : 0,
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
}
