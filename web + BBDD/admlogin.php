<?php 
// Junto a comprobar todo además comprobamos que la acción esté correcta desde el inicio
if(!isset($_POST['key']) || empty($_POST['key']) || !isset($_POST['user']) || empty($_POST['user']) || !isset($_POST['pass']) || empty($_POST['pass'])
	|| !isset($_POST['action']) || empty($_POST['action']) // Comprobaciones todo enviado
	|| ($_POST['action'] != 'o' && $_POST['action'] != 'p' && $_POST['action'] != 'l') ){ // action es solo algo en {o, p, l}
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

// Ahora comprobar si tiene permisos. Podría hacerse con un left join pero si no no se detecta cerradura inexistente.
// Un admin NUNCA expira aunque la BD diga lo contrario, no necesario comprobar.

$sql = "SELECT `type` FROM `users` WHERE `lockid` = '" . $key_id . "' AND `username` = '" . $conexion->real_escape_string($_POST['user']) . "' AND `pass` = '" . $conexion->real_escape_string($_POST['pass']) . "'";
//echo $sql;

$resultado = $conexion->query($sql);
if (!$resultado) {
	die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
}

if ($resultado->num_rows == 0) { // Incorrecto
	//GUARDAR EN LOGS. No necesario capturar cámara porque puede no estar ahí.
	$sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, '" . $key_id . "', '" . $conexion->real_escape_string($_POST['key']) . "', 'w', CURRENT_TIMESTAMP, 'Admin login erróneo para usuario " . $conexion->real_escape_string($_POST['user']) . ".', NULL)";
	//echo $sql;
	$resultado = $conexion->query($sql);
	if (!$resultado) {
		die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
	}

	die('<!DOCTYPE html>
<html>
	<body>
	<p>Login incorrecto.</p>
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

// ¿admin? 
$fila = $resultado->fetch_assoc();
// print_r($fila);

if ($fila['type'] != 'a') { // No admin
	//GUARDAR EN LOGS. No necesario capturar cámara porque puede no estar ahí.
	$sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, '" . $key_id . "', '" . $conexion->real_escape_string($_POST['key']) . "', 'w', CURRENT_TIMESTAMP, 'Admin login. Usuario " . $conexion->real_escape_string($_POST['user']) . ". NO es admin', NULL)";
	//echo $sql;
	$resultado = $conexion->query($sql);
	if (!$resultado) {
		die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
	}
	
	die('<!DOCTYPE html>
<html>
	<body>
	<p>No eres administrador.</p>
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
?>
<!DOCTYPE html>
<html>
<head>
<title>Acceso administradores de Smartlock</title>
</head>
<body>
<h1>Acceso administradores de Smartlock</h1>

<p>Hola, <?php echo $_POST['user']; ?>. Aquí tienes:</p>

<?php 
if ($_POST['action'] == 'o') { // Abrir puerta
	// Abrir puerta y registrar en logs
	$sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, '" . $key_id . "', '" . $conexion->real_escape_string($_POST['key']) . "', 'w', CURRENT_TIMESTAMP, 'Admin " . $conexion->real_escape_string($_POST['user']) . " solicita apertura.', NULL)";
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

	echo "<p>Puerta abierta</p>";
}

if ($_POST['action'] == 'p') { // Sacar foto
	$sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, '" . $key_id . "', '" . $conexion->real_escape_string($_POST['key']) . "', 'w', CURRENT_TIMESTAMP, 'Admin " . $conexion->real_escape_string($_POST['user']) . " solicita foto.', NULL)";
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

	echo "<p>Foto tomada</p>";
	sleep(10);
	
	// Sacar última ID con foto de los logs para esta cerradura, tenemos $key_id ya
	$sql = "SELECT `pic_id` FROM `logs` WHERE `lockid` = '" . $key_id . "' AND `pic_id` IS NOT NULL ORDER BY `id` DESC LIMIT 1";
	// echo $sql; 
	
	$resultado = $conexion->query($sql);
	if (!$resultado) {
		die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
	}

	// Debe haber solo una única fila...
	$fila = $resultado->fetch_assoc();
	
	echo "<p><img src='get_img.php?id=" . $fila['pic_id'] . "'></p>";
}

if ($_POST['action'] == 'l')	{
	$sql = "SELECT `id`, `lockcode`, `origin`, `date`, `log`, `pic_id` FROM `logs` WHERE `lockid` = '" . $key_id . "' ORDER BY `id` DESC LIMIT 20";
	// echo $sql; 
	
	$resultado = $conexion->query($sql);
	if (!$resultado) {
		die("Fallo al lanzar la consulta a la BD: " . $conexion->error);
	}
	
	if ($resultado->num_rows == 0) { // No hay resultados
?>
	<p>No hay entradas para este smartlock</p>
<?php 
	}
	else { // Hay resultados, mostrarlos
	?>
		<p>Mostrando las <?php echo $resultado->num_rows;?> entradas más recientes</p>
		<table border ="1">
			<tr>
				<th>Código smartlock</th>
				<th>Origen</th>
				<th>Fecha</th>
				<th>Texto</th>
				<th>Imagen</th>
			</tr>
	<?php while ($fila = $resultado->fetch_assoc()) {
			if ($fila['origin'] == 'w') $origen = "web";
			elseif ($fila['origin'] == 'l') $origen = "smartlock";
			else $origen = "otro"; 
				
			echo "		<tr>
				<td>" . $fila['lockcode'] . "</td>
				<td>" . $origen . "</td>
				<td>" . $fila['date'] . "</td>
				<td>" . $fila['log'] . "</td>
				<td>" . (is_null($fila['pic_id']) ? "(no hay)" : "<img src='get_img.php?id=" . $fila['pic_id'] . "' width='200' height='120'>") . "</td>
			</tr>\n	";
	}
	?>
		</table>
<?php } // End else resultados
} // End IF logs 
?> 

<form action="index.php" method="post">
    <input type="submit" value="Volver" />
	</form>
	</body>
</html>