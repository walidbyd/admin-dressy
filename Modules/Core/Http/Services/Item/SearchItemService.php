<?php

namespace Modules\Core\Http\Services\Item;

use App\Http\Contracts\Item\PaidItemHistoryServiceInterface;
use App\Http\Contracts\Item\SearchItemServiceInterface;
use App\Http\Contracts\User\BlockUserServiceInterface;
use App\Http\Services\PsService;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Item\PaidItemHistory;

class SearchItemService extends PsService implements SearchItemServiceInterface
{
    public function __construct(
        protected ComplaintItemService $complaintItemService,
        protected BlockUserServiceInterface $blockUserService,
        protected PaidItemHistoryServiceInterface $paidItemHistoryService,
    ) {}

    /**
     * @coveredBy testPrepareFiltersNotInData*
     */
    public function prepareFiltersNotInData($isBlockUser, $loginUserId, $exculdeIds = [])
    {
        $block_ids = [];
        if ($isBlockUser == 1) {
            $blockUserConds['to_block_user_id'] = $loginUserId;
            $block_ids = $this->blockUserService->getAll(relation: null, conds: $blockUserConds)->pluck('from_block_user_id')->toArray();
        }

        $complaintItems = $this->complaintItemService->getComplaintItems(reportedUserId: $loginUserId)->pluck('item_id')->toArray();

        return [
            'blockUserIds_not_in' => $block_ids,
            'complaintItemIds_not_in' => $complaintItems,
            'id' => $exculdeIds,
        ];
    }

    /**
     * @coveredBy testPreparePaidItemFiltersData*
     */
    public function preparePaidItemFiltersData($filters)
    {
        $filters['is_paid'] = Constants::yes;
        $filters['paid_item_histories_timestamp'] = getRoundedFiveMinuteTimestamp();
        $filters['paid_item_histories_deleted_at'] = null;

        return $filters;
    }

    /**
     * @coveredBy testPrepareFiltersNotInData*
     */
    public function prepareNormalItemFiltersNotInData($filtersNotIn)
    {
        $paidItemIds = $this->paidItemHistoryService->getAll(
            status: Constants::publish,
            startTimeStamp: getRoundedFiveMinuteTimestamp(),
            endTimestamp: getRoundedFiveMinuteTimestamp()
        )->pluck(PaidItemHistory::itemId)->toArray();

        $filtersNotIn['id'] = array_merge($filtersNotIn['id'], $paidItemIds);

        return $filtersNotIn;
    }
}
