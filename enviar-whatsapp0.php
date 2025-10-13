<?php
// Configuración de la conexión a la base de datos
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'cbr_whatsapp';

// Crear la conexión a la base de datos
$conn = new mysqli($host, $user, $password, $dbname);

// Manejar errores de conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener el número de carátula del formulario
$numero_caratula = $_POST['numero'] ?? '';

if (empty($numero_caratula)) {
    die("Error: El número de carátula es un campo requerido.");
}

// Nombre del archivo temporal para el envío. Usamos un ID único para evitar conflictos.
$nombreArchivoTemporal = "certificado-wp-" . uniqid() . ".pdf";

// Consulta SQL para obtener los datos del cliente y el documento binario
// Ajusta esta consulta si los nombres de tus tablas o campos son diferentes
$sql = "SELECT t1.nombre, t1.telefono, t2.data 
        FROM cardex AS t1 
        JOIN certificados_listo AS t2 ON t1.numero = t2.caratula 
        WHERE t1.numero = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $numero_caratula);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Enlazar las variables del resultado
    $stmt->bind_result($documentoNombre, $documentoTelefono, $documentoPDF);
    $stmt->fetch();

    // Guardar el contenido binario del PDF en un archivo temporal
    file_put_contents($nombreArchivoTemporal, $documentoPDF);

    // Definir el mensaje de forma dinámica
    $mensaje = "Hola $documentoNombre,\n\nAdjuntamos el certificado solicitado.\n¡Saludos!";

    // Iniciar cURL para enviar el archivo
    $ch = curl_init();

    if (class_exists('CURLFile')) {
        $file_path = new CURLFile($nombreArchivoTemporal, 'application/pdf', 'certificado.pdf');
    } else {
        $file_path = "@" . realpath($nombreArchivoTemporal);
    }
    
    // Preparar los datos para la API de WhatsApp, incluyendo el archivo
    $postData = [
        "telefono" => $documentoTelefono,
        "mensaje" => $mensaje,
        "documento" => $file_path
    ];

    curl_setopt($ch, CURLOPT_URL, "http://localhost:3001/enviar");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error de cURL: ' . curl_error($ch);
    } else {
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error: La respuesta del servidor no es un JSON válido. Respuesta recibida: " . $response;
        } else if ($result['success'] ?? false) {
            echo "¡Listo! 🎉 Tu mensaje con el documento PDF ha sido enviado a WhatsApp exitosamente.";
        } else {
            echo "Hubo un error inesperado al enviar el mensaje. Mensaje del servidor: " . ($result['error'] ?? 'No se recibió mensaje de error.');
        }
    }
    
    curl_close($ch);
    
    // Eliminar el archivo temporal
    unlink($nombreArchivoTemporal);
    
} else {
    echo "Documento no encontrado en la base de datos para el número de carátula proporcionado.";
}

$stmt->close();
$conn->close();
?>