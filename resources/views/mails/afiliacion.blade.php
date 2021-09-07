<html lang="es"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <title>Nueva afiliación</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
</head>
<body>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;1,100&display=swap');
        
        body{
            font-family: 'Roboto', sans-serif;
        }
    </style>
    <div style="
            background-color: #f1f1f1;">
        <div style="
            width: 600px;
            margin: auto;
            display: block;
            padding: 3rem 0;">
            <div style="
                text-align: center;
                padding: 3rem;
                background-color: white;
                border-radius: 1rem;
                box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 12px;
                font-size: .9rem;"
            >
                <img style="
                width: 160px;" src="https://www.cclam.org.pe/wp-content/uploads/2021/02/logo-png-con-bordes-1-e1613147392892.png">
                <h2 style="font-size: 1.7rem;">¡Nueva afiliacion!</h2>
                <p>Se ha registrado una nueva afiliación.</p>
                <p>Estos son los datos del nuevo asociado:</p>
                <p style="margin-top:2rem;">Nombre:</p>
                <strong style="font-size: 1.4rem;">{{ $demo->afiliado }}</strong>
                <p style="margin-top:2rem;">Documento:</p>
                <strong style="font-size: 1.4rem;">{{ $demo->documento }}</strong>
                <p style="margin-top:2rem;">Promotor:</p>
                <strong style="font-size: 1.4rem;">{{ $demo->promotor }}</strong>
            </div>
            <div  style="
            text-align: center;
            ">
                <p>
                    Mensaje enviado a 
                    <a href="#">{{ $demo->receiver }}</a>. 
                    Este email es enviado automáticamente, por favor no responder.</p>
                </p>
            </div>
    </div>
</body></html>