<?php
$telefono = "56920085674";
$mensaje = "Hola Juan Heredia,\n\nGracias por tu solicitud.\nAquí tienes el documento solicitado en PDF.\n¡Saludos!";
$nombreArchivo = "certificado-wp.pdf"; // Así funcionaba antes, y así debe funcionar ahora.

$data = [
    "telefono" => $telefono,
    "mensaje" => $mensaje,
    "nombreArchivo" => $nombreArchivo // La API solo necesita el nombre del archivo
];

$options = [
    "http" => [
        "header"  => "Content-type: application/json",
        "method"  => "POST",
        "content" => json_encode($data)
    ]
];

$context = stream_context_create($options);

$response = @file_get_contents("http://localhost:3001/enviar", false, $context);

if ($response === FALSE) {
    echo "¡Ups! Tuvimos un problema. No pudimos enviar el mensaje. Por favor, intenta de nuevo más tarde o contáctanos directamente.";
} else {
    $result = json_decode($response, true);
    if ($result['success'] ?? false) {
        echo "¡Listo! 🎉 Tu mensaje con el documento PDF ha sido enviado a WhatsApp exitosamente.";
    } else {
        echo "Hubo un error inesperado al enviar el mensaje. Por favor, revisa la consola de la aplicación para más detalles.";
    }
}
?>