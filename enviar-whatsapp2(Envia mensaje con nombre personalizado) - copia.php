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

// Obtener el número de carátula del formulario
$numero_formulario = $_POST['numero'] ?? '';

if (empty($numero_formulario)) {
    die("Error: El número de carátula es requerido.");
}

// Consulta SQL para obtener los datos del cliente
$sql = "SELECT nombre, telefono FROM cardex WHERE numero = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $numero_formulario);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($documentoNombre, $documentoTelefono);
    $stmt->fetch();

    // Definir el mensaje personalizado
    $mensaje = "Cordial saludo estimado(a) " . $documentoNombre . ", le estamos enviando el documento solicitado a través de la Plataforma Web del Conservador de Bienes Raíces.";

    // URL del PDF (se deja vacía para la prueba de solo mensaje)
    $pdfUrl = null;

    // Preparar los datos para la API de WhatsApp, incluyendo la URL del PDF
    $data = [
        "telefono" => $documentoTelefono,
        "mensaje" => $mensaje,
        "pdfUrl" => $pdfUrl
    ];

    // Iniciar cURL para enviar los datos JSON
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://192.168.1.134:3001/enviar"); // URL del bot
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
} else {
    echo "Documento no encontrado en la base de datos para el número de carátula proporcionado.";
}

$stmt->close();
$conn->close();
?>
