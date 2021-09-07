<?php

namespace App\Exports;

use App\Models\Associated\Associated;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AssociatedExport implements FromCollection, WithHeadings
{


    protected $idAsociado;
    protected $state;
    protected $debCollector;
    protected $comite;
    protected $promotor;
    protected $month;
    
     function __construct($idAsociado, $state, $debCollector,$comite,$promotor,$month) {
            $this->idAsociado = $idAsociado;
            $this->state = $state;
            $this->debCollector = $debCollector;
            $this->comite = $comite;
            $this->promotor = $promotor;
            $this->month = $month;
     }
     
    public function headings(): array
    {
        return ["Asociado", "Documento","Tipo", "Estado", "Direccion juridica", 'Direccion social' ,"Actividad", "Comite gremial","Importe", "Cobrador",
        'Telefonos','Correos', 'Representante','Ingreso','Promotor'];

    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $first= Associated::
        select(
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial, Persona.nombresCompletos) as asociado'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.ruc, Persona.documento) as documento'),
            \DB::raw('IF(Asociado.tipoAsociado=1, "Empresa", "Persona")'),
            \DB::raw('IF(Asociado.estado=1, "Activo", IF(Asociado.estado=2, "En proceso", IF(Asociado.estado=3, "Preactivo", "Retiro")))'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.direccion, Persona.direccion) as direccion'),
            'Asociado.direccionSocial',
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.actividad, Persona.actividad) as actividad'),
            'ComiteGremial.nombre as comitegremial',
            'Asociado.importeMensual',
            'Sector.descripcion as cobrador',
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.telefonos, Persona.telefonos) as telefonos'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.correos, Persona.correos) as correos'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Contacto.nombreCompleto , "-") as representante'),
            'Asociado.fechaIngreso',
            'Promotor.nombresCompletos'
        )
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Contacto', 'Contacto.idContacto', '=', 'Empresa.idRepresentante')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->join('ComiteGremial', 'ComiteGremial.idComite', '=', 'Asociado.idComiteGremial')
        ->join('Promotor', 'Promotor.idPromotor', '=', 'Asociado.idPromotor');

        if($this->idAsociado){
            $first->where('Asociado.idAsociado','=',$this->idAsociado);
        }

        if($this->state){
            $first->where('Asociado.estado',"=",$this->state == 4 ? 0 : $this->state);
        }

        if($this->debCollector){
            $first->where('Sector.idSector',$this->debCollector);
        }

        if($this->promotor){
            $first->where('Asociado.idPromotor',$this->promotor);
        }

        if($this->comite){
            $first->where('ComiteGremial.idComite',$this->comite);
        }

        if($this->month){
            $first->whereBetween('Asociado.fechaIngreso',[$this->month."-01",$this->month.'-31']);
        }
        
        $first
        ->orderBy('asociado', 'asc');             
        return $first->get();
    }
}