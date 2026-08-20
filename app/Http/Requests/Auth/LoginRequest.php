<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Intentos permitidos antes de bloquear temporalmente la combinación. */
    private const MAX_ATTEMPTS = 5;

    /** Duración del bloqueo, en segundos. */
    private const DECAY_SECONDS = 60;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => __('mensajes.campo.correo'),
            'password' => __('mensajes.campo.contrasena'),
        ];
    }

    /**
     * Intenta autenticar con las credenciales de la petición.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            // Un único mensaje genérico: distinguir «no existe» de «contraseña
            // incorrecta» permitiría averiguar qué correos tienen cuenta.
            throw ValidationException::withMessages([
                'email' => __('mensajes.acceso.credenciales'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        // Dos mensajes enteros y no uno con la unidad en un hueco: en inglés
        // el plural no se forma igual y una plantilla partida dejaría frases
        // imposibles de traducir bien.
        throw ValidationException::withMessages([
            'email' => $seconds > 60
                ? __('mensajes.acceso.demasiados_minutos', ['n' => (int) ceil($seconds / 60)])
                : __('mensajes.acceso.demasiados_segundos', ['n' => $seconds]),
        ]);
    }

    /** El límite se aplica por combinación de correo e IP, no solo por IP. */
    protected function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower((string) $this->string('email')).'|'.$this->ip()
        );
    }
}
