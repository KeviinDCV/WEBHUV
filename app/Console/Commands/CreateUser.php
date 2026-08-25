<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
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
    protected $signature = 'huv:usuario
        {--simple : Acepta cualquier contraseña, sin exigir longitud ni símbolos}';

    protected $description = 'Crea una cuenta de acceso al portal';

    public function handle(): int
    {
        if ($this->option('simple')) {
            $this->components->warn('Sin reglas de contraseña: para cuentas de prueba.');
        }

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
            validate: fn (string $value): ?string => $this->passwordError($value),
        );

        $confirmation = password(label: 'Repita la contraseña', required: true);

        if ($secret !== $confirmation) {
            $this->error('Las contraseñas no coinciden. No se creó ninguna cuenta.');

            return self::FAILURE;
        }

        // El permiso se pregunta al final, cuando ya se sabe que la cuenta se
        // va a crear: pedirlo antes de comprobar que las dos contraseñas
        // coinciden sería pedir un dato para tirarlo acto seguido.
        //
        // Y el operador va primero, y por tanto marcado por omisión: quien
        // pulse Enter sin leer se lleva el permiso menor, no el mayor.
        $role = select(
            label: 'Permiso',
            options: [
                User::ROLE_OPERATOR => 'Operador — edita el portal',
                User::ROLE_ADMIN => 'Administrador — además, cuentas y estadísticas',
            ],
            default: User::ROLE_OPERATOR,
        );

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($secret),
            'role' => $role,
        ]);

        $this->newLine();
        $this->info("Cuenta creada: {$user->name} <{$user->email}> — {$user->role}");

        return self::SUCCESS;
    }

    /**
     * Lo que le falta a la contraseña, o null si vale.
     *
     * Con --simple no se le pide nada más que no estar vacía. Es para las
     * cuentas de prueba: escribir doce caracteres con símbolo cada vez que se
     * crea un usuario en el portátil de quien programa no protege nada, y lo
     * que de verdad pasaba es que se reutilizaba siempre la misma contraseña
     * buena, que es peor.
     *
     * La opción hay que pedirla a mano y sale un aviso al usarla: sin las dos
     * cosas, la regla acaba desapareciendo también de las cuentas de verdad, y
     * estas abren la administración de un hospital.
     */
    private function passwordError(string $value): ?string
    {
        if ($this->option('simple')) {
            return null;
        }

        $validator = Validator::make(
            ['password' => $value],
            ['password' => [Password::min(12)->letters()->numbers()->symbols()]]
        );

        return $validator->fails() ? $validator->errors()->first('password') : null;
    }
}
