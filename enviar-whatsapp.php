<?php
// Configuración de la conexión a la base de datos
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'pdfblob';

// Crear la conexión a la base de datos
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    // Si la conexión falla, se detiene y se envía un mensaje de error JSON.
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conn->connect_error]));
}

// Incluir el archivo para registrar el envío.
require_once 'registrar-envio.php';

// Obtener el número de carátula del formulario
$numero_formulario = $_POST['numero'] ?? '';

if (empty($numero_formulario)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: El número de carátula es requerido.']);
    exit;
}

// 1. Validar el estado 'CARDEX' antes de proceder
$sql_estado = "SELECT estado FROM cardex WHERE numero = ? LIMIT 1";
$stmt_estado = $conn->prepare($sql_estado);
$stmt_estado->bind_param("i", $numero_formulario);
$stmt_estado->execute();
$stmt_estado->store_result();

if ($stmt_estado->num_rows > 0) {
    $stmt_estado->bind_result($estado);
    $stmt_estado->fetch();
    $stmt_estado->close();

    if ($estado !== 'KARDEX' && $estado !== 'DESPACHADO') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Documento no listo para enviar. El estado actual es: ' . $estado]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Documento no encontrado en la base de datos.']);
    exit;
}

// 2. Proceder con el envío
$sql = "SELECT t1.nombre, t1.telefono, t2.data 
        FROM cardex AS t1 
        JOIN certificados_listo AS t2 ON t1.numero = t2.caratula 
        WHERE t1.numero = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $numero_formulario);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($documentoNombre, $documentoTelefono, $documentoPDF);
    $stmt->fetch();

    // Crear el archivo temporal
    $nombreArchivoTemporal = "certificado-wp-" . uniqid() . ".pdf";
    $rutaArchivoLocal = $nombreArchivoTemporal;
    file_put_contents($rutaArchivoLocal, $documentoPDF);

    // Definir la URL pública para el bot
    $pdfUrl = "http://172.22.68.238/pdf-wp/" . $nombreArchivoTemporal;

    // Definir el mensaje personalizado
    $mensaje = "Cordial saludo estimado(a) " . $documentoNombre . ", le estamos enviando el documento solicitado a través de la Plataforma Web del Conservador de Bienes Raíces. \n\nPor favor no responder a este mensaje, se envía desde una herramienta de gestión.";

    // Preparar los datos para la API de WhatsApp, incluyendo la URL del PDF
    $data = [
        "telefono" => $documentoTelefono,
        "mensaje" => $mensaje,
        "pdfUrl" => $pdfUrl
    ];

    // Iniciar cURL para enviar los datos JSON
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://172.22.68.238:3001/enviar"); // URL del bot
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error de cURL: ' . curl_error($ch)]);
    } else {
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error: La respuesta del servidor no es un JSON válido. Respuesta recibida: ' . $response]);
        } else if ($result['success'] ?? false) {
            // Registrar el envío exitoso
            registrarEnvio($conn, $numero_formulario, $documentoTelefono, $documentoNombre, $estado);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => "🎉 ¡Listo! Tu mensaje con el documento PDF ha sido enviado exitosamente."]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Hubo un error inesperado al enviar el mensaje. Mensaje del servidor: " . ($result['error'] ?? 'No se recibió mensaje de error.')]);
        }
    }
    
    curl_close($ch);
    // Eliminar el archivo temporal
    unlink($rutaArchivoLocal);
    
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => "Documento no encontrado en la base de datos para el número de carátula proporcionado."]);
}

$conn->close();
?>