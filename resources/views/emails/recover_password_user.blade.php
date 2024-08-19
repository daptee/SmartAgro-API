<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Bienvenido a {{ config('services.clinic_name') }}</title>
</head>
<body>
    <p style="white-space: pre-line">
        Hola {{ $user->name }}.
        Hemos recibido una solicitud para reestablecer tu contraseña.
        
        Tu nueva password es: <h3><strong>{{ $str_random_password }}</strong></h3>
        Te recomendamos, al ingresar al sistema, cambiarla por una de tu eleccion.

        <br><br>

        Muchas gracias.

        <br>
        
        El equipo de SmartAgro.
    </p>
</body>
</html>