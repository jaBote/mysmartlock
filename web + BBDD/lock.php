<?php

if(!isset($_GET['key']) || empty($_GET['key'])){
	// No necesario guardar en logs, puede ser cualquiera curioseando
	die('<!DOCTYPE html>
<html>
	<body>
	<p>Busca una cerradura primero.</p>
	<button onclick="atras()">Atrás</button>
	
	<script>
	function atras() {
		window.history.go(-1);
	}
	</script>
	</body>
</html>	
');
}

// Conexión SQL a la base de datos
$conexion = new mysqli(IP, USER, PASS, BASE_DATOS);

if ($conexion->connect_errno) {
	die("Error de conexión: (" . $conexion->connect_errno . ") " . $conexion->connect_error);
}

$sql = "SELECT `id` FROM `locks` WHERE `code` = '" . $conexion->real_escape_string($_GET['key']) . "'";
// echo $sql . "<br>"; // Depuración

$resultado = $conexion->query($sql);
if (!$resultado) {
	die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
}

if ($resultado->num_rows == 0) { // Inexistente
	// Ídem a caso anterior
	die('<!DOCTYPE html>
<html>
	<body>
	<p>Parece que la cerradura que buscas no existe.</p>
	<button onclick="atras()">Atrás</button>
	
	<script>
	function atras() {
		window.history.go(-1);
	}
	</script>
	</body>
</html>
'); 
}

// Una única fila que no es necesario rescatar, más tarde
// Presentar formulario acceso
?>

<!DOCTYPE html>
<head>
<title>Abriendo llave <?php echo $_GET['key']; ?></title>
</head>
<body>
<h1>Abriendo llave <?php echo $_GET['key']; ?></h1>

<p>Escriba usuario y contraseña</p>

<form action="/open.php" method="post">
  <input type="hidden" name="key" value="<?php echo $_GET['key']; ?>">
  Usuario: <input type="text" name="user"><br><br>
  Passwd: <input type="password" name="pass"><br><br>
  <input type="submit" value="Abrir smartlock">
</form>

</body>
</html>