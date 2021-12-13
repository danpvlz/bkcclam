<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORDEN DE PEDIDO</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&amp;display=swap"
        rel="stylesheet">
</head>

<body>
    <div style="
            font-family: 'Poppins', sans-serif;
            ">
        <div style="text-align: center;padding: 20px 0px;">
            <h1 style="margin-top: 0;line-height: 35px;font-weight: 700;">¡HEMOS REGISTRADO TU PEDIDO!</h1>
        </div>
        <div bgcolor="#ffffff" link="#3366cc" vlink="#3366cc" alink="#3366cc" marginheight="0" marginwidth="0"
            style="margin:0;padding:0">
            <br><br>
            <table border="0" cellpadding="0" cellspacing="0" align="center"
                style="border-collapse:collapse;border-spacing:0">
                <tbody>
                    <tr>
                        <td valign="top">
                            <div
                                style="display:block;padding:0;margin:0;height:100%;max-height:none;min-height:none;line-height:normal;overflow:visible">
                                <table border="0" cellpadding="0" cellspacing="0" align="center"
                                    style="border-collapse:collapse;border-spacing:0;width:742px">
                                    <tbody>
                                        <tr>
                                            <td style="width:50px"></td>
                                            <td>
                                                <img alt="CCLAM" src="https://cclam.org.pe/recursos.base/public/storage/logocclam.png"
                                                    border="0" style="border:none;padding:0;margin:0;height: 91px;"
                                                    class="CToWUd">
                                            </td>
                                            <td align="right"
                                                style="font-size:32px;/* font-weight:300; *//* font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; *//* color:rgb(136,136,136); */font-weight: 500;">
                                                Orden de pedido</td>
                                            <td style="width:50px"></td>
                                        </tr>
                                        <tr height="20">
                                            <td colspan="4">&nbsp;</td>
                                        </tr>
                                        <tr></tr>
                                        <tr>
                                            <td align="center" colspan="4">
                                                <table border="0" cellspacing="0" cellpadding="0"
                                                    style="border-collapse:collapse;border-spacing:0;" width="660">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding-bottom: 10px;"><span
                                                                    style="color: #8f918f;font-size: 15px;">Información
                                                                    de pedido</span></td>
                                                        </tr>
                                                        <tr style="background-color: #fafafa;">
                                                            <td style="display: flex;text-align: center;">
                                                                <div
                                                                    style="width: 50%;border: solid 1px white;font-size: 13px;display: flex;justify-content: center;align-items: center;padding: 5px;">
                                                                    <strong>N° PEDIDO</strong>
                                                                </div>
                                                                <div
                                                                    style="width: 50%;border: solid 1px white;font-size: 13px;display: flex;justify-content: center;align-items: center;padding: 5px;">
                                                                    <strong>FECHA DE PEDIDO</strong>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr style="font-size: 14px;">
                                                            <td style="display: flex;text-align: center;">
                                                                <div
                                                                    style="width: 50%;border: solid 1px white;padding: 5px; background-color: #fafafa;">
                                                                    <span style="display: block;">{{ $params->nro_pedido }}</span>
                                                                </div>
                                                                <div
                                                                    style="width: 50%;display: flex;justify-content: center;align-items: center;border: solid 1px white;padding: 5px;background-color: #fafafa;">
                                                                    <span>{{ $params->fecha_pedido }}</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding-top: 40px;padding-bottom: 10px;"><span
                                                                    style="color: #8f918f;font-size: 15px;">Información
                                                                    del cliente</span></td>
                                                        </tr>
                                                        <tr style="background-color: #fafafa;">
                                                            <td style="display: flex;text-align: center;">
                                                                <div
                                                                    style="width: 30%;border: solid 1px white;font-size: 13px;display: flex;justify-content: center;align-items: center;padding: 5px;">
                                                                    <strong>DOCUMENTO</strong>
                                                                </div>
                                                                <div
                                                                    style="width: 70%;border: solid 1px white;font-size: 13px;display: flex;justify-content: center;align-items: center;padding: 5px;">
                                                                    @if ($params->tipo_doc == "RUC")
                                                                    <strong>RAZÓN SOCIAL</strong>
                                                                    @else
                                                                    <strong>NOMBRES COMPLETOS</strong>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr style="font-size: 14px;">
                                                            <td style="display: flex;text-align: center;">
                                                                <div
                                                                    style="width: 30%;border: solid 1px white;padding: 5px;background-color: #fafafa;">
                                                                    <span style="display: block;">{{ $params->documento }}</span>
                                                                </div>
                                                                <div
                                                                    style="width: 70%;display: flex;justify-content: center;align-items: center;border: solid 1px white;padding: 5px;background-color: #fafafa;">
                                                                    <span>{{ $params->cliente }}</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr style="background-color: #fafafa;">
                                                            <td style="display: flex;text-align: center;">
                                                                <div
                                                                    style="width: 30%;border: solid 1px white;font-size: 13px;display: flex;justify-content: center;align-items: center;padding: 5px;">
                                                                    <strong>TELÉFONO</strong>
                                                                </div>
                                                                <div
                                                                    style="width: 70%;border: solid 1px white;font-size: 13px;display: flex;justify-content: center;align-items: center;padding: 5px;">
                                                                    <strong>CORREO</strong>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr style="font-size: 14px;">
                                                            <td style="display: flex;text-align: center;">
                                                                <div
                                                                    style="width: 30%;border: solid 1px white;padding: 5px;background-color: #fafafa;">
                                                                    <span style="display: block;">{{ $params->telefono }}</span>
                                                                </div>
                                                                <div
                                                                    style="width: 70%;display: flex;justify-content: center;align-items: center;border: solid 1px white;padding: 5px;background-color: #fafafa;">
                                                                    <span>{{ $params->receiver }}</span>
                                                                </div>

                                                            </td>
                                                        </tr>
                                                        @if ($params->direccion)
                                                        <tr style="background-color: #fafafa;">
                                                            <td style="display: flex;text-align: center;">
                                                                <div
                                                                    style="width: 100%;border-top: solid 1px white;font-size: 13px;display: flex;justify-content: center;align-items: center;padding: 5px;border-right: solid 1px white;border-bottom: solid 1px white;">
                                                                    <strong>DIRECCIÓN</strong>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr style="font-size: 14px;">
                                                            <td style="display: flex;text-align: center;">
                                                                <div
                                                                    style="width: 100%;border: solid 1px white;padding: 5px;background-color: #fafafa;">
                                                                    <span style="display: block;">{{ $params->direccion }}</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        @endif
                                                        <tr>
                                                            <td style="padding-top: 40px;padding-bottom: 10px;"><span
                                                                    style="color: #8f918f;font-size: 15px;">Información de pago</span></td>
                                                        </tr>
                                                        <tr style="background-color: #fafafa;">
                                                            <td style="display: flex;text-align: center;">
                                                                <div
                                                                    style="width: 33.3%;border: solid 1px white;font-size: 13px;display: flex;justify-content: center;align-items: center;padding: 5px;">
                                                                    <strong>ESTADO DE PAGO</strong>
                                                                </div>
                                                                <div
                                                                    style="width: 33.3%;border: solid 1px white;font-size: 13px;display: flex;justify-content: center;align-items: center;padding: 5px;">
                                                                    <strong>TARJETA</strong>
                                                                </div>
                                                                <div
                                                                    style="width: 33.3%;border: solid 1px white;font-size: 13px;display: flex;justify-content: center;align-items: center;padding: 5px;">
                                                                    <strong>MONTO</strong>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr style="font-size: 14px;">
                                                            <td style="display: flex;text-align: center;">
                                                                <div
                                                                    style="width: 33.3%;border: solid 1px white;padding: 5px; background-color: #fafafa;">
                                                                    <span style="display: block;">{{ $params->estadoPago }}</span>
                                                                </div>
                                                                <div
                                                                    style="width: 33.3%;display: flex;justify-content: center;align-items: center;border: solid 1px white;padding: 5px; background-color: #fafafa;">
                                                                    <span>{{ $params->tarjeta }}</span>
                                                                </div>
                                                                <div
                                                                    style="width: 33.3%;display: flex;justify-content: center;align-items: center;border: solid 1px white;padding: 5px; background-color: #fafafa;">
                                                                    <span>{{ $params->montoPago }}</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding-top: 20px;"><span
                                                                    style="color: #8f918f;font-size: 15px;">Detalle del
                                                                    pedido</span></td>
                                                        </tr>
                                                        <tr height="30">
                                                            <td></td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <table width="660" border="0" cellpadding="0"
                                                                    cellspacing="0"
                                                                    style="border-collapse:collapse;border-spacing:0;width:660px;color:rgb(51,51,51);font-size: 15px;">
                                                                    <tbody>

                                                                        <tr style="max-height:114px">

                                                                            <td style="line-height:15px;">
                                                                                <span dir="auto"
                                                                                    style="font-weight:600">
                                                                                    {{ $params->concepto }}
                                                                                </span>
                                                                            </td>
                                                                            <td width="100" align="right" valign="top"
                                                                                style="padding:0 20px 0 0;width:100px">
                                                                                <table cellpadding="0" cellspacing="0"
                                                                                    border="0"
                                                                                    style="border-collapse:collapse;border-spacing:0;font-size:12px;font-family:inherit">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="right"
                                                                                                colspan="3"><span
                                                                                                    style="font-weight:600;white-space:nowrap">S/.
                                                                                                    {{ $params->monto_concepto }}</span></td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 5px 0px;">
                                                                <table width="660" border="0" cellpadding="0"
                                                                    cellspacing="0"
                                                                    style="border-collapse:collapse;border-spacing:0;width:660px;color:rgb(51,51,51);font-size: 15px;">
                                                                    <tbody>
                                                                        <tr style="max-height:114px">
                                                                            <td style="line-height:15px;">
                                                                                <span dir="auto">TRÁMITE VIRTUAL
                                                                                </span>
                                                                            </td>
                                                                            <td width="100" align="right" valign="top"
                                                                                style="padding:0 20px 0 0;width:100px">
                                                                                <table cellpadding="0" cellspacing="0"
                                                                                    border="0"
                                                                                    style="border-collapse:collapse;border-spacing:0;font-size:12px;font-family:inherit">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="right"
                                                                                                colspan="3"><span>S/.
                                                                                                    5.00</span></td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        @if ($params->descuento)
                                                        <tr>
                                                            <td style="padding: 20px 0px 0px 0px;">
                                                                <table width="660" border="0" cellpadding="0"
                                                                    cellspacing="0"
                                                                    style="border-collapse:collapse;border-spacing:0;width:660px;color:rgb(51,51,51);font-size: 15px;font-weight: 600;">
                                                                    <tbody>

                                                                        <tr style="max-height:114px">
                                                                            <td style="line-height:15px;">
                                                                                <span dir="auto">DESCUENTO ({{$params->descuento}})
                                                                                </span>
                                                                            </td>
                                                                            <td width="100" align="right" valign="top"
                                                                                style="padding:0 20px 0 0;width:100px">
                                                                                <table cellpadding="0" cellspacing="0"
                                                                                    border="0"
                                                                                    style="border-collapse:collapse;border-spacing:0;font-size:12px;font-family:inherit">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="right"
                                                                                                colspan="3"><span>- S/. {{$params->descuento_monto}}</span></td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        @endif
                                                        <tr>
                                                            <td>
                                                                <table width="660" border="0" cellpadding="0"
                                                                    cellspacing="0"
                                                                    style="border-collapse:collapse;border-spacing:0;width:660px;color:rgb(51,51,51);">
                                                                    <tbody>
                                                                        <tr height="30">
                                                                            <td colspan="3"></td>
                                                                        </tr>
                                                                        <tr height="1">
                                                                            <td height="1" colspan="3"
                                                                                style="padding:0 10px 0 10px">
                                                                                <div
                                                                                    style="line-height:1px;height:1px;background-color:rgb(238,238,238)">
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                        <tr height="48"
                                                                            style="background-color: #fafafa;">
                                                                            <td align="right"
                                                                                style="color:rgb(102,102,102);font-size:10px;font-weight:600;padding:0 30px 0 0;border-width:1px;border-color:rgb(238,238,238)">
                                                                                TOTAL</td>
                                                                            <td width="1"
                                                                                style="background-color:rgb(238,238,238);width:1px">
                                                                            </td>
                                                                            <td width="90" align="right"
                                                                                style="width:120px;padding:0 20px 0 0;font-size:16px;font-weight:600;white-space:nowrap">
                                                                                S/. {{$params->total}}</td>
                                                                        </tr>
                                                                        <tr height="1">
                                                                            <td height="1" colspan="3"
                                                                                style="padding:0 10px 0 10px">
                                                                                <div
                                                                                    style="line-height:1px;height:1px;background-color:rgb(238,238,238)">
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr style="height:20px">
                                            <td colspan="4">&nbsp;</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="yj6qo"></div>
            <div class="adL">
                <br><br>
            </div>
        </div>
        <div style="width: 580px; margin:auto;">
            <small style="text-align: center; margin-top:1rem;">
                Mensaje enviado a
                <a href="#">{{ $params->receiver }}</a>.
                Este email es enviado automáticamente, por favor no responder.</small>
            <p></p>
        </div>
    </div>
</body>

</html>