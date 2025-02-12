<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\FormatoRegistroRecibo;

class FormatoRegistroReciboSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_basico',
            'descripcion'=>'Basico',
            'cantidad'=>'cantidad_basico',
            'importe'=>'monto_basico',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_antiguedad',
            'descripcion'=>'Antiguedad',
            'cantidad'=>'cantidad_antiguedad',
            'importe'=>'monto_antiguedad',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_resolucion',
            'descripcion'=>'Resolucion',
            'cantidad'=>'cantidad_resolucion',
            'importe'=>'monto_resolucion',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_categoria23',
            'descripcion'=>'Categoria 23',
            'cantidad'=>'cantidad_categoria23',
            'importe'=>'monto_categoria23',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_titulo',
            'descripcion'=>'Titulo',
            'cantidad'=>'cantidad_titulo',
            'importe'=>'monto_titulo',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_presentismo',
            'descripcion'=>'Presentismo',
            'cantidad'=>'cantidad_presentismo',
            'importe'=>'monto_presentismo',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_remunerativo',
            'descripcion'=>'Remunerativo Bonificable',
            'cantidad'=>'cantidad_remunerativo',
            'importe'=>'monto_remunerativo',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_zona',
            'descripcion'=>'Zona',
            'cantidad'=>'cantidad_zona',
            'importe'=>'monto_zona',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_remunerativo_no_bonificable',
            'descripcion'=>'Remunerativo no Bonificable',
            'cantidad'=>'cantidad_remunerativo_no_bonificable',
            'importe'=>'monto_remunerativo_no_bonificable',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_responsabilidad',
            'descripcion'=>'Responsabilidad',
            'cantidad'=>'cantidad_responsabilidad',
            'importe'=>'monto_responsabilidad',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_sac_proporcional',
            'descripcion'=>'SAC Proporcional',
            'cantidad'=>'cantidad_sac_proporcional',
            'importe'=>'monto_sac_proporcional',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_horas_extras_50',
            'descripcion'=>'Horas extras al 50%',
            'cantidad'=>'cantidad_horas_extras_50',
            'importe'=>'monto_horas_extras_50',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_resoluc_8095',
            'descripcion'=>'Resolucion 8095',
            'cantidad'=>'cantidad_resoluc_8095',
            'importe'=>'monto_resoluc_8095',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_gastos_presentacion',
            'descripcion'=>'Gastos Representacion',
            'cantidad'=>'cantidad_gastos_presentacion',
            'importe'=>'monto_gastos_presentacion',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_asignacion_familiar',
            'descripcion'=>'Asignacion familiar',
            'cantidad'=>'cantidad_asignacion_familiar',
            'importe'=>'monto_asignacion_familiar',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_ayuda_escolar',
            'descripcion'=>'Ayuda escolar',
            'cantidad'=>'cantidad_ayuda_escolar',
            'importe'=>'monto_ayuda_escolar',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_ayuda_escolar_hijodiscapacitado',
            'descripcion'=>'Ayuda escolar hijo discapacitado',
            'cantidad'=>'cantidad_ayuda_escolar_hijodiscapacitado',
            'importe'=>'monto_ayuda_escolar_hijodiscapacitado',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_asignacion_noremunerativo',
            'descripcion'=>'Asignacion no remunerativa',
            'cantidad'=>'cantidad_asignacion_noremunerativo',
            'importe'=>'monto_asignacion_noremunerativo',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_refrigerio',
            'descripcion'=>'Refrigerio',
            'cantidad'=>'cantidad_refrigerio',
            'importe'=>'monto_refrigerio',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_impuesto_ganancias',
            'descripcion'=>'Impuesto Ganancias',
            'cantidad'=>'cantidad_impuesto_ganancias',
            'importe'=>'monto_impuesto_ganancias',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_aporte_os',
            'descripcion'=>'Aporte OS',
            'cantidad'=>'cantidad_aporte_os',
            'importe'=>'monto_aporte_os',
            'empresa_id'=>1
        ]);
       FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_aporte_jubilacion',
            'descripcion'=>'Aporte Jubilacion',
            'cantidad'=>'cantidad_aporte_jubilacion',
            'importe'=>'monto_aporte_jubilacion',
            'empresa_id'=>1
        ]);
       FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_issn',
            'descripcion'=>'ISSN',
            'cantidad'=>'cantidad_issn',
            'importe'=>'monto_issn',
            'empresa_id'=>1
        ]);
       FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_deuda_asistencial_issn',
            'descripcion'=>'Deuda Asistencial ISSN',
            'cantidad'=>'cantidad_deuda_asistencial_issn',
            'importe'=>'monto_deuda_asistencial_issn',
            'empresa_id'=>1
        ]);
       FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_prestamos_turismo_issn',
            'descripcion'=>'Prestamos Turismo ISSN',
            'cantidad'=>'cantidad_prestamos_turismo_issn',
            'importe'=>'monto_prestamos_turismo_issn',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_seg_vida',
            'descripcion'=>'SEG VIDA',
            'cantidad'=>'cantidad_seg_vida',
            'importe'=>'monto_seg_vida',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_seguro_adicional',
            'descripcion'=>'Seguro Adicional',
            'cantidad'=>'cantidad_seguro_adicional',
            'importe'=>'monto_seguro_adicional',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_seguro_amparo_familiar',
            'descripcion'=>'Seguro Amparo Familiar',
            'cantidad'=>'cantidad_seguro_amparo_familiar',
            'importe'=>'monto_seguro_amparo_familiar',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_anticipo_haberes',
            'descripcion'=>'Anticipo Haberes',
            'cantidad'=>'cantidad_anticipo_haberes',
            'importe'=>'monto_anticipo_haberes',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_tranfer',
            'descripcion'=>'Tranfer',
            'cantidad'=>'cantidad_tranfer',
            'importe'=>'monto_tranfer',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_macro',
            'descripcion'=>'Banco Macro',
            'cantidad'=>'cantidad_macro',
            'importe'=>'monto_macro',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_patagonia',
            'descripcion'=>'Banco Patagonia',
            'cantidad'=>'cantidad_patagonia',
            'importe'=>'monto_patagonia',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_aporte_partidario',
            'descripcion'=>'Aporte Partidario',
            'cantidad'=>'cantidad_aporte_partidario',
            'importe'=>'monto_aporte_partidario',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_ipvu',
            'descripcion'=>'IPVU',
            'cantidad'=>'cantidad_ipvu',
            'importe'=>'monto_ipvu',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_descuentos_retributivos',
            'descripcion'=>'Descuentos Retributivos',
            'cantidad'=>'cantidad_descuentos_retributivos',
            'importe'=>'monto_descuentos_retributivos',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_uoem_cuota',
            'descripcion'=>'UOEM Cuota',
            'cantidad'=>'cantidad_uoem_cuota',
            'importe'=>'monto_uoem_cuota',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_upcn_cuota',
            'descripcion'=>'UPCN Cuota',
            'cantidad'=>'cantidad_upcn_cuota',
            'importe'=>'monto_upcn_cuota',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_mudon_cuota',
            'descripcion'=>'MUDOM Cuota',
            'cantidad'=>'cantidad_mudon_cuota',
            'importe'=>'monto_mudon_cuota',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_cuota_alimentaria',
            'descripcion'=>'Cuota Alimentaria',
            'cantidad'=>'cantidad_cuota_alimentaria',
            'importe'=>'monto_cuota_alimentaria',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_embargo_judicial',
            'descripcion'=>'Embargo Judicial',
            'cantidad'=>'cantidad_embargo_judicial',
            'importe'=>'monto_embargo_judicial',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_cuota_diniello',
            'descripcion'=>'Cuota Diniello',
            'cantidad'=>'cantidad_cuota_diniello',
            'importe'=>'monto_cuota_diniello',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_descuento_group',
            'descripcion'=>'Descuento Group',
            'cantidad'=>'cantidad_descuento_group',
            'importe'=>'monto_descuento_group',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_contribuciones_jubilatorias',
            'descripcion'=>'Contribuciones Jubilatorias',
            'cantidad'=>'cantidad_contribuciones_jubilatorias',
            'importe'=>'monto_contribuciones_jubilatorias',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_contribuciones_os',
            'descripcion'=>'Contribuciones OS',
            'cantidad'=>'cantidad_contribuciones_os',
            'importe'=>'monto_contribuciones_os',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_contribuciones_art',
            'descripcion'=>'Contribuciones Art',
            'cantidad'=>'cantidad_contribuciones_art',
            'importe'=>'monto_contribuciones_art',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_bono',
            'descripcion'=>'Bono 1/2',
            'cantidad'=>'cantidad_bono',
            'importe'=>'monto_bono',
            'empresa_id'=>1
        ]);

        FormatoRegistroRecibo::create([
            'tipo'=>'total',
            'codigo'=> '',
            'descripcion'=>'Total Remunerativo',
            'cantidad'=>'',
            'importe'=>'monto_total_remun',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'total',
            'codigo'=> '',
            'descripcion'=>'neto',
            'cantidad'=>'',
            'importe'=>'neto',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'total',
            'codigo'=> '',
            'descripcion'=>'Total no Remunerativo',
            'cantidad'=>'',
            'importe'=>'total_no_remunerativo',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'total',
            'codigo'=> '',
            'descripcion'=>'Total de retenciones',
            'cantidad'=>'',
            'importe'=>'total_retenciones',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'total',
            'codigo'=> '',
            'descripcion'=>'Neto',
            'cantidad'=>'',
            'importe'=>'neto',
            'empresa_id'=>1
        ]);



    }
}
