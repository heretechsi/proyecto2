<?php
// Incluye tu archivo de conexión a la base de datos aquí
// Ejemplo: include 'config.php';
// Asegúrate de tener las variables $host, $dbname, $user, $password

// Configuración de la conexión a la base de datos



$conn = new mysqli('localhost', 'root', '', 'pdfblob');

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $numero = $_POST['numero'];
    $foja = $_POST['foja'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];

    // Validar y obtener el archivo PDF
    if (isset($_FILES['documento']) && $_FILES['documento']['error'] === UPLOAD_ERR_OK) {
        // Leer el contenido del archivo PDF en un array de bytes
        $documentoPDF = file_get_contents($_FILES['documento']['tmp_name']);

        // Preparar la consulta SQL para insertar los datos
        $stmt = $conn->prepare("INSERT INTO documentos (numero, foja, nombre, tipo, documento) VALUES (?, ?, ?, ?, ?)");
        
        // Asignar los parámetros. 'sbss' representa string, blob, string, string.
        $stmt->bind_param("issss", $numero, $foja, $nombre, $tipo, $documentoPDF);
        
        if ($stmt->execute()) {
            echo "¡Documento subido y guardado exitosamente en la base de datos!";
        } else {
            echo "Error al guardar el documento: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error: No se ha seleccionado un archivo o el archivo está corrupto.";
    }

    $conn->close();
} else {
    echo "Método de solicitud no válido.";
}
?>