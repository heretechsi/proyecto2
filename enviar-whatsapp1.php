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

// Obtener los datos del formulario (POST)
$nombre_formulario = $_POST['nombre'] ?? '';
$telefono_formulario = $_POST['telefono'] ?? '';

var_dump($_POST);

if (empty($nombre_formulario) || empty($telefono_formulario)) {
    die("Error: Faltan datos requeridos (nombre o teléfono).");
}

// Definir el mensaje de prueba
$mensaje = "¡Bienvenido!";

// Preparar los datos para la API de WhatsApp, sin el PDF
$data = [
    "telefono" => $telefono_formulario,
    "mensaje" => $mensaje
];

// Iniciar cURL para enviar los datos JSON
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://192.168.1.134:3001/enviar");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error de cURL: ' . curl_error($ch);
} else {
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error: La respuesta del servidor no es un JSON válido. Respuesta recibida: " . $response;
    } else if ($result['success'] ?? false) {
        echo "¡Listo! 🎉 Tu mensaje ha sido enviado a WhatsApp exitosamente.";
    } else {
        echo "Hubo un error inesperado al enviar el mensaje. Mensaje del servidor: " . ($result['error'] ?? 'No se recibió mensaje de error.');
    }
}

curl_close($ch);
$conn->close();
?>
