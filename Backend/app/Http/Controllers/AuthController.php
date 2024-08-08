<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use Illuminate\Support\Facades\Password;
use Carbon\Carbon;

class AuthController extends Controller
{
    // Inscription utilisateur
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'sexe' => 'required|string|in:male,female,other', 
            'date_of_birth' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Calculate the user's age
        $dateOfBirth = Carbon::parse($request->date_of_birth);
        $age = $dateOfBirth->age;

        if ($age < 13 || $age > 35) {
            return response()->json(['message' => 'User must be between 15 and 35 years old.'], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'sexe' => $request->sexe, 
            'date_of_birth' => $request->date_of_birth
        ]);

        // Créer un token de vérification
        // $verificationUrl = URL::temporarySignedRoute(
        //     'verification.verify',
        //     now()->addMinutes(60),
        //     ['id' => $user->id, 'hash' => sha1($user->email)]
        // );

        // Envoyer l'e-mail de vérification
        // Mail::to($user->email)->send(new VerifyEmail($verificationUrl));

        // return response()->json(['message' => 'Registration successful, please check your email for verification link'], 201);
        return response()->json(['message' => 'Registration successful !!!'], 201);
    }

    // Connexion utilisateur
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    
    public function logout(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    // Vérification par e-mail
    public function verify(Request $request)
    {
        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired verification link'], 400);
        }

        $user = User::findOrFail($request->id);

        if (!hash_equals((string) $request->hash, sha1($user->email))) {
            return response()->json(['message' => 'Invalid verification link'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verified successfully']);
    }

    // Mot de passe oublié
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent to your email'])
            : response()->json(['message' => 'Unable to send reset link'], 400);
    }

    // Réinitialiser le mot de passe
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                $user->tokens()->delete();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successfully'])
            : response()->json(['message' => 'Failed to reset password'], 400);
    }

    // Afficher le profil utilisateur
     public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    // Mise à jour du profil utilisateur
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'sexe' => 'sometimes|string|max:255',
            'date_of_birth' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->has('sexe')) {
            $user->sexe = $request->sexe;
        }

        if ($request->has('date_of_birth')) {
            $user->date_of_birth = $request->date_of_birth;
        }

        $user->save();

        return response()->json(['message' => 'Profile updated successfully']);
    }

    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token,'token_type' => 'Bearer',], 200);
    }

}