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

// Validar que se haya subido un archivo
if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
    die("Error: No se seleccionó un archivo o hubo un error al subirlo.");
}

// Validar que el archivo sea un PDF
$fileMimeType = $_FILES['documento']['type'];
if ($fileMimeType !== 'application/pdf') {
    die("Error: El archivo debe ser un documento PDF.");
}

// Validar que el número de carátula no esté vacío
if (empty($numero_formulario)) {
    die("Error: El número de carátula es requerido para subir el documento.");
}

// Leer el contenido binario del archivo subido
$documentoPDF = file_get_contents($_FILES['documento']['tmp_name']);

// Consulta SQL para actualizar el campo 'data' en la tabla 'certificados_listo'
// El campo 'data' debe ser de tipo BLOB o similar en tu base de datos
$sql = "UPDATE certificados_listo SET data = ? WHERE caratula = ?";

$stmt = $conn->prepare($sql);
// 's' para string para el documento binario, 'i' para integer para la carátula
$stmt->bind_param("si", $documentoPDF, $numero_formulario);

if ($stmt->execute()) {
    echo "¡Éxito! 🎉 El documento se ha actualizado correctamente para la carátula " . htmlspecialchars($numero_formulario) . ".";
} else {
    echo "Hubo un error al actualizar el documento: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
