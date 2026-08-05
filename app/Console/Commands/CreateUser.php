<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Alta de cuentas del personal.
 *
 * El portal no tiene registro público: es la única vía de crear un usuario.
 * La contraseña se pide de forma oculta y nunca se pasa como argumento, para
 * que no quede registrada en el historial de la consola.
 */
class CreateUser extends Command
{
    protected $signature = 'huv:usuario';

    protected $description = 'Crea una cuenta de acceso al portal';

    public function handle(): int
    {
        $name = text(
            label: 'Nombre completo',
            required: true,
            validate: fn (string $value): ?string => strlen($value) < 3
                ? 'El nombre debe tener al menos 3 caracteres.'
                : null,
        );

        $email = text(
            label: 'Correo institucional',
            required: true,
            validate: function (string $value): ?string {
                $validator = Validator::make(
                    ['email' => $value],
                    ['email' => ['email', 'max:255', 'unique:users,email']],
                    ['unique' => 'Ya existe una cuenta con ese correo.']
                );

                return $validator->fails() ? $validator->errors()->first('email') : null;
            },
        );

        $secret = password(
            label: 'Contraseña',
            required: true,
            validate: function (string $value): ?string {
                $validator = Validator::make(
                    ['password' => $value],
                    ['password' => [Password::min(12)->letters()->numbers()->symbols()]]
                );

                return $validator->fails() ? $validator->errors()->first('password') : null;
            },
        );

        $confirmation = password(label: 'Repita la contraseña', required: true);

        if ($secret !== $confirmation) {
            $this->error('Las contraseñas no coinciden. No se creó ninguna cuenta.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($secret),
        ]);

        $this->newLine();
        $this->info("Cuenta creada: {$user->name} <{$user->email}>");

        return self::SUCCESS;
    }
}
