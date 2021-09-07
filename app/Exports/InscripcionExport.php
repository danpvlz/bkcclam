<?php

namespace App\Exports;

use App\Models\FormacionYDesarrollo\Inscripcion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InscripcionExport implements FromCollection, WithHeadings
{


    protected $curso;
    protected $participante;
    
     function __construct($curso, $participante) {
            $this->curso = $curso;
            $this->participante = $participante;
     }

    public function headings(): array
    {
        return ["NOTIFICAR [SI|NO]", "TIPO DOC [DNI|C.E|PASS|OTRO]","DOCUMENTO", "NOMBRE", "CORREO", "CELULAR", "CURSO"];

    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $first= Inscripcion::
        select(
            \DB::raw('"SI"'),
            \DB::raw('"DNI"'),
            'Participante.dni', 
            \DB::raw('UPPER(CONCAT(Participante.nombres," ",Participante.apellidoPaterno," ",Participante.apellidoMaterno)) as participante'),
            'Participante.correo',
            'Participante.celular',
            \DB::raw('UPPER(Curso.descripcion)')
            
        )
        ->join('Participante', 'Participante.idParticipante', '=', 'Inscripcion.idParticipante')
        ->join('Curso', 'Curso.idCurso', '=', 'Inscripcion.idCurso');

        if($this->curso){
            $first->where('Curso.idCurso','=',$this->curso);
        }

        if($this->participante){
            $first->where('Participante.idParticipante','=',$this->participante);
        };             
        return $first->get();
    }
}
