<?php

return [

    // ---------------- Mensajes de las reglas de Laravel ----------------
    //
    // Los de la propia plataforma, traducidos. Los mensajes escritos a mano
    // para un formulario concreto no viven aquí sino en mensajes.php, junto al
    // resto de rótulos de la administración.
    //
    // Casi todos empiezan por «El campo :attribute» y no por el nombre a secas.
    // No es palabrería: el hueco lo rellena el nombre del campo —«imagen de
    // fondo», «fecha de cierre»— y sin un sustantivo delante el adjetivo tendría
    // que concordar con él, que unas veces es masculino y otras femenino. Con
    // «El campo» delante la concordancia es siempre con «campo» y ningún
    // mensaje sale mal escrito.

    'accepted' => 'Debe aceptar el campo :attribute.',
    'accepted_if' => 'Debe aceptar el campo :attribute cuando :other es :value.',
    'active_url' => 'El campo :attribute no es una dirección válida.',
    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha igual o posterior a :date.',
    'alpha' => 'El campo :attribute solo puede contener letras.',
    'alpha_dash' => 'El campo :attribute solo puede contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El campo :attribute solo puede contener letras y números.',
    'any_of' => 'El campo :attribute no es válido.',
    'array' => 'El campo :attribute debe ser un conjunto de valores.',
    'ascii' => 'El campo :attribute solo puede contener caracteres alfanuméricos y símbolos de un byte.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha igual o anterior a :date.',
    'between' => [
        'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
        'file' => 'El campo :attribute debe pesar entre :min y :max kilobytes.',
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'can' => 'El campo :attribute contiene un valor no autorizado.',
    'confirmed' => 'La confirmación del campo :attribute no coincide.',
    'contains' => 'Al campo :attribute le falta un valor obligatorio.',
    'current_password' => 'La contraseña no es correcta.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_equals' => 'El campo :attribute debe ser una fecha igual a :date.',
    'date_format' => 'El campo :attribute no corresponde con el formato :format.',
    'decimal' => 'El campo :attribute debe tener :decimal decimales.',
    'declined' => 'Debe rechazar el campo :attribute.',
    'declined_if' => 'Debe rechazar el campo :attribute cuando :other es :value.',
    'different' => 'El campo :attribute y :other deben ser distintos.',
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'digits_between' => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'dimensions' => 'Las dimensiones del campo :attribute no son válidas.',
    'distinct' => 'El campo :attribute tiene un valor repetido.',
    'doesnt_contain' => 'El campo :attribute no puede contener ninguno de estos valores: :values.',
    'doesnt_end_with' => 'El campo :attribute no puede terminar con ninguno de estos valores: :values.',
    'doesnt_start_with' => 'El campo :attribute no puede empezar con ninguno de estos valores: :values.',
    'email' => 'El campo :attribute no es una dirección de correo válida.',
    'encoding' => 'El campo :attribute debe estar codificado en :encoding.',
    'ends_with' => 'El campo :attribute debe terminar con uno de estos valores: :values.',
    'enum' => 'El valor elegido en el campo :attribute no es válido.',
    'exists' => 'El valor elegido en el campo :attribute no es válido.',
    'extensions' => 'El campo :attribute debe tener una de estas extensiones: :values.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'filled' => 'El campo :attribute no puede quedar vacío.',
    'gt' => [
        'array' => 'El campo :attribute debe tener más de :value elementos.',
        'file' => 'El campo :attribute debe pesar más de :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
        'string' => 'El campo :attribute debe tener más de :value caracteres.',
    ],
    'gte' => [
        'array' => 'El campo :attribute debe tener :value elementos o más.',
        'file' => 'El campo :attribute debe pesar :value kilobytes o más.',
        'numeric' => 'El campo :attribute debe ser mayor o igual que :value.',
        'string' => 'El campo :attribute debe tener :value caracteres o más.',
    ],
    'hex_color' => 'El campo :attribute debe ser un color hexadecimal válido.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El valor elegido en el campo :attribute no es válido.',
    'in_array' => 'El campo :attribute debe existir en :other.',
    'in_array_keys' => 'El campo :attribute debe contener al menos una de estas claves: :values.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'ip' => 'El campo :attribute no es una dirección IP válida.',
    'ipv4' => 'El campo :attribute no es una dirección IPv4 válida.',
    'ipv6' => 'El campo :attribute no es una dirección IPv6 válida.',
    'json' => 'El campo :attribute debe ser una cadena JSON válida.',
    'list' => 'El campo :attribute debe ser una lista.',
    'lowercase' => 'El campo :attribute debe ir en minúsculas.',
    'lt' => [
        'array' => 'El campo :attribute debe tener menos de :value elementos.',
        'file' => 'El campo :attribute debe pesar menos de :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser menor que :value.',
        'string' => 'El campo :attribute debe tener menos de :value caracteres.',
    ],
    'lte' => [
        'array' => 'El campo :attribute no puede tener más de :value elementos.',
        'file' => 'El campo :attribute debe pesar :value kilobytes o menos.',
        'numeric' => 'El campo :attribute debe ser menor o igual que :value.',
        'string' => 'El campo :attribute debe tener :value caracteres o menos.',
    ],
    'mac_address' => 'El campo :attribute no es una dirección MAC válida.',
    'max' => [
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
        'file' => 'El campo :attribute no puede pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
    ],
    'max_digits' => 'El campo :attribute no puede tener más de :max dígitos.',
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'mimetypes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El campo :attribute debe pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'min_digits' => 'El campo :attribute debe tener al menos :min dígitos.',
    'missing' => 'El campo :attribute no debe enviarse.',
    'missing_if' => 'El campo :attribute no debe enviarse cuando :other es :value.',
    'missing_unless' => 'El campo :attribute no debe enviarse salvo que :other sea :value.',
    'missing_with' => 'El campo :attribute no debe enviarse cuando :values está presente.',
    'missing_with_all' => 'El campo :attribute no debe enviarse cuando :values están presentes.',
    'multiple_of' => 'El campo :attribute debe ser múltiplo de :value.',
    'not_in' => 'El valor elegido en el campo :attribute no es válido.',
    'not_regex' => 'El formato del campo :attribute no es válido.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'password' => [
        'letters' => 'El campo :attribute debe contener al menos una letra.',
        'mixed' => 'El campo :attribute debe contener al menos una mayúscula y una minúscula.',
        'numbers' => 'El campo :attribute debe contener al menos un número.',
        'symbols' => 'El campo :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'El campo :attribute ha aparecido en una filtración de datos. Escriba otro distinto.',
    ],
    'present' => 'El campo :attribute debe estar presente.',
    'present_if' => 'El campo :attribute debe estar presente cuando :other es :value.',
    'present_unless' => 'El campo :attribute debe estar presente salvo que :other sea :value.',
    'present_with' => 'El campo :attribute debe estar presente cuando :values está presente.',
    'present_with_all' => 'El campo :attribute debe estar presente cuando :values están presentes.',
    'prohibited' => 'El campo :attribute no está permitido.',
    'prohibited_if' => 'El campo :attribute no está permitido cuando :other es :value.',
    'prohibited_if_accepted' => 'El campo :attribute no está permitido cuando se acepta :other.',
    'prohibited_if_declined' => 'El campo :attribute no está permitido cuando se rechaza :other.',
    'prohibited_unless' => 'El campo :attribute no está permitido salvo que :other esté entre :values.',
    'prohibits' => 'El campo :attribute impide que :other esté presente.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_array_keys' => 'El campo :attribute debe contener entradas para: :values.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_if_accepted' => 'El campo :attribute es obligatorio cuando se acepta :other.',
    'required_if_declined' => 'El campo :attribute es obligatorio cuando se rechaza :other.',
    'required_unless' => 'El campo :attribute es obligatorio salvo que :other esté entre :values.',
    'required_with' => 'El campo :attribute es obligatorio cuando :values está presente.',
    'required_with_all' => 'El campo :attribute es obligatorio cuando :values están presentes.',
    'required_without' => 'El campo :attribute es obligatorio cuando :values no está presente.',
    'required_without_all' => 'El campo :attribute es obligatorio cuando no está presente ninguno de :values.',
    'same' => 'El campo :attribute y :other deben coincidir.',
    'size' => [
        'array' => 'El campo :attribute debe contener :size elementos.',
        'file' => 'El campo :attribute debe pesar :size kilobytes.',
        'numeric' => 'El campo :attribute debe ser :size.',
        'string' => 'El campo :attribute debe tener :size caracteres.',
    ],
    'starts_with' => 'El campo :attribute debe empezar con uno de estos valores: :values.',
    'string' => 'El campo :attribute debe ser texto.',
    'timezone' => 'El campo :attribute no es una zona horaria válida.',
    'unique' => 'El campo :attribute ya está en uso.',
    'uploaded' => 'No se pudo subir el campo :attribute.',
    'uppercase' => 'El campo :attribute debe ir en mayúsculas.',
    'url' => 'El campo :attribute no es una dirección válida.',
    'ulid' => 'El campo :attribute no es un ULID válido.',
    'uuid' => 'El campo :attribute no es un UUID válido.',

    // ---------------- Mensajes por campo y regla ----------------
    //
    // Vacío a propósito: los mensajes propios de cada formulario se declaran en
    // su FormRequest y salen de mensajes.php, que es donde quien traduce los
    // encuentra junto a los demás rótulos de la administración.

    'custom' => [],

    // ---------------- Nombres de campo ----------------
    //
    // También vacío: cada FormRequest declara los suyos en attributes().

    'attributes' => [],

];
