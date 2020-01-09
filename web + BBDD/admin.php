<!DOCTYPE html>
<html>
<head>
<title>Acceso administradores de Smartlock</title>
</head>
<body>
<h1>Acceso administradores de Smartlock</h1>

<p>Escriba usuario y contraseña, código smartlock y qué desea hacer. Debe ser administrador.</p>

<form action="/admlogin.php" method="post">
  Smartlock:<input type="text" name="key"><br><br>
  Usuario: <input type="text" name="user"><br><br>
  Passwd: <input type="password" name="pass"><br><br>
  Acción: <br>
  <input type="radio" id="r1" name="action" value="o"><label for="r1">Apertura remota</label><br>
  <input type="radio" id="r2" name="action" value="p"><label for="r2">Tomar foto (tarda unos 10 segundos)</label><br>
  <input type="radio" id="r3" name="action" value="l"><label for="r3">Ver logs</label><br><br>
  <input type="submit" value="Administrar">
</form>

</body>
</html>