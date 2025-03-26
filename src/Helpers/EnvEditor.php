<?php

namespace Devanox\Core\Helpers;

class EnvEditor
{
    public static function filePath()
    {
        return base_path('.env');
    }

    public static function data()
    {
        $envFile = self::filePath();
        if (!file_exists($envFile)) {
            return fluent([]);
        }

        $envData = file_get_contents($envFile);
        $lines = explode("\n", $envData);
        $data = [];

        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $data[trim($key)] = trim($value);
            }
        }

        return fluent($data);
    }

    public static function isKeyPresent($key): bool
    {
        $data = self::data();
        return $data->has($key);
    }

    public static function get($key, $default = null)
    {
        $data = self::data();
        if ($data->has($key)) {
            return $data->get($key, $default);
        }

        return $default;
    }

    public static function set($key, $value)
    {
        $data = self::data();
        $envFile = self::filePath();
        $data->set($key, $value);
        $lines = [];
        foreach ($data->all() as $k => $v) {
            $lines[] = "$k=$v";
        }

        file_put_contents($envFile, implode("\n", $lines));

        return $data;
    }

    public static function remove($key)
    {
        $data = self::data();
        $envFile = self::filePath();
        if ($data->has($key)) {
            $data->forget($key);
            $lines = [];
            foreach ($data->all() as $k => $v) {
                $lines[] = "$k=$v";
            }

            file_put_contents($envFile, implode("\n", $lines));
        }

        return $data;
    }

    public static function setMultiple(array $newData)
    {
        $data = self::data();
        $envFile = self::filePath();

        foreach ($newData as $key => $value) {
            $data->set($key, $value);
        }

        $lines = [];
        foreach ($data->all() as $k => $v) {
            $lines[] = "$k=$v";
        }

        file_put_contents($envFile, implode("\n", $lines));
        return $data;
    }
}
