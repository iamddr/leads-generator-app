<?php

namespace LeadBrowser\Admin\Http\Controllers\Activity;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use LeadBrowser\Activity\Repositories\ActivityRepository;
use LeadBrowser\Activity\Repositories\FileRepository;
use LeadBrowser\Admin\Http\Controllers\Controller;
use LeadBrowser\Organization\Repositories\EmployeeRepository;
use LeadBrowser\Lead\Repositories\LeadRepository;
use LeadBrowser\User\Repositories\UserRepository;

class ActivityController extends Controller
{
    /**
     * FileRepository object
     *
     * @var \LeadBrowser\Activity\Repositories\FileRepository
     */
    protected $fileRepository;

    /**
     * ActivityRepository object
     *
     * @var \LeadBrowser\Activity\Repositories\ActivityRepository
     */
    protected $activityRepository;

    /**
     * LeadRepository object
     *
     * @var \LeadBrowser\Lead\Repositories\LeadRepository
     */
    protected $leadRepository;

    /**
     * UserRepository object
     *
     * @var \LeadBrowser\User\Repositories\UserRepository
     */
    protected $userRepository;

    /**
     * EmployeeRepository object
     *
     * @var \LeadBrowser\Organization\Repositories\EmployeeRepository
     */
    protected $employeeRepository;

    /**
     * Create a new controller instance.
     *
     * @param \LeadBrowser\Activity\Repositories\ActivityRepository  $activityRepository
     * @param \LeadBrowser\Activity\Repositories\FileRepository  $fileRepository
     * @param \LeadBrowser\Activity\Repositories\LeadRepository  $leadRepository
     * @param \LeadBrowser\User\Repositories\UserRepository  $userRepository
     * @param \LeadBrowser\Organization\Repositories\EmployeeRepository  $employeeRepository
     *
     * @return void
     */
    public function __construct(
        ActivityRepository $activityRepository,
        FileRepository $fileRepository,
        LeadRepository $leadRepository,
        UserRepository $userRepository,
        EmployeeRepository $employeeRepository
    ) {
        $this->activityRepository = $activityRepository;

        $this->fileRepository = $fileRepository;

        $this->leadRepository = $leadRepository;

        $this->userRepository = $userRepository;

        $this->employeeRepository = $employeeRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin::activities.index');
    }

