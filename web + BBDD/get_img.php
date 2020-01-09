<?php
// Página de traducir imagen de base de datos a JPG
// Créditos: https://stackoverflow.com/questions/7793009/how-to-retrieve-images-from-mysql-database-and-display-in-an-html-tag
// Adaptado a nuestra forma de trabajo (uso de mysqli como objeto)

if (!ctype_digit($_GET['id'])){ // Mal vamos si no es un número
	die('Eso no era un dígito');
}

$conexion = new mysqli(IP, USER, PASS, BASE_DATOS);

if ($conexion->connect_errno) {
	die("Error de conexión: (" . $conexion->connect_errno . ") " . $conexion->connect_error);
}

$sql = "SELECT `pic_data` FROM `pics` WHERE `pic_id` = '" . $_GET['id'] . "'";
//echo $sql . "<br>"; // Depuración

$resultado = $conexion->query($sql);
if (!$resultado) {
	die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
}

if ($resultado->num_rows == 0) { // Inexistente
	die('No está el registro'); 
} 

// Debe haber solo una única fila...
$fila = $resultado->fetch_assoc();
mysqli_close($conexion);

header("Content-type: image/jpeg");
echo $fila['pic_data'];

?>