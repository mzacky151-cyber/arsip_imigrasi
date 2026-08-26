<?php

session_start();
require_once "../config/koneksi.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    header("Location: index.php?error=1");
    exit;
}

$sql = "
    SELECT
        id_user,
        nama,
        username,
        password,
        level
    FROM user
    WHERE username = ?
    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    header("Location: index.php?error=1");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

$loginBerhasil = false;

if ($user) {
    $passwordDatabase = $user["password"];

    /*
     * Memeriksa apakah password sudah berbentuk hash.
     */
    $infoHash = password_get_info($passwordDatabase);
    $sudahHash = $infoHash["algo"] !== null;

    if ($sudahHash) {
        $loginBerhasil = password_verify($password, $passwordDatabase);
    } else {
        /*
         * Dukungan sementara untuk password lama yang masih berupa teks biasa.
         */
        $loginBerhasil = hash_equals($passwordDatabase, $password);

        /*
         * Setelah berhasil login, password lama langsung diubah menjadi hash.
         */
        if ($loginBerhasil) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $updatePassword = mysqli_prepare(
                $koneksi,
                "UPDATE user SET password = ? WHERE id_user = ?"
            );

            if ($updatePassword) {
                mysqli_stmt_bind_param(
                    $updatePassword,
                    "si",
                    $passwordHash,
                    $user["id_user"]
                );

                mysqli_stmt_execute($updatePassword);
                mysqli_stmt_close($updatePassword);
            }
        }
    }
}

mysqli_stmt_close($stmt);

if (!$loginBerhasil) {
    header("Location: index.php?error=1");
    exit;
}

session_regenerate_id(true);

$_SESSION["login"]    = true;
$_SESSION["id_user"]  = (int) $user["id_user"];
$_SESSION["nama"]     = $user["nama"];
$_SESSION["username"] = $user["username"];
$_SESSION["level"]    = $user["level"];

header("Location: ../dashboard/index.php");
exit;