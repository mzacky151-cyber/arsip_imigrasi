<?php

include "../config/koneksi.php";
include "../template/session.php";

wajibLevel(["admin"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$idUser = filter_input(
    INPUT_POST,
    "id_user",
    FILTER_VALIDATE_INT
);

$nama = trim($_POST["nama"] ?? "");

$nip = preg_replace(
    "/\D/",
    "",
    trim($_POST["nip"] ?? "")
);

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";
$level = $_POST["level"] ?? "";

/* ==========================
   VALIDASI DATA
========================== */

if (!$idUser) {

    echo "
    <script>
        alert('ID user tidak valid.');
        window.location='index.php';
    </script>
    ";

    exit;
}

if (
    $nama === "" ||
    $nip === "" ||
    $username === "" ||
    $level === ""
) {

    echo "
    <script>
        alert('Nama, NIP, username, dan level wajib diisi.');
        history.back();
    </script>
    ";

    exit;
}

if (!preg_match("/^[0-9]{5,30}$/", $nip)) {

    echo "
    <script>
        alert('NIP hanya boleh berisi 5 sampai 30 angka.');
        history.back();
    </script>
    ";

    exit;
}

if (!in_array($level, ["admin", "petugas"], true)) {

    echo "
    <script>
        alert('Level user tidak valid.');
        history.back();
    </script>
    ";

    exit;
}

/* ==========================
   CEK USER MASIH ADA
========================== */

$stmtUser = mysqli_prepare(
    $koneksi,
    "
    SELECT id_user
    FROM user
    WHERE id_user = ?
    LIMIT 1
    "
);

mysqli_stmt_bind_param(
    $stmtUser,
    "i",
    $idUser
);

mysqli_stmt_execute($stmtUser);

$hasilUser = mysqli_stmt_get_result($stmtUser);
$userAda = mysqli_fetch_assoc($hasilUser);

mysqli_stmt_close($stmtUser);

if (!$userAda) {

    echo "
    <script>
        alert('Data user tidak ditemukan.');
        window.location='index.php';
    </script>
    ";

    exit;
}

/* ==========================
   CEK USERNAME ATAU NIP DUPLIKAT
========================== */

$stmtCek = mysqli_prepare(
    $koneksi,
    "
    SELECT id_user
    FROM user
    WHERE
        (username = ? OR nip = ?)
        AND id_user <> ?
    LIMIT 1
    "
);

mysqli_stmt_bind_param(
    $stmtCek,
    "ssi",
    $username,
    $nip,
    $idUser
);

mysqli_stmt_execute($stmtCek);

$hasilCek = mysqli_stmt_get_result($stmtCek);

if (mysqli_num_rows($hasilCek) > 0) {

    mysqli_stmt_close($stmtCek);

    echo "
    <script>
        alert('Username atau NIP sudah digunakan oleh user lain.');
        history.back();
    </script>
    ";

    exit;
}

mysqli_stmt_close($stmtCek);

/* ==========================
   UPDATE USER
========================== */

if ($password !== "") {

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $stmtUpdate = mysqli_prepare(
        $koneksi,
        "
        UPDATE user
        SET
            nama = ?,
            nip = ?,
            username = ?,
            password = ?,
            level = ?
        WHERE id_user = ?
        "
    );

    mysqli_stmt_bind_param(
        $stmtUpdate,
        "sssssi",
        $nama,
        $nip,
        $username,
        $passwordHash,
        $level,
        $idUser
    );

} else {

    $stmtUpdate = mysqli_prepare(
        $koneksi,
        "
        UPDATE user
        SET
            nama = ?,
            nip = ?,
            username = ?,
            level = ?
        WHERE id_user = ?
        "
    );

    mysqli_stmt_bind_param(
        $stmtUpdate,
        "ssssi",
        $nama,
        $nip,
        $username,
        $level,
        $idUser
    );
}

if (!mysqli_stmt_execute($stmtUpdate)) {

    mysqli_stmt_close($stmtUpdate);

    die(
        "Gagal memperbarui user: " .
        mysqli_error($koneksi)
    );
}

mysqli_stmt_close($stmtUpdate);

/* Jika user mengedit akunnya sendiri */
if ($idUser === (int) $_SESSION["id_user"]) {

    $_SESSION["nama"] = $nama;
    $_SESSION["username"] = $username;
    $_SESSION["level"] = $level;
}

$_SESSION["user_update_success"] = true;

header("Location: index.php");
exit;