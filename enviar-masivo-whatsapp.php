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

// Incluir el archivo para registrar el envío.
require_once 'registrar-envio.php';

// Obtener las fechas de inicio y fin para el filtro masivo
$from_date = $_GET['from_date'] ?? date('Y-m-d') . ' 00:00:00';
$to_date = $_GET['to_date'] ?? date('Y-m-d') . ' 23:59:59';

echo "<h3>Detalle del Proceso de Envío Masivo:</h3>";
echo "<p><strong>Rango de Fechas:</strong> {$from_date} a {$to_date}</p>";
echo "<p><strong>Estados a Enviar:</strong> CARDEX o DESPACHADO</p>";
echo "<pre>"; // Usamos <pre> para que el formato de texto plano se mantenga

// 1. Obtener todos los documentos con estado 'CARDEX' o 'DESPACHADO' en el rango de fechas
$sql = "SELECT t1.numero, t1.nombre, t1.telefono, t1.estado, t2.data
        FROM cardex AS t1
        JOIN certificados_listo AS t2 ON t1.numero = t2.caratula
        WHERE (t1.estado = 'CARDEX' OR t1.estado = 'DESPACHADO') AND t1.ultima_mod BETWEEN ? AND ?
        ORDER BY t1.numero ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $from_date, $to_date);
$stmt->execute();
$result = $stmt->get_result();

$sent_count = 0;
$error_count = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $numero_caratula = $row['numero'];
        $documentoNombre = $row['nombre'];
        $documentoTelefono = $row['telefono'];
        $documentoPDF = $row['data'];
        $estado = $row['estado'];

        echo "Procesando documento {$numero_caratula} - {$documentoNombre}...\n";

        // Crear el archivo temporal
        $nombreArchivoTemporal = "certificado-wp-" . uniqid() . ".pdf";
        $rutaArchivoLocal = $nombreArchivoTemporal;
        file_put_contents($rutaArchivoLocal, $documentoPDF);

        // Definir la URL pública para el bot
        $pdfUrl = "http://192.168.1.134/pdf-wp/" . $nombreArchivoTemporal;

        // Definir el mensaje personalizado
        $mensaje = "Cordial saludo estimado(a) " . $documentoNombre . ", le estamos enviando el documento solicitado a través de la Plataforma Web del Conservador de Bienes Raíces.";

        // Preparar los datos para la API de WhatsApp
        $data = [
            "telefono" => $documentoTelefono,
            "mensaje" => $mensaje,
            "pdfUrl" => $pdfUrl
        ];

        // Iniciar cURL para enviar los datos JSON
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://192.168.1.134:3001/enviar");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $result_curl = json_decode($response, true);
        
        if (curl_errno($ch)) {
            echo "❌ ERROR: " . curl_error($ch) . " (Carátula: {$numero_caratula})\n";
            $error_count++;
        } else if (json_last_error() !== JSON_ERROR_NONE) {
            echo "❌ ERROR: La respuesta del bot no es un JSON válido. Respuesta: {$response} (Carátula: {$numero_caratula})\n";
            $error_count++;
        } else if (($result_curl['success'] ?? false)) {
            registrarEnvio($conn, $numero_caratula, $documentoTelefono, $documentoNombre, $estado);
            echo "✅ Envío Exitoso (Carátula: {$numero_caratula})\n";
            $sent_count++;
        } else {
            echo "❌ Fallo el envío (Carátula: {$numero_caratula}). Mensaje del bot: " . ($result_curl['error'] ?? 'Sin mensaje de error.') . "\n";
            $error_count++;
        }
        
        curl_close($ch);
        unlink($rutaArchivoLocal);
    }
} else {
    echo "No se encontraron documentos que cumplan con los criterios.\n";
}

$stmt->close();
$conn->close();

echo "\n--- Resumen ---\n";
echo "Proceso finalizado.\n";
echo "Documentos encontrados: " . ($sent_count + $error_count) . "\n";
echo "Documentos enviados: {$sent_count}\n";
echo "Documentos con error: {$error_count}\n";
echo "</pre>";
?>