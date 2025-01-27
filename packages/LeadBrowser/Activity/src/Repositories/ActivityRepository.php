<?php

namespace LeadBrowser\Activity\Repositories;

use LeadBrowser\Core\Eloquent\Repository;
use LeadBrowser\User\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

class ActivityRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'LeadBrowser\Activity\Contracts\Activity';
    }

    /**
     * @param  string  $dateRange
     * @return mixed
     */
    public function getActivities($dateRange)
    {
        return $this->select(
                'activities.id',
                'activities.created_at',
                'activities.title',
                'activities.schedule_from as start',
                'activities.schedule_to as end',
                'users.name as user_name',
            )
            ->addSelect(DB::raw('IF(activities.is_done, "done", "") as class'))
            ->leftJoin('activity_participants', 'activities.id', '=', 'activity_participants.activity_id')
            ->leftJoin('users', 'activities.user_id', '=', 'users.id')
            ->whereIn('type', ['call', 'meeting', 'lunch'])
            ->whereBetween('activities.schedule_from', $dateRange)
            ->where(function ($query) {
                $currentUser = auth()->guard('user')->user();

                if ($currentUser->view_permission != 'global') {
                    if ($currentUser->view_permission == 'group') {
                        $userIds = app(UserRepository::class)->getCurrentUserGroupsUserIds();

                        $query->whereIn('activities.user_id', $userIds)
                            ->orWhereIn('activity_participants.user_id', $userIds);
                    } else {
                        $query->where('activities.user_id', $currentUser->id)
                            ->orWhere('activity_participants.user_id', $currentUser->id);
                    }
                }
            })
            ->distinct()
            ->get();
    }

    /**
     * @param  string  $startFrom
     * @param  string  $endFrom
     * @param  array  $participants
     * @param  integer  $id
     * @return boolean
     */
    public function isDurationOverlapping($startFrom, $endFrom, $participants = [], $id)
    {
        $queryBuilder = $this->model
            ->leftJoin('activity_participants', 'activities.id', '=', 'activity_participants.activity_id')
            ->where(function ($query) use ($startFrom, $endFrom) {
                $query->where([
                    ['activities.schedule_from', '<=', $startFrom],
                    ['activities.schedule_to', '>=', $startFrom],
                ])->orWhere([
                    ['activities.schedule_from', '>=', $startFrom],
                    ['activities.schedule_from', '<=', $endFrom],
                ]);
            })
            ->where(function ($query) use ($participants) {
                if (is_null($participants)) {
                    return;
                }

                if (isset($participants['users'])) {
                    $query->orWhereIn('activity_participants.user_id', $participants['users']);
                }

                if (isset($participants['employees'])) {
                    $query->orWhereIn('activity_participants.employee_id', $participants['employees']);
                }
            })
            ->groupBy('activities.id');

        if (! is_null($id)) {
            $queryBuilder->where('activities.id', '!=', $id);
        }

        return $queryBuilder ? true : false;
    }
}