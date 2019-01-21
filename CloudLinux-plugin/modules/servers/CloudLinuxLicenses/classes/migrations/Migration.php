<?php

namespace CloudLinuxLicenses\classes\migrations;


use CloudLinuxLicenses\classes\models\Migration as MigrationModel;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

class Migration
{
    public static function check()
    {
        $last = self::last();
        $available = self::getAvailable($last);

        return (bool) $available;
    }

    public static function up()
    {
        if (!Capsule::schema()->hasTable('CloudLinux_migrations')) {
            $model = new MigrationModel();
            Capsule::schema()->create('CloudLinux_migrations', $model->getSchema());
        }

        $last = self::last();

        $available = self::getAvailable($last);

        foreach ($available as $versionNumber) {
            self::run($versionNumber, 'up');

            MigrationModel::firstOrCreate([
                'version' => $versionNumber,
            ]);
        }
    }

    public static function down($target = null)
    {
        $existing = MigrationModel::where('version', '>', $target)->orderBy('version', 'desc')->first();

        foreach ($existing as $row) {
            self::run($row->version, 'down');

            MigrationModel::where('version', $row->version)->delete();
        }
    }

    private static function run($versionNumber, $direction)
    {
        $className = 'CloudLinuxLicenses\classes\migrations\versions\Version' . $versionNumber;

        /** @var VersionInterface $version */
        $version = new $className;

        if (!is_array($version->$direction())) {
            return;
        }

        $db = Model::getConnectionResolver();

        foreach ($version->$direction() as $sql) {
            $db->statement($sql);
        };
    }

    public static function last()
    {
        $last = MigrationModel::orderBy('version', 'desc')->first();

        if ($last) {
            return (int) $last->version;
        }

        return -1;
    }

    public static function getAvailable($min = 0)
    {
        $versions_path = __DIR__ . '/versions';

        $files = glob($versions_path . '/*.php');

        # get versions of available migrations classes
        $path = preg_replace('#(\.|\/)#i', '\\\\${1}', $versions_path);
        $files = preg_replace("#(" . $path . "\/Version)|(\.php)#i", '', $files);

        $files = array_filter($files, function($item) use ($min) {
            return $item > $min;
        });

        sort($files);

        return $files;
    }

    public static function fillByActivation()
    {
        $migrations = self::getAvailable();
        foreach ($migrations as $version) {
            MigrationModel::create(['version' => $version]);
        }
    }
}