<?php

namespace LeadBrowser\Admin\Http\Controllers\User;

use Illuminate\Support\Facades\Auth;
use LeadBrowser\Admin\Http\Controllers\Controller;
use LeadBrowser\User\Models\User;

class SessionController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        if (auth()->guard('user')->check()) {
            return redirect()->route('dashboard.index');
        } else {
            if (strpos(url()->previous(), 'admin') !== false) {
                $intendedUrl = url()->previous();
            } else {
                $intendedUrl = route('dashboard.index');
            }

            session()->put('url.intended', $intendedUrl);

            return view('admin::sessions.login');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (! auth()->guard('user')->attempt(request(['email', 'password']), request('remember'))) {
            session()->flash('warning', trans('admin::app.sessions.login.login-error'));

            return redirect()->back();
        }

        if (auth()->guard('user')->user()->status == 0) {
            session()->flash('warning', trans('admin::app.sessions.login.activate-warning'));

            auth()->guard('user')->logout();

            return redirect()->route('auth.login.create');
        }

        return redirect()->intended(route('dashboard.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        auth()->guard('user')->logout();

        return redirect()->route('auth.login.create');
    }
}