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

// SQL para crear la tabla si no existe
$sql_create_table = "CREATE TABLE IF NOT EXISTS envios_whatsapp (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_caratula INT(11) NOT NULL,
    telefono VARCHAR(50) NOT NULL,
    nombre_cliente VARCHAR(255) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_create_table) === TRUE) {
    echo "¡Tabla 'envios_whatsapp' creada exitosamente o ya existe!";
} else {
    echo "Error al crear la tabla: " . $conn->error;
}

// Cerrar la conexión
$conn->close();
?>
