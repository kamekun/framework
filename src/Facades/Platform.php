<?php

namespace Sokeio\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 
 * @method static void enableCachePage()
 * @method static void disableCachePage()
 * @method static bool checkCachePage()
 * @method static mixed FileVersion()
 * @method static bool download($remote_file_url, $local_file, $throw = false)
 * @method static mixed findFile(string $name)
 * @method static mixed downloadFile(string $name)
 * @method static mixed install(string $name)
 * @method static mixed installLocal(string $file)
 * @method static mixed Load(string $path)
 * @method static mixed registerComposer($path, $resgister = false)
 * @method static void addLink(string $source,string $target, bool $relative = false)
 * @method static mix getLinks()
 * @method static void makeLink()
 * @method static mix listExtend()
 * @method static void checkFolderPlatform()
 * @method static mix getDataInfo($path,$register)
 * @method static array getModels()
 * @method static void NotificationAdd($title,$description,$meta_data,$to_role,$to_user)
 * @method static void BootGate()
 * @method static void Ready($callback = null)
 * @method static void DoReady()
 * @method static bool CheckGate();
 * @method static bool CheckConnectDB();
 * @method static void setEnv($arrs)
 * 
 * 
 * @see \Sokeio\Facades\Platform
 */
class Platform extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Sokeio\PlatformManager::class;
    }
}
