<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION["login"]) ||
    $_SESSION["login"] !== true ||
    !isset($_SESSION["id_user"], $_SESSION["nama"], $_SESSION["level"])
) {
    $_SESSION = [];

    session_destroy();

    header("Location: ../login/index.php?session=expired");
    exit;
}

/**
 * Memeriksa apakah pengguna memiliki level tertentu.
 */
function punyaAkses(array $levelDiizinkan): bool
{
    return isset($_SESSION["level"]) &&
        in_array($_SESSION["level"], $levelDiizinkan, true);
}

/**
 * Membatasi halaman berdasarkan level pengguna.
 */
function wajibLevel(array $levelDiizinkan): void
{
    if (!punyaAkses($levelDiizinkan)) {
        header("Location: ../dashboard/index.php?akses=ditolak");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Perlindungan CSRF
|--------------------------------------------------------------------------
*/

function tokenCsrf(): string
{
    if (
        !isset($_SESSION["csrf_token"]) ||
        !is_string($_SESSION["csrf_token"]) ||
        $_SESSION["csrf_token"] === ""
    ) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function verifikasiCsrf(?string $token): bool
{
    return isset($_SESSION["csrf_token"])
        && is_string($_SESSION["csrf_token"])
        && is_string($token)
        && hash_equals($_SESSION["csrf_token"], $token);
}