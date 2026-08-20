@php
    use App\Support\CommentWall;

    $uid = $uid ?? '';
    $selected = (int) old('comment_wall', $value ?? CommentWall::NINGUNA);
@endphp

{{--
    Participación de un contenido, con las tres opciones del portal
    institucional. Es lo que decide si la ficha lleva el botón «Participa».
--}}
<label for="comment_wall{{ $uid }}" class="sr-only">{{ __('admin-contenidos.participacion.etiqueta') }}</label>
<select id="comment_wall{{ $uid }}" name="comment_wall"
        class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
    @foreach (CommentWall::options() as $option => $label)
        <option value="{{ $option }}" @selected($selected === $option)>{{ $label }}</option>
    @endforeach
</select>
