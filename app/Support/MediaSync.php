<?php

namespace App\Support;

use App\Models\ContentMedia;
use App\Support\ResponsiveImage;
use App\Models\LibraryImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Sincroniza fotos, vídeo y archivos con lo que llega del formulario de medios.
 *
 * Vive aparte del controlador de contenidos porque el mismo bloque del editor
 * —«Agrega Foto», «Agrega vídeo», «Agrega archivo» y la biblioteca— se usa tal
 * cual en los artículos de un tema. Duplicar estas líneas es justo la
 * divergencia que ya sufrieron dos componentes de este proyecto.
 *
 * El dueño solo tiene que ofrecer una relación media() de ContentMedia.
 */
final class MediaSync
{
    public function __construct(
        private readonly Model $owner,
        /** Carpeta del disco público donde se guarda lo que se sube. */
        private readonly string $directory,
        /**
         * Si el dueño publica fotos y vídeo, además de archivos.
         *
         * Un documento y una convocatoria solo publican archivos: su ficha no
         * pinta ni imagen ni galería. Y no basta con excluirlas de la
         * validación —`Rule::excludeIf` solo las deja fuera de los datos
         * validados—, porque aquí se leen de la petición: se guardarían en
         * disco y no se verían en ninguna parte.
         */
        private readonly bool $gallery = true,
    ) {}

    public function apply(Request $request): void
    {
        $existing = $this->owner->media()->get()->keyBy('id');

        // 1. Bajas.
        foreach ($request->input('media_delete', []) as $id) {
            $existing->get((int) $id)?->delete();
            $existing->forget((int) $id);
        }

        // 2. Descripciones de lo que se conserva.
        foreach ($request->input('media_alt', []) as $id => $alt) {
            $existing->get((int) $id)?->update(['alt' => $alt]);
        }

        // 3. Fotos nuevas.
        $alts = $request->input('photo_alts', []);

        foreach ($this->gallery ? $request->file('photos', []) : [] as $index => $photo) {
            $ruta = $photo->store($this->directory, 'public');

            // Las miniaturas de la tarjeta. Sin ellas, un listado sirve la foto
            // original —hasta dos megas— en un hueco de 220 por 150.
            ResponsiveImage::generate($ruta, 'public', ResponsiveImage::CARD_WIDTHS);

            $this->owner->media()->create([
                'type' => ContentMedia::TYPE_IMAGE,
                'path' => $ruta,
                'alt' => $alts[$index] ?? null,
                'original_name' => $photo->getClientOriginalName(),
                'size' => $photo->getSize(),
                'position' => $this->owner->media()->max('position') + 1,
            ]);
        }

        // 4. Archivos nuevos.
        $titles = $request->input('file_titles', []);

        foreach ($request->file('files', []) as $index => $file) {
            $this->owner->media()->create([
                'type' => ContentMedia::TYPE_FILE,
                // En la carpeta del dueño, no en una suelta llamada
                // «documentos»: así lo de un tema vive junto a lo de ese tema y
                // una copia de seguridad no tiene que reconciliar tres árboles.
                'path' => $file->store($this->directory, 'public'),
                'alt' => ($titles[$index] ?? null) ?: $file->getClientOriginalName(),
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'position' => $this->owner->media()->max('position') + 1,
            ]);
        }

        if ($this->gallery) {
            // 5. Imágenes de la biblioteca: se sincroniza el conjunto elegido.
            $this->syncLibraryImages($request->input('library_ids', []));

            // 6. Vídeo: uno por contenido.
            $this->syncVideo($request->input('video_url'));

            $this->settleMainImage($request->input('media_main'));
        }
    }

    /**
     * Vincula y desvincula imágenes de la biblioteca.
     *
     * Al desvincular solo se borra la fila de enlace: el archivo pertenece a la
     * biblioteca y puede estar en uso en otros contenidos.
     *
     * @param  list<int|string>  $chosen
     */
    private function syncLibraryImages(array $chosen): void
    {
        $chosen = array_map('intval', $chosen);

        $linked = $this->owner->media()->whereNotNull('library_image_id')->get();

        $linked->whereNotIn('library_image_id', $chosen)->each->delete();

        $already = $linked->pluck('library_image_id')->all();

        LibraryImage::whereIn('id', array_diff($chosen, $already))
            ->get()
            ->each(function (LibraryImage $image): void {
                $this->owner->media()->create([
                    'library_image_id' => $image->id,
                    'type' => ContentMedia::TYPE_IMAGE,
                    'path' => $image->path,
                    'alt' => $image->alt,
                    'original_name' => $image->original_name,
                    'size' => $image->size,
                    'position' => $this->owner->media()->max('position') + 1,
                ]);
            });
    }

    private function syncVideo(?string $url): void
    {
        $video = $this->owner->media()->where('type', ContentMedia::TYPE_VIDEO)->first();

        if (blank($url)) {
            $video?->delete();
        } elseif ($video) {
            $video->update(['url' => $url]);
        } else {
            $this->owner->media()->create(['type' => ContentMedia::TYPE_VIDEO, 'url' => $url]);
        }
    }

    /**
     * Marca la foto principal.
     *
     * Solo puede haber una: es la que representa al contenido en los listados.
     * Si no se eligió ninguna, se toma la primera para que las tarjetas no
     * queden sin imagen.
     */
    private function settleMainImage(mixed $chosen): void
    {
        $images = $this->owner->media()->where('type', ContentMedia::TYPE_IMAGE)->orderBy('position')->get();

        if ($images->isEmpty()) {
            return;
        }

        $main = $images->firstWhere('id', (int) $chosen) ?? $images->first();

        $this->owner->media()
            ->where('type', ContentMedia::TYPE_IMAGE)
            ->update(['is_main' => false]);

        $main->update(['is_main' => true]);
    }
}
