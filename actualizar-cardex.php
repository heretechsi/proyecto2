<?php
// Configuración de la conexión a la base de datos
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'pdfblob';

// Crear la conexión a la base de datos
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener los datos del formulario
$numero = $_POST['numero'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$rut = $_POST['rut'] ?? '';
$domicilio = $_POST['domicilio'] ?? '';
$email = $_POST['email'] ?? '';
$telefono = $_POST['telefono'] ?? '';

if (empty($numero)) {
    die("Error: El número de carátula es requerido para actualizar los datos.");
}

// Construir la consulta SQL dinámicamente
$updates = [];
$params = [];
$types = '';

if (!empty($nombre)) {
    $updates[] = "nombre = ?";
    $params[] = $nombre;
    $types .= 's';
}
if (!empty($rut)) {
    $updates[] = "rut = ?";
    $params[] = $rut;
    $types .= 's';
}
if (!empty($domicilio)) {
    $updates[] = "domicilio = ?";
    $params[] = $domicilio;
    $types .= 's';
}
if (!empty($email)) {
    $updates[] = "email = ?";
    $params[] = $email;
    $types .= 's';
}
if (!empty($telefono)) {
    $updates[] = "telefono = ?";
    $params[] = $telefono;
    $types .= 's';
}

if (empty($updates)) {
    die("No se han proporcionado datos para actualizar.");
}

$sql = "UPDATE cardex SET " . implode(", ", $updates) . " WHERE numero = ?";
$params[] = $numero;
$types .= 'i';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo "¡Éxito! 🎉 Los datos del cliente se han actualizado correctamente para la carátula " . htmlspecialchars($numero) . ".";
} else {
    echo "Hubo un error al actualizar los datos: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
