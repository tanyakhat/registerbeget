<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function create()
    {
        return view('user.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'max:255', 'string'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' =>[ 'required', 'confirmed', 'min:8'],

        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice')->with('success', 'Registration successful! Please verify your email');

    }

    public function login()
    {
        return view('user.login');
    }

    public function loginAuth(Request $request)
    {
        //валидирование данных//
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'min:8'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard')->with('success', 'Welcome, ' . Auth::user()-> name . '!');
        }

        return back()->withErrors([
            'email' => 'Wrong email or password',
        ])->onlyInput('email');

    }
    //отправка на email
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }

    //Верификация Email
    public function dashboard()
    {
        return view('user.dashboard');
    }

    public function forgotPasswordStore(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['success' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function resetPasswordUpdate(Request $request)
    {
       $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8' , 'confirmed'],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => bcrypt($password), // Хэширование нового пароля
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );
//            $request->only('email', 'password', 'password_confirmation', 'token'),
//            function (User $user, string $password) {
//                $user->forceFill([
//                    'password' => $password
//                ])->setRememberToken(Str::random(60));
//
//                $user->save();
//
//                event(new PasswordReset($user));
//            }
//        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => [__($status)]]);

    }
}
