<?php

namespace Modules\Core\Http\Services\Configuration;

use App\Http\Contracts\Configuration\AdPostTypeServiceInterface;
use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use App\Http\Services\PsService;
use Exception;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Configuration\AdPostType;
use Modules\Core\Entities\Configuration\SystemConfig;

class AdPostTypeService extends PsService implements AdPostTypeServiceInterface
{
    public function __construct(protected SystemConfigServiceInterface $systemConfigService) {}

    public function get($id = null, $conds = null)
    {
        return AdPostType::when($id, function ($query, $id) {
            $query->where(AdPostType::id, $id);
        })
            ->when($conds, function ($query, $conds) {
                $query->where($conds);
            })
            ->first();
    }

    public function getAll($id = null, $conds = null)
    {
        return AdPostType::when($id, function ($query, $id) {
            $query->where(AdPostType::id, $id);
        })
            ->when($conds, function ($query, $conds) {
                $query->where($conds);
            })
            ->get();
    }

    /**
     * @coveredBy testGetAdPostType*
     */
    public function getAdPostType(?string $adPostType = null)
    {
        try {
            $adPostTypes = $this->getAll()->pluck(AdPostType::key)->toArray();
            $systemConfig = $this->systemConfigService->get();
            $defaultAdPostType = $this->get($systemConfig->{SystemConfig::adType})->{AdPostType::key};

            if (empty($adPostType) || is_null($adPostType)) {
                return $defaultAdPostType;
            }
            if (in_array($adPostType, $adPostTypes)) {
                return $adPostType;
            }
            // @todo : why we still need to check this condition ?
            // above condition should be enough.
            if ($adPostType == Constants::onlyPaidItemAdType || $adPostType == Constants::paidItemFirstWithGoogleAdType) {
                return $adPostType;
            } else {
                return $defaultAdPostType;
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    // ///////////////////////////////////////////////////
    // // Private Functions
    // ///////////////////////////////////////////////////

    // -------------------------------------------------------------------
    // Data Preparations
    // -------------------------------------------------------------------

    // -------------------------------------------------------------------
    // Database
    // -------------------------------------------------------------------

    // ------------------------------------------------------------------
    // Others
    // ------------------------------------------------------------------
}
