<?php

/**
 * Procesa las imagenes subidas para el hero del landing: valida el archivo,
 * lo recorta al aspecto del hero sin deformarlo (crop centrado), lo
 * redimensiona, lo comprime y lo convierte a WebP generando una variante
 * desktop (1800x650) y una mobile (900x325). Solo se sirven las variantes
 * optimizadas; el archivo original nunca se guarda en public.
 */
final class ImageOptimizerService
{
    public const HERO_WIDTH = 1800;
    public const HERO_HEIGHT = 650;
    public const MOBILE_WIDTH = 900;

    private const MAX_BYTES = 10485760;      // 10 MB por imagen subida
    private const MAX_PIXELS = 40000000;     // ~40 MP para no agotar memoria
    private const MIN_WIDTH = 800;
    private const MIN_HEIGHT = 400;
    private const WEBP_QUALITY = 82;
    private const JPEG_QUALITY = 84;

    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * Valida y procesa una imagen subida ($_FILES['...']). Devuelve los datos
     * de la variante principal para registrarla en la tabla files.
     *
     * @return array{disk_path:string, width:int, height:int, size_bytes:int, mime:string}
     * @throws RuntimeException con mensaje apto para mostrar al usuario.
     */
    public function processHeroImage(array $file): array
    {
        [$tmp, $sourceMime] = $this->validate($file);

        return $this->generateVariants($tmp, $sourceMime);
    }

    /**
     * Igual que processHeroImage pero desde un archivo local del servidor
     * (seeders / migracion de imagenes existentes), sin las validaciones
     * propias de una subida HTTP.
     *
     * @return array{disk_path:string, width:int, height:int, size_bytes:int, mime:string}
     * @throws RuntimeException
     */
    public function processHeroImageFromFile(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('No se encontro el archivo de imagen: ' . $path);
        }

        return $this->generateVariants($path, $this->validateImageInfo($path));
    }

    /** @return array{disk_path:string, width:int, height:int, size_bytes:int, mime:string} */
    private function generateVariants(string $sourcePath, string $sourceMime): array
    {
        $source = $this->createImage($sourcePath, $sourceMime);
        [$cropX, $cropY, $cropW, $cropH] = $this->coverCrop(imagesx($source), imagesy($source));

        $ext = function_exists('imagewebp') ? 'webp' : 'jpg';
        $base = bin2hex(random_bytes(12));
        $dir = $this->slidersDir();
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            imagedestroy($source);
            throw new RuntimeException('No se pudo crear la carpeta de sliders en el servidor.');
        }

        $variants = [
            [self::HERO_WIDTH, self::HERO_HEIGHT],
            [self::MOBILE_WIDTH, (int)round(self::MOBILE_WIDTH * self::HERO_HEIGHT / self::HERO_WIDTH)],
        ];

        $written = [];
        foreach ($variants as [$w, $h]) {
            $target = $dir . '/' . $base . '-' . $w . '.' . $ext;
            if (!$this->writeVariant($source, $cropX, $cropY, $cropW, $cropH, $w, $h, $target, $ext)) {
                imagedestroy($source);
                foreach ($written as $path) {
                    @unlink($path);
                }
                throw new RuntimeException('No se pudo generar la imagen optimizada.');
            }
            $written[] = $target;
        }
        imagedestroy($source);

        $mainPath = $dir . '/' . $base . '-' . self::HERO_WIDTH . '.' . $ext;

        return [
            'disk_path' => 'uploads/sliders/' . $base . '-' . self::HERO_WIDTH . '.' . $ext,
            'width' => self::HERO_WIDTH,
            'height' => self::HERO_HEIGHT,
            'size_bytes' => (int)(filesize($mainPath) ?: 0),
            'mime' => $ext === 'webp' ? 'image/webp' : 'image/jpeg',
        ];
    }

    /**
     * Ruta publica (disk_path) de la variante mobile a partir de la principal.
     */
    public static function mobileVariantPath(string $diskPath): string
    {
        return str_replace('-' . self::HERO_WIDTH . '.', '-' . self::MOBILE_WIDTH . '.', $diskPath);
    }

    /**
     * Elimina de forma segura la variante principal y la mobile de una imagen
     * de slider. Solo actua dentro de public/uploads/.
     */
    public function deleteHeroImage(string $diskPath): void
    {
        foreach ([$diskPath, self::mobileVariantPath($diskPath)] as $path) {
            delete_public_upload($path);
        }
    }

    /** @return array{0:string,1:string} [ruta temporal, mime real] */
    private function validate(array $file): array
    {
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('La imagen supera el tamano maximo permitido por el servidor.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir la imagen. Intenta nuevamente.');
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Archivo temporal invalido.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new RuntimeException('La imagen supera el limite de 10 MB.');
        }

        return [$tmp, $this->validateImageInfo($tmp)];
    }

    /**
     * Validaciones comunes de la imagen (formato, resolucion, dimensiones
     * minimas) para subidas HTTP y archivos locales. Devuelve el mime real.
     */
    private function validateImageInfo(string $path): string
    {
        $info = getimagesize($path);
        if (!$info) {
            throw new RuntimeException('El archivo no es una imagen valida.');
        }

        $mime = (string)($info['mime'] ?? '');
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException('Formato no permitido. Usa JPG, PNG, WebP o GIF.');
        }

        [$width, $height] = $info;
        if ($width * $height > self::MAX_PIXELS) {
            throw new RuntimeException('La imagen tiene demasiada resolucion (maximo ~40 megapixeles).');
        }
        if ($width < self::MIN_WIDTH || $height < self::MIN_HEIGHT) {
            throw new RuntimeException('La imagen es muy pequena: minimo ' . self::MIN_WIDTH . 'x' . self::MIN_HEIGHT . ' px (recomendado 1800x650).');
        }

        return $mime;
    }

    private function createImage(string $path, string $mime): GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/gif' => imagecreatefromgif($path),
            default => false,
        };

        if (!$image instanceof GdImage) {
            throw new RuntimeException('No se pudo procesar la imagen en el servidor.');
        }

        return $image;
    }

    /**
     * Calcula el recorte centrado que cubre el aspecto del hero (1800:650)
     * sin deformar la imagen original.
     *
     * @return array{0:int,1:int,2:int,3:int} [x, y, ancho, alto] del recorte
     */
    private function coverCrop(int $width, int $height): array
    {
        $targetRatio = self::HERO_WIDTH / self::HERO_HEIGHT;
        $sourceRatio = $width / $height;

        if ($sourceRatio > $targetRatio) {
            $cropW = (int)round($height * $targetRatio);
            return [(int)floor(($width - $cropW) / 2), 0, $cropW, $height];
        }

        $cropH = (int)round($width / $targetRatio);
        return [0, (int)floor(($height - $cropH) / 2), $width, $cropH];
    }

    private function writeVariant(GdImage $source, int $cropX, int $cropY, int $cropW, int $cropH, int $width, int $height, string $target, string $ext): bool
    {
        $canvas = imagecreatetruecolor($width, $height);
        if ($ext === 'webp') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
        } else {
            // JPEG no soporta transparencia: fondo blanco.
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        }

        imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, $width, $height, $cropW, $cropH);
        $ok = $ext === 'webp'
            ? imagewebp($canvas, $target, self::WEBP_QUALITY)
            : imagejpeg($canvas, $target, self::JPEG_QUALITY);
        imagedestroy($canvas);

        return (bool)$ok;
    }

    private function slidersDir(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/sliders';
    }
}
