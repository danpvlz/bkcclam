<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PagoAllExport implements WithMultipleSheets
{
    protected $serie;
    protected $operacion;
    protected $sofdoc;
    protected $since;
    protected $until;
    protected $banco;
    protected $idAsociado;
    protected $idCliente;

    public function __construct(
        $serie,
        $operacion,
        $sofdoc,
        $since,
        $until,
        $banco,
        $idAsociado,
        $idCliente
    )
    {
        $this->serie = $serie;
        $this->operacion = $operacion;
        $this->sofdoc = $sofdoc;
        $this->since = $since;
        $this->until = $until;
        $this->banco = $banco;
        $this->idAsociado = $idAsociado;
        $this->idCliente = $idCliente;
    }
    
    public function sheets(): array
    {
            $sheets = [
                new PagoExport(
                    $this->serie,
                    $this->operacion,
                    $this->sofdoc,
                    $this->since,
                    $this->until,
                    1,
                    $this->idAsociado,
                    $this->idCliente
                ),
                new PagoExport(
                    $this->serie,
                    $this->operacion,
                    $this->sofdoc,
                    $this->since,
                    $this->until,
                    2,
                    $this->idAsociado,
                    $this->idCliente
                ),
                new PagoExport(
                    $this->serie,
                    $this->operacion,
                    $this->sofdoc,
                    $this->since,
                    $this->until,
                    3,
                    $this->idAsociado,
                    $this->idCliente
                ),
                new PagoExport(
                    $this->serie,
                    $this->operacion,
                    $this->sofdoc,
                    $this->since,
                    $this->until,
                    4,
                    $this->idAsociado,
                    $this->idCliente
                ),
                new PagoExport(
                    $this->serie,
                    $this->operacion,
                    $this->sofdoc,
                    $this->since,
                    $this->until,
                    5,
                    $this->idAsociado,
                    $this->idCliente
                )
            ];

        return $sheets;
    }
}