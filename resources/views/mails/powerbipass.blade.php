<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de permiso</title>
</head>
<body>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100;300;400;500;700&display=swap');
    body{
        font-family: 'Noto Sans JP', sans-serif;
    }
    </style>
    <div style="
        width: 700px;
        box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
        margin: auto;
        padding: 2rem 3rem;
        border-radius: 1rem;
        text-align: center;
        ">
        <img src="https://www.cclam.org.pe/gs/static/media/logocclam.8304b563.png" style="width: 126px;" />
        <h1>Solicitud de permiso</h1>
        <p>{{ $demo->solicitante }} ha solicitado acceso para ver los indicadores.</p>
        <p style="margin-top: 2rem;"><strong>¿Conceder permiso?</strong></p>
        <br />
        <div style="margin: 1rem 0 2.5rem 0;">
            <a style="
                margin: 3rem 0;
                padding: .7rem 2.3rem;
                color: #13448c;
                border: none;
                border-radius: .3rem;
                font-size: 1rem;
                font-weight: bold;
                cursor: pointer;
            " href="{{ $demo->linkDeny }}" >No, negar permiso</a>
            <a style="
                margin: 3rem 0;
                padding: .7rem 2.3rem;
                background-color: #13448c;
                color: white;
                border: none;
                border-radius: .3rem;
                font-size: 1rem;
                font-weight: 100;
                cursor: pointer;
            " href="{{ url($demo->linkGrant) }}" >Sí, dar permiso</a>
        </div>
    </div>
    <div  style="
    display: flex;
    justify-content: center;
    opacity: .8;
    padding: 1rem .5rem 3rem .5rem;
    ">
        <p>
            Mensaje enviado a 
            <a href="#">{{ $demo->receiver }}</a>. 
            Este email es enviado automáticamente, por favor no responder.</p>
        </p>
    </div>
</body>
</html>