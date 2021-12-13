<html lang="es"><head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORDEN DE PEDIDO CONFIRMADA</title>
</head>
<body>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap');
    body{
        font-family: 'Poppins', sans-serif;
        text-align: center;
    }
    .text{
        font-family: 'Poppins', sans-serif;
    }
    .title{
        font-size:32px;
        margin:0px;
    }
    .subcontainer{
        text-align:left;
        width:100%;
        margin-top:20px;
    }
    .subtitle{
        color: #8f918f;
        font-size: 15px;
    }
    .table-detail{
        width: 100%;
        margin-bottom:10px;
    }
    .row-table{
        font-family: 'Poppins', sans-serif;
        background-color: #fafafa;
    }
    .row-table th{
        padding:5px;
        font-weight: 700;
    }
    .row-table-data{
        text-align:center;
        font-family: 'Poppins', sans-serif;
    }
    .row-table-data span{
        display: block;
        font-family: 'Poppins', sans-serif;
    }
    .row-table-data td{
        padding:5px;
    }
    .row-table-detail{
        font-family: 'Poppins', sans-serif;
    }
    .row-table-total{
        background-color: #fafafa;
    }
    .row-table-total td{
        padding:10px 5px;
    }
</style>
    <div bgcolor="#ffffff" link="#3366cc" vlink="#3366cc" alink="#3366cc" marginheight="0" marginwidth="0" style="margin:0;padding:0;width: 600px; margin: auto;
    font-family: 'Poppins', sans-serif;">
        <div style="display:flex;justify-content: space-between;">
            <img alt="CCLAM" src="https://cclam.org.pe/recursos.base/public/storage/logocclam.png" border="0" style="border:none;padding:0;margin:0;height: 91px;">
            <p align="right" class="title text">Orden de pedido</p>
        </div>
        <div class="subcontainer">
            <p class="subtitle text">Información de pedido</p>
        </div>
        <table class="table-detail">
            <thead>
                <tr class="row-table">
                    <th>N° PEDIDO</th>
                    <th>FECHA DE PEDIDO</th>
                </tr>
            </thead>
            <tbody>
                <tr class="row-table-data">
                    <td>
                        <span>{{ $nro_pedido }}</span>
                        <span>Referenciar en la transferencia</span>
                    </td>
                    <td>{{ $fecha_pedido }}</td>
                </tr>
            </tbody>
        </table>
        <div class="subcontainer">
            <p class="subtitle text">Información del cliente</p>
        </div>
        <table class="table-detail">
            <thead>
                <tr class="row-table">
                    <th>DOCUMENTO</th>
                    @if ($tipo_doc == "RUC")
                    <th>RAZÓN SOCIAL</th>
                    @else
                    <th>NOMBRES COMPLETOS</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                <tr class="row-table-data">
                    <td>{{ $documento }}</td>
                    <td>{{ $cliente }}</td>
                </tr>
            </tbody>
        </table>
        <table class="table-detail">
            <thead>
                <tr class="row-table">
                    <th>TELÉFONO</th>
                    <th>CORREO</th>
                </tr>
            </thead>
            <tbody>
                <tr class="row-table-data">
                    <td>{{ $telefono }}</td>
                    <td>{{ $receiver }}</td>
                </tr>
            </tbody>
        </table>
        @if ($direccion)
        <table class="table-detail">
            <thead>
                <tr class="row-table">
                    <th>DIRECCIÓN</th>
                </tr>
            </thead>
            <tbody>
                <tr class="row-table-data">
                    <td>{{ $direccion }}</td>
                </tr>
            </tbody>
        </table>
        @endif
        <div class="subcontainer">
            <p class="subtitle text">Detalle del pedido</p>
        </div>
        <table class="table-detail">
            <tbody>
                <tr class="row-table-detail">
                    <td><strong>{{ $concepto }}</strong></td>
                    <td style="text-align:right;"><strong>S/. {{ $monto_concepto }}</strong></td>
                </tr>
                <tr class="row-table-detail">
                    <td>TRÁMITE VIRTUAL</td>
                    <td style="text-align:right;">S/. 5.00</td>
                </tr>
                @if ($descuento)
                <tr class="row-table-detail">
                    <td>DESCUENTO ({{ $descuento }})</td>
                    <td style="text-align:right;">- S/. {{ $descuento_monto }}</td>
                </tr>
                @endif
            </tbody>
        </table>
        <table class="table-detail">
            <tbody>
                <tr class="row-table-total">
                    <td><strong>TOTAL</strong></td>
                    <td style="text-align:right;"><strong>S/. {{ $total }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>