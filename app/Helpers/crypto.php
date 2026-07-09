<?php

/**
 * Cifrado simetrico con libsodium (XSalsa20-Poly1305) para secretos
 * de la aplicacion, p. ej. contrasenas de cuentas de correo.
 * La clave se define en .env como MAIL_ENCRYPTION_KEY (base64, 32 bytes).
 */

function mail_encryption_key(): string
{
    static $key = null;
    if ($key !== null) {
        return $key;
    }

    $raw = (string)env_value('MAIL_ENCRYPTION_KEY', '');
    if ($raw === '') {
        throw new RuntimeException('MAIL_ENCRYPTION_KEY no esta definida en el archivo .env.');
    }

    $decoded = base64_decode($raw, true);
    if ($decoded === false || strlen($decoded) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new RuntimeException('MAIL_ENCRYPTION_KEY debe ser base64 de exactamente 32 bytes.');
    }

    return $key = $decoded;
}

/** Cifra un texto plano; devuelve base64(nonce + cifrado). */
function encrypt(string $plaintext): string
{
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = sodium_crypto_secretbox($plaintext, $nonce, mail_encryption_key());
    return base64_encode($nonce . $cipher);
}

/** Descifra un valor generado por encrypt(). Lanza excepcion si es invalido. */
function decrypt(string $encoded): string
{
    $decoded = base64_decode($encoded, true);
    if ($decoded === false || strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        throw new RuntimeException('Valor cifrado invalido.');
    }

    $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $plain = sodium_crypto_secretbox_open($cipher, $nonce, mail_encryption_key());

    if ($plain === false) {
        throw new RuntimeException('No se pudo descifrar: clave incorrecta o datos corruptos.');
    }

    return $plain;
}
