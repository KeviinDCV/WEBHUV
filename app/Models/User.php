<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Quien edita el portal: contenidos, temas, banners, el menú.
     *
     * Es todo lo que se podía hacer antes de que existieran los roles.
     */
    public const ROLE_OPERATOR = 'operador';

    /**
     * Quien además administra la herramienta: da de alta cuentas y ve las
     * estadísticas de uso.
     */
    public const ROLE_ADMIN = 'administrador';

    /** @var list<string> */
    public const ROLES = [self::ROLE_OPERATOR, self::ROLE_ADMIN];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** ¿Administra la herramienta, y no solo el contenido? */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** El nombre del rol tal como se le enseña a una persona. */
    public function roleLabel(): string
    {
        return __('admin-usuarios.rol.'.$this->role);
    }
}
