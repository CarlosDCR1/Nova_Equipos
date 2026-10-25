<?php
require_once "api/core/Sesion.php";
Sesion::destruir();
header("Location: login.php");
exit;