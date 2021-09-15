<?php

namespace App\Exports;

use App\Models\Associated\Associated;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CoberturaExport implements FromCollection, WithHeadings
{
    protected $debCollector;
    
     function __construct($debCollector) {
        $this->debCollector = $debCollector;
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
        ->join('Promotor', 'Promotor.idPromotor', '=', 'Asociado.idPromotor')
        ->where('Asociado.estado',"=",1)
        ->whereRaw('Asociado.idAsociado not in ( SELECT idAsociado FROM `Membresia` where estado=2 and year=YEAR(NOW()) and mes=MONTH(NOW()) and TIMESTAMPDIFF(MONTH,updated_at,CURRENT_DATE())>0)');

        if($this->debCollector){
            $first->where('Sector.idSector',$this->debCollector);
        }
        
        $first
        ->orderBy('asociado', 'asc');             
        return $first->get();
    }
}