    /**
     * Returns a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get()
    {
        if (request('view_type')) {
            $startDate = request()->get('startDate')
                        ? Carbon::createFromTimeString(request()->get('startDate') . " 00:00:01")
                        : Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');

            $endDate = request()->get('endDate')
                    ? Carbon::createFromTimeString(request()->get('endDate') . " 23:59:59")
                    : Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');

            $activities = $this->activityRepository->getActivities([$startDate, $endDate])->toArray();

            return response()->json([
                'activities' => $activities,
            ]);
        } else {
            return app(\LeadBrowser\Admin\DataGrids\Activity\ActivityDataGrid::class)->toJson();
        }
    }

    /**
     * Check if activity duration is overlapping with another activity duration.
     *
     * @return \Illuminate\Http\Response
     */
    public function checkIfOverlapping()
    {
        $isOverlapping = $this->activityRepository->isDurationOverlapping(
            request('schedule_from'),
            request('schedule_to'),
            request('participants'),
            request('id')
        );

        return response()->json([
            'overlapping' => $isOverlapping,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), [
            'type'          => 'required',
            'comment'       => 'required_if:type,note',
            'schedule_from' => 'required_unless:type,note',
            'schedule_to'   => 'required_unless:type,note',
        ]);

        Event::dispatch('activity.create.before');

        $activity = $this->activityRepository->create(array_merge(request()->all(), [
            'is_done' => request('type') == 'note' ? 1 : 0,
            'user_id' => auth()->guard('user')->user()->id,
        ]));

        if (request('participants')) {
            if (is_array(request('participants.users'))) {
                foreach (request('participants.users') as $userId) {
                    $activity->participants()->create([
                        'user_id' => $userId
                    ]);
                }
            }

            if (is_array(request('participants.employees'))) {
                foreach (request('participants.employees') as $employeeId) {
                    $activity->participants()->create([
                        'employee_id' => $employeeId,
                    ]);
                }
            }
        }

        if (request('lead_id')) {
            $lead = $this->leadRepository->find(request('lead_id'));

            $lead->activities()->attach($activity->id);
        }

        Event::dispatch('activity.create.after', $activity);

        session()->flash('success', trans('admin::app.activities.create-success', ['type' => trans('admin::app.activities.' . $activity->type)]));

        return redirect()->back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $activity = $this->activityRepository->findOrFail($id);

        return view('admin::activities.edit', compact('activity'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        Event::dispatch('activity.update.before', $id);

        $activity = $this->activityRepository->update(request()->all(), $id);

        if (request('participants')) {
            $activity->participants()->delete();

            if (is_array(request('participants.users'))) {
                foreach (request('participants.users') as $userId) {
                    $activity->participants()->create([
                        'user_id' => $userId
                    ]);
                }
            }

            if (is_array(request('participants.employees'))) {
                foreach (request('participants.employees') as $employeeId) {
                    $activity->participants()->create([
                        'employee_id' => $employeeId,
                    ]);
                }
            }
        }

        if (request('lead_id')) {
            $lead = $this->leadRepository->find(request('lead_id'));

            if (! $lead->activities->contains($id)) {
                $lead->activities()->attach($id);
            }
        }

        Event::dispatch('activity.update.after', $activity);

        if (request()->ajax()) {
            return response()->json([
                'message' => trans('admin::app.activities.update-success', ['type' => trans('admin::app.activities.' . $activity->type)]),
            ]);
        } else {
            session()->flash('success', trans('admin::app.activities.update-success', ['type' => trans('admin::app.activities.' . $activity->type)]));

            return redirect()->route('activities.index');
        }
    }

    /**
     * Mass Update the specified resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function massUpdate()
    {
        $count = 0;

        $data = request()->all();

        foreach (request('rows') as $activityId) {
            Event::dispatch('activity.update.before', $activityId);

            $activity = $this->activityRepository->update([
                'is_done' => request('value'),
            ], $activityId);

            Event::dispatch('activity.update.after', $activity);

            $count++;
        }

        if (! $count) {
            return response()->json([
                'message' => trans('admin::app.activities.mass-update-failed'),
            ], 400);
        }

        return response()->json([
            'message' => trans('admin::app.activities.mass-update-success'),
        ]);
    }

    /**
     * Search participants results
     *
     * @return \Illuminate\Http\Response
     */
    public function searchParticipants()
    {
        $users = $this->userRepository->findWhere([
            ['name', 'like', '%' . urldecode(request()->input('query')) . '%']
        ]);

        $employees = $this->employeeRepository->findWhere([
            ['name', 'like', '%' . urldecode(request()->input('query')) . '%']
        ]);

        return response()->json([
            'users'   => $users,
            'employees' => $employees,
        ]);
    }

    /**
     * Upload files to storage
     *
     * @return \Illuminate\View\View
     */
    public function upload()
    {
        $this->validate(request(), [
            'file' => 'required',
        ]);

        Event::dispatch('activities.file.create.before');

        $file = $this->fileRepository->upload(request()->all());

        if ($file) {
            if ($leadId = request('lead_id')) {
                $lead = $this->leadRepository->find($leadId);

                $lead->activities()->attach($file->activity->id);
            }

            Event::dispatch('activities.file.create.after', $file);

            session()->flash('success', trans('admin::app.activities.file-upload-success'));
        } else {
            session()->flash('warning', trans('admin::app.activities.file-upload-error'));
        }

        return redirect()->back();
    }

    /**
     * Download file from storage
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function download($id)
    {
        $file = $this->fileRepository->findOrFail($id);

        return Storage::download($file->path);
    }

    /*
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $activity = $this->activityRepository->findOrFail($id);

        try {
            Event::dispatch('activity.delete.before', $id);

            $this->activityRepository->delete($id);

            Event::dispatch('activity.delete.after', $id);

            return response()->json([
                'message' => trans('admin::app.activities.destroy-success', ['type' => trans('admin::app.activities.' . $activity->type)]),
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => trans('admin::app.activities.destroy-failed', ['type' => trans('admin::app.activities.' . $activity->type)]),
            ], 400);
        }
    }

    /**
     * Mass Delete the specified resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function massDestroy()
    {
        foreach (request('rows') as $activityId) {
            Event::dispatch('activity.delete.before', $activityId);

            $this->activityRepository->delete($activityId);

            Event::dispatch('activity.delete.after', $activityId);
        }

        return response()->json([
            'message' => trans('admin::app.response.destroy-success', ['name' => trans('admin::app.activities.title')])
        ]);
    }
}
