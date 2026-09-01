<?php

namespace Modules\Core\Http\Services\Utilities;

use App\Config\Cache\BuilderInfoCache;
use App\Config\Cache\CheckVersionUpdateCache;
use App\Config\ps_constant;
use App\Config\ps_url;
use App\Http\Services\PsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Core\Entities\Utilities\BuilderAppInfoCache;
use Modules\Core\Entities\Utilities\CheckVersionUpdate;
use Modules\Core\Http\Facades\PsCache;
use Throwable;

class PSXBuilderService extends PsService
{
    public function __construct() {}

    public function getCheckVersionUpdate($id = null)
    {
        $param = [$id];

        return PsCache::remember([CheckVersionUpdateCache::BASE], CheckVersionUpdateCache::GET_EXPIRY, $param,
            function () use ($id) {
                return CheckVersionUpdate::when($id, function ($query, $id) {
                    $query->where(CheckVersionUpdate::id, $id);
                })->first();
            });
    }

    public function getBuilderAppInfoCache($id = null)
    {
        $param = [$id];

        return PsCache::remember([BuilderInfoCache::GET_KEY], BuilderInfoCache::GET_EXPIRY, $param,
            function () use ($id) {
                return BuilderAppInfoCache::when($id, function ($query, $id) {
                    // $query->where(BuilderAppInfoCache::id, $id);
                })->first();
            });
    }

    // @todo : later we can move this function call to the PageLoadHandler
    public function syncBuilderInfo($project)
    {

        $builderAppInfoCache = Cache::remember(BuilderInfoCache::INFO_KEY, BuilderInfoCache::INFO_EXPIRY, function () use ($project) {

            try {
                $_info = $this->getBuilderInfoFromApi($project);
                Log::info('Loading Builder Info API...');

                $this->updateBuilderAppInfoCache($_info);

            } catch (\Throwable $e) {
            }

            return $this->getBuilderAppInfoCache();
        });

        $builderAppInfo = $this->convertBuilderInfoCacheToObject($builderAppInfoCache);

        return $builderAppInfo;
    }

    /**
     * @deprecated Temporary fix for previous version update
     */
    public function updateBuilderAppInfoCacheWrapper($builderAppInfoCacheData)
    {
        try {
            $this->update($builderAppInfoCacheData);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * @deprecated Temporary fix for previous version update
     */
    public function saveBuilderAppInfoCacheWrapper($builderAppInfoCacheData)
    {
        try {
            $this->store($builderAppInfoCacheData);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////

    private function updateBuilderAppInfoCache($request)
    {
        $builderAppInfoCache = $this->getBuilderAppInfoCache();

        $properties = [
            'isConnected',
            'isProjectChanged',
            'isValid',
            'syncAble',
            'versionCode',
        ];

        $cachedData = $builderAppInfoCache ? json_decode($builderAppInfoCache->cached_data) : (object) [];

        foreach ($properties as $property) {
            if (isset($request->$property)) {
                $cachedData->$property = $request->$property;
            }
        }

        if (isset($request->latestVersion)) {
            $cachedData->versionCode = $request->latestVersion->version_code ?? $cachedData->versionCode;
            $cachedData->versionNumber = $request->latestVersion->version_number ?? $cachedData->versionNumber;
            $cachedData->description = $request->latestVersion->description ?? $cachedData->description;
        }

        $cache = [
            'cached_data' => json_encode($cachedData),
        ];

        if ($builderAppInfoCache) {
            $this->update($cache);
        } else {
            $this->store($cache);
        }

        PsCache::clear(BuilderInfoCache::GET_KEY);
    }

    private function convertBuilderInfoCacheToObject($builderAppInfoCache)
    {
        $cache = json_decode($builderAppInfoCache->cached_data);

        $builderAppInfo = (object) [
            'isConnected' => $cache->isConnected,
            'isProjectChanged' => $cache->isProjectChanged,
            'latestVersion' => (object) [
                'version_code' => $cache->versionCode,
                'version_number' => $cache->versionNumber ?? '',
                'description' => $cache->description ?? '',
            ],
            'isValid' => $cache->isValid,
            'syncAble' => $cache->syncAble,
        ];

        return $builderAppInfo;
    }

    private function store($builderAppInfoCacheData)
    {

        try {
            $builderAppInfoCache = new BuilderAppInfoCache;
            $builderAppInfoCache->fill($builderAppInfoCacheData);
            $builderAppInfoCache->added_user_id = Auth::id();
            $builderAppInfoCache->save();
        } catch (Throwable $e) {
            throw $e;
        }

    }

    private function update($builderAppInfoCacheData)
    {
        try {
            $builderAppInfoCache = $this->getBuilderAppInfoCache();
            $builderAppInfoCache->fill($builderAppInfoCacheData);
            $builderAppInfoCache->updated_user_id = Auth::id();
            $builderAppInfoCache->update();
        } catch (Throwable $e) {
            throw $e;
        }

    }

    private function getBuilderInfoFromApi($project)
    {
        $domain = $_SERVER['HTTP_HOST'] ?? '-';
        // '&domain='.$domain
        $builderAppInfo = getHttpWithApiKey(ps_constant::base_url, getApiKey(), ps_url::builderAppInfo,
            'project_id='.$project->id.
            '&project_url='.$project->project_url.
            '&project_code='.$project->project_code.
            '&is_publish='.ps_constant::isPublish.
            '&log_code='.getLogCode().
            '&domain='.$domain
        );

        return $builderAppInfo;
    }
}
