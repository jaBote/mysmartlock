<?php 

if(!isset($_POST['key']) || empty($_POST['key']) || !isset($_POST['user']) || empty($_POST['user']) || !isset($_POST['pass']) || empty($_POST['pass'])){
	// ¿Olvidos? Meh, no se loguea tampoco
	die('<!DOCTYPE html>
<html>
	<body>
	<p>Te falta algún dato por introducir o no son adecuados.</p>
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
// print_r($_POST);

// Esencial comprobar si existe la cerradura
// Conexión SQL a la base de datos
$conexion = new mysqli(IP, USER, PASS, BASE_DATOS);

if ($conexion->connect_errno) {
	die("Error de conexión: (" . $conexion->connect_errno . ") " . $conexion->connect_error);
}

$sql = "SELECT `id` FROM `locks` WHERE `code` = '" . $conexion->real_escape_string($_POST['key']) . "'";
//echo $sql . "<br>"; // Depuración

$resultado = $conexion->query($sql);
if (!$resultado) {
	die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
}

if ($resultado->num_rows == 0) { // Inexistente
	// Guardar en logs por si alguien está intentando entrar de verdad
	$sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, NULL, '" . $conexion->real_escape_string($_POST['key']) . "', 'w', CURRENT_TIMESTAMP, 'Cerradura inexistente: " . $conexion->real_escape_string($_POST['key']) . "', NULL)";
	echo $sql;
	$resultado = $conexion->query($sql);
	if (!$resultado) {
		die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
	}
	
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

// Debe haber solo una única fila...
$fila = $resultado->fetch_assoc();
$key_id = $fila['id'];

// Ahora comprobar si tiene permisos. Podría haberse hecho antes en left join pero si no, no se detecta cerradura inexistente

$sql = "SELECT UNIX_TIMESTAMP(`expiry`) AS `expires` FROM `users` WHERE `lockid` = '" . $key_id . "' AND `username` = '" . $conexion->real_escape_string($_POST['user']) . "' AND `pass` = '" . $conexion->real_escape_string($_POST['pass']) . "'";
//echo $sql;

$resultado = $conexion->query($sql);
if (!$resultado) {
	die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
}

if ($resultado->num_rows == 0) { // Incorrecto
	// Insertar en logs
	$sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, '" . $key_id . "', '" . $conexion->real_escape_string($_POST['key']) . "', 'w', CURRENT_TIMESTAMP, 'Login erróneo para usuario " . $conexion->real_escape_string($_POST['user']) . ". Petición de foto.', NULL)";
	//echo $sql;
	$resultado = $conexion->query($sql);
	if (!$resultado) {
		die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
	}
	
	// Pedir foto
	$sql = "INSERT INTO `commands` (`lockid`, `action`, `date`) VALUES ('" . $key_id . "', 'p', CURRENT_TIMESTAMP)";
	//echo $sql;
	$resultado = $conexion->query($sql);
	if (!$resultado) {
		die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
	}
	
	die('<!DOCTYPE html>
<html>
	<body>
	<p>Login incorrecto. Sonríe a la cámara.</p>
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

// ¿Cuenta activa? 
$fila = $resultado->fetch_assoc();
// print_r($fila);

if (time() > $fila['expires'] && !empty($fila['expires'])) { // Caducado
	// Insertar en logs
	$sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, '" . $key_id . "', '" . $conexion->real_escape_string($_POST['key']) . "', 'w', CURRENT_TIMESTAMP, 'Usuario expirado: " . $conexion->real_escape_string($_POST['user']) . ". Petición de foto.', NULL)";
	//echo $sql;
	$resultado = $conexion->query($sql);
	if (!$resultado) {
		die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
	}
	
	// Pedir foto
	$sql = "INSERT INTO `commands` (`lockid`, `action`, `date`) VALUES ('" . $key_id . "', 'p', CURRENT_TIMESTAMP)";
	//echo $sql;
	$resultado = $conexion->query($sql);
	if (!$resultado) {
		die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
	}

	die('<!DOCTYPE html>
<html>
	<body>
	<p>Tu cuenta ha caducado. Sonríe a la cámara.</p>
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

// Login correcto. Insertar en logs y abrir.
$sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, '" . $key_id . "', '" . $conexion->real_escape_string($_POST['key']) . "', 'w', CURRENT_TIMESTAMP, 'Login correcto para usuario " . $conexion->real_escape_string($_POST['user']) . ". Petición de apertura.', NULL)";
//echo $sql;
$resultado = $conexion->query($sql);
if (!$resultado) {
	die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
}

// Abrir
$sql = "INSERT INTO `commands` (`lockid`, `action`, `date`) VALUES ('" . $key_id . "', 'o', CURRENT_TIMESTAMP)";
//echo $sql;
$resultado = $conexion->query($sql);
if (!$resultado) {
	die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
}
?>

<!DOCTYPE html>
<html>
	<body>
	<p>Hola, <?php echo $_POST['user']; ?>. Puedes pasar.</p>
	
	<form action="index.php" method="post">
    <input type="submit" value="Volver" />
	</form>
	</body>
</html>