<?php

namespace Devanox\Core\Helpers;

class InstallerInfo
{
    public static function filePath()
    {
        return storage_path('install.json');
    }

    public static function create(array $data = [])
    {
        $installFile = self::filePath();

        if (!file_exists($installFile)) {
            touch($installFile);
        }

        file_put_contents($installFile, json_encode($data));

        return fluent($data);
    }

    public static function data()
    {
        $installFile = self::filePath();

        if (!file_exists($installFile)) {
            return fluent([]);
        }

        $installData = json_decode(file_get_contents($installFile), true);

        return fluent($installData);
    }

    public static function getData($key, $default = null)
    {
        $data = self::data();

        if ($data->has($key)) {
            return $data->get($key, $default);
        }

        return $default;
    }

    public static function setData($key, $value)
    {
        $data = self::data();
        $installFile = self::filePath();
        $data->set($key, $value);
        file_put_contents($installFile, json_encode($data->all()));

        return $data;
    }

    public static function getStatus()
    {
        return self::getData('status', 'not_started');
    }

    public static function setStatus($status)
    {
        return self::setData('status', $status);
    }

    public static function remove()
    {
        $installFile = self::filePath();

        if (file_exists($installFile)) {
            unlink($installFile);
        }
    }
}